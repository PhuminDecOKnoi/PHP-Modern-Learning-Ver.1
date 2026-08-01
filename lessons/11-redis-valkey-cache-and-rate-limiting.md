# 11 — Redis and Valkey: Cache, Session and Rate Limiting

## บทนำ

Redis และ Valkey เป็น in-memory key-value data stores ที่มักใช้เป็น infrastructure component ร่วมกับฐานข้อมูลหลัก ไม่ใช่ตัวแทน relational database โดยอัตโนมัติ เหมาะกับ cache, session store, rate limiter, distributed coordination, short-lived data และ messaging patterns บางประเภท

บทนี้เน้นการออกแบบ cache ที่ถูกต้อง เพราะปัญหาส่วนใหญ่ไม่ได้เกิดจากคำสั่ง `GET` หรือ `SET` แต่เกิดจาก stale data, cache stampede, key collision, serialization, memory pressure, failure policy และการผูก business logic กับ cache มากเกินไป

---

## ผลลัพธ์การเรียนรู้

- เชื่อมต่อ Redis-compatible server ผ่าน PHP client
- ออกแบบ key namespace และ TTL
- ใช้ cache-aside pattern
- ป้องกัน cache stampede
- ออกแบบ distributed rate limiting
- ใช้ Redis เป็น session store อย่างระมัดระวัง
- เข้าใจ fail-open และ fail-closed policy
- แยก cache correctness ออกจาก source-of-truth correctness

---

## 1. Client Options

แนวทางที่พบบ่อย:

- Predis — pure PHP library ติดตั้งผ่าน Composer
- phpredis — native PHP extension

ตัวอย่าง Predis:

```bash
composer require predis/predis
```

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Predis\Client;

$redis = new Client([
    'scheme' => 'tcp',
    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    'database' => 0,
    'timeout' => 1.0,
    'read_write_timeout' => 1.0,
]);

$redis->ping();
```

### Production baseline

- ใช้ TLS เมื่อเชื่อมผ่าน network ที่ไม่เชื่อถือ
- จำกัด network access
- ใช้ authentication/ACL
- ตั้ง connect/read timeout
- ไม่ log password หรือ full connection string
- เลือก client ที่รองรับ deployment topology ของระบบ

---

## 2. Key Design

รูปแบบ key ที่ดีควรสื่อ domain, tenant, entity และ version:

```text
hr:v1:employee:1001
hr:v1:department:20:employees
api:v1:rate:user:1001
session:v1:abc123
```

### หลักปฏิบัติ

- ใช้ prefix ป้องกัน collision
- ใส่ schema/cache version เพื่อ invalidate ทั้งชุดได้
- หลีกเลี่ยง key ยาวเกินจำเป็น
- อย่าใส่ข้อมูลส่วนบุคคลที่อ่านได้ตรง ๆ หากไม่จำเป็น
- กำหนด ownership ของ key แต่ละ namespace

---

## 3. Cache-Aside Pattern

Flow:

1. อ่าน cache
2. ถ้า hit ให้คืนค่า
3. ถ้า miss ให้อ่าน source of truth
4. serialize และเขียน cache พร้อม TTL
5. คืนค่า

```php
<?php

declare(strict_types=1);

use Predis\ClientInterface;

final readonly class EmployeeCache
{
    public function __construct(
        private ClientInterface $redis,
        private EmployeeRepository $repository,
    ) {
    }

    public function find(int $employeeId): ?array
    {
        $key = "hr:v1:employee:{$employeeId}";
        $cached = $this->redis->get($key);

        if (is_string($cached)) {
            return json_decode(
                $cached,
                true,
                flags: JSON_THROW_ON_ERROR,
            );
        }

        $employee = $this->repository->findArrayById($employeeId);

        if ($employee === null) {
            // Negative cache ป้องกัน repeated misses
            $this->redis->setex($key . ':missing', 30, '1');
            return null;
        }

        $this->redis->setex(
            $key,
            300,
            json_encode($employee, JSON_THROW_ON_ERROR),
        );

        return $employee;
    }
}
```

### Source of truth

Cache ต้องไม่เป็นแหล่งความจริงหลักของข้อมูลธุรกิจ เว้นแต่ระบบถูกออกแบบมาโดยเฉพาะ

---

## 4. Cache Invalidation

เมื่อ update source of truth:

```php
$repository->update($employee);
$redis->del(["hr:v1:employee:{$employee->id}"]);
```

แนวทางหลัก:

- Delete-on-write — ลบ cache หลังเขียน DB สำเร็จ
- Write-through — เขียน DB และ cache ผ่าน component เดียว
- Versioned keys — เปลี่ยน namespace/version
- Event-driven invalidation — publish event หลัง commit

### ข้อควรระวัง

- ลบ cache ก่อน DB commit อาจเกิด stale repopulation
- เขียน DB สำเร็จแต่ลบ cache ล้มเหลวต้องมี recovery strategy
- TTL ไม่ใช่ invalidation strategy ที่สมบูรณ์ แต่เป็น safety net

---

## 5. Cache Stampede

เมื่อ popular key หมดอายุพร้อมกัน หลาย request อาจ query database พร้อมกัน

แนวทางป้องกัน:

- TTL jitter
- Lock around regeneration
- Stale-while-revalidate
- Probabilistic early refresh
- Request coalescing

TTL jitter:

```php
$baseTtl = 300;
$jitter = random_int(0, 60);
$redis->setex($key, $baseTtl + $jitter, $payload);
```

Simple lock:

```php
$lockKey = $key . ':lock';
$lockToken = bin2hex(random_bytes(16));

$acquired = $redis->set(
    $lockKey,
    $lockToken,
    'EX',
    10,
    'NX',
);
```

การปล่อย lock ต้องตรวจ token แบบ atomic ไม่ควร `DEL` โดยไม่ตรวจ ownership

---

## 6. Distributed Rate Limiting

Local counter ใช้ไม่ได้เมื่อ application มีหลาย instance จึงต้องใช้ shared store

Fixed-window concept:

```php
$key = "api:v1:rate:user:{$userId}:" . gmdate('YmdHi');
$count = $redis->incr($key);

if ($count === 1) {
    $redis->expire($key, 60);
}

if ($count > 100) {
    http_response_code(429);
    header('Retry-After: 60');
    exit('Too Many Requests');
}
```

### Race condition

`INCR` และ `EXPIRE` แยกคำสั่งอาจเกิด key ที่ไม่มี TTL หาก process หยุดกลางทาง ควรใช้:

- Transaction (`MULTI`/`EXEC`)
- Lua script
- Server-side function ตามความสามารถระบบ

Token bucket เหมาะกับการอนุญาต burst ภายใต้อัตราเฉลี่ย ส่วน sliding window ให้ความแม่นยำกว่า fixed window แต่ใช้ resource มากขึ้น

---

## 7. Lua Script สำหรับ Atomic Decision

```lua
local current = redis.call('INCR', KEYS[1])

if current == 1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
end

if current > tonumber(ARGV[2]) then
    return {0, current}
end

return {1, current}
```

PHP ควร cache script SHA และ fallback เมื่อ script cache ถูกล้าง

### Security

- จำกัด script ที่ application เรียกได้
- อย่าสร้าง Lua code จาก user input
- ส่งข้อมูลผ่าน `KEYS` และ `ARGV`
- กำหนด execution complexity

---

## 8. Session Store

Redis ช่วยแชร์ session ระหว่างหลาย application instances ได้ แต่ต้องออกแบบ:

- Session TTL
- Secure session ID
- Regenerate ID หลัง login
- Logout/delete session
- Concurrent request behavior
- Failover policy
- Encryption สำหรับข้อมูลอ่อนไหวตาม threat model

ไม่ควรเก็บ object ขนาดใหญ่ใน session และไม่ควรใช้ session เป็นฐานข้อมูลผู้ใช้

---

## 9. Fail-Open vs Fail-Closed

เมื่อ Redis ใช้งานไม่ได้ ต้องตัดสินใจตามหน้าที่:

| Use case | แนวทางที่เป็นไปได้ |
|---|---|
| Product cache | Fail open ไป DB |
| Optional personalization | ข้าม cache/feature |
| Login session | มัก fail closed เพราะยืนยันสถานะไม่ได้ |
| Abuse rate limit | เลือกตามความเสี่ยง; endpoint สำคัญมัก fail closed |
| Metrics buffer | อาจ drop ภายใต้ policy |

Policy ต้องกำหนดล่วงหน้า ไม่ใช่เกิด incident แล้วค่อยตัดสินใจ

---

## 10. Serialization

ตัวเลือก:

- JSON — อ่านง่าย ข้ามภาษาได้ แต่ต้องจัดการ types
- PHP serialize — ผูกกับ PHP และมีความเสี่ยงหาก unserialize ข้อมูลที่ไม่เชื่อถือ
- MessagePack/อื่น ๆ — compact แต่เพิ่ม dependency

แนวทางแนะนำสำหรับบทเรียน:

```php
$payload = json_encode([
    'schemaVersion' => 1,
    'employeeId' => $employeeId,
    'status' => 'active',
], JSON_THROW_ON_ERROR);
```

ใส่ schema version เพื่อรองรับการเปลี่ยนโครงสร้าง

---

## 11. Memory and Eviction

ต้องเข้าใจ:

- `maxmemory`
- Eviction policy
- TTL distribution
- Hot keys
- Big keys
- Fragmentation
- Persistence configuration
- Replication/failover

Cache ที่ไม่มี TTL อาจโตไม่จำกัด ส่วน eviction อาจลบข้อมูลโดยไม่แจ้ง application จึงห้ามออกแบบ business correctness ให้ขึ้นกับ cache entry ที่อาจหาย

---

## 12. Testing

- Unit test cache wrapper ด้วย fake client
- Integration test กับ Redis-compatible server จริง
- ทดสอบ TTL
- ทดสอบ cache miss/hit
- ทดสอบ stale cache
- ทดสอบ Redis unavailable
- ทดสอบ concurrent rate-limit decisions
- Load test hot key และ stampede

---

## แบบฝึกปฏิบัติ

1. สร้าง Employee cache-aside service
2. เพิ่ม TTL jitter
3. จำลอง stale cache หลัง update
4. เขียน atomic fixed-window limiter ด้วย Lua
5. ตัดสิน fail-open/fail-closed สำหรับ 5 endpoints
6. ตรวจ big keys และเสนอ key redesign
7. สร้าง session expiry checklist

---

## Production Checklist

- [ ] Key namespace ชัดเจน
- [ ] Cache entries มี TTL
- [ ] มี invalidation strategy
- [ ] ป้องกัน stampede
- [ ] Serialization มี schema version
- [ ] Rate limiter atomic
- [ ] มี timeout และ circuit-breaker policy
- [ ] กำหนด fail-open/fail-closed
- [ ] Monitor memory, eviction, hot keys และ latency
- [ ] Cache ไม่ใช่ source of truth โดยไม่ตั้งใจ

---

## References

- Redis PHP Client Guide — https://redis.io/docs/latest/develop/clients/php/
- Redis Cache-Aside with PHP — https://redis.io/docs/latest/develop/use-cases/cache-aside/php/
- Redis Rate Limiting — https://redis.io/docs/latest/develop/use-cases/rate-limiter/
- Valkey Documentation — https://valkey.io/topics/
- Predis — https://github.com/predis/predis
