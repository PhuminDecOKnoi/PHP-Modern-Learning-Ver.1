# 14 — HTTP Client, cURL, URI and PSR Standards

## บทนำ

ระบบ PHP สมัยใหม่ไม่ได้ทำหน้าที่เป็น API server อย่างเดียว แต่ต้องเรียกบริการภายนอก เช่น identity provider, payroll service, document service, payment gateway และ AI API การเรียก HTTP แบบมืออาชีพต้องควบคุม timeout, TLS, retry, redirect, authentication, observability และ SSRF ไม่ใช่เพียงเรียก `file_get_contents()` กับ URL

---

## ผลลัพธ์การเรียนรู้

- ส่ง HTTP request ด้วย cURL อย่างปลอดภัย
- แยก connect timeout และ total timeout
- ตรวจ status code, headers และ response body
- ใช้ URI API/การสร้าง query โดยไม่ต่อ string แบบเสี่ยง
- เข้าใจ PSR-7, PSR-17 และ PSR-18
- ออกแบบ retry, idempotency และ circuit breaker
- ป้องกัน SSRF และ unsafe redirect
- ทดสอบ HTTP integration ด้วย fake client และ contract tests

---

## 1. HTTP Request ที่มีขอบเขตชัดเจน

```php
<?php

declare(strict_types=1);

function requestJson(string $url, array $headers = []): array
{
    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException('Unable to initialize cURL.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge([
            'Accept: application/json',
        ], $headers),
        CURLOPT_CONNECTTIMEOUT_MS => 1_000,
        CURLOPT_TIMEOUT_MS => 5_000,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    try {
        $body = curl_exec($handle);

        if ($body === false) {
            throw new RuntimeException(
                'HTTP transport failed: ' . curl_error($handle),
            );
        }

        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("Unexpected HTTP status: {$status}");
        }

        return json_decode(
            $body,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    } finally {
        curl_close($handle);
    }
}
```

---

## 2. Timeout ไม่ใช่ค่าเดียว

ควรกำหนดอย่างน้อย:

- DNS/connect timeout
- TLS handshake/connection timeout
- Total request timeout
- Read timeout ตาม client/library
- Application deadline

ห้ามปล่อย request ภายนอกค้างโดยไม่มีขอบเขต เพราะจะกิน PHP workers และทำให้ระบบล่มแบบ cascading failure

---

## 3. POST JSON

```php
$payload = json_encode([
    'employeeId' => 1001,
    'action' => 'verify',
], JSON_THROW_ON_ERROR);

$handle = curl_init('https://service.example/api/verifications');

curl_setopt_array($handle, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
        'Idempotency-Key: ' . $idempotencyKey,
    ],
    CURLOPT_CONNECTTIMEOUT_MS => 1_000,
    CURLOPT_TIMEOUT_MS => 5_000,
]);
```

อย่า log access token หรือ request body ที่มีข้อมูลอ่อนไหว

---

## 4. URI และ Query Parameters

หลีกเลี่ยงการต่อ query string ด้วยมือ:

```php
$query = http_build_query([
    'status' => 'active',
    'page' => 1,
    'perPage' => 50,
], encoding_type: PHP_QUERY_RFC3986);

$url = 'https://service.example/api/employees?' . $query;
```

PHP 8.5 เพิ่ม URI extension สำหรับ parse และ normalize URI ตามมาตรฐาน จึงควรใช้ object API เมื่อ environment รองรับ โดยเฉพาะงาน callback URL, redirect validation และ relative URI resolution

---

## 5. SSRF Protection

SSRF เกิดเมื่อ server ถูกหลอกให้เรียก URL ที่ผู้โจมตีกำหนด เช่น localhost, metadata endpoint หรือ private network

แนวทาง:

- ใช้ allowlist host/scheme/port
- อนุญาตเฉพาะ HTTPS เมื่อเหมาะสม
- Resolve DNS และตรวจ private/reserved ranges
- ป้องกัน DNS rebinding ตาม threat model
- ไม่ follow redirects โดยอัตโนมัติ
- ตรวจ redirect target ทุกครั้ง
- ใช้ outbound proxy/firewall
- จำกัด response size และ timeout

ตัวอย่าง allowlist เบื้องต้น:

```php
function assertAllowedUrl(string $url): void
{
    $parts = parse_url($url);

    if (!is_array($parts)) {
        throw new InvalidArgumentException('Invalid URL.');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));

    $allowedHosts = ['api.example.com', 'identity.example.com'];

    if ($scheme !== 'https' || !in_array($host, $allowedHosts, true)) {
        throw new InvalidArgumentException('URL is not allowed.');
    }
}
```

Allowlist ระดับ application ควรทำงานร่วมกับ network control

---

## 6. Redirect Policy

การเปิด `CURLOPT_FOLLOWLOCATION` โดยไม่ควบคุมอาจพา request ไปยัง host ที่ไม่อนุญาต

แนวทาง professional:

1. ปิด auto redirect
2. อ่าน `Location`
3. Resolve URI
4. ตรวจ allowlist ใหม่
5. จำกัดจำนวน redirects
6. ตัด authorization header เมื่อเปลี่ยน origin

---

## 7. Retry Policy

Retry ได้เมื่อ:

- Connection reset ก่อนส่ง request สำเร็จ
- Timeout ที่ operation เป็น idempotent
- HTTP 429 ตาม `Retry-After`
- HTTP 502/503/504 ตาม policy

ไม่ควร retry อัตโนมัติเมื่อ:

- Validation error 4xx
- Authentication failure
- POST ที่ไม่มี idempotency key
- Operation ที่อาจสร้างผลซ้ำ

ใช้ exponential backoff + jitter และจำกัด total deadline

---

## 8. Idempotency

สำหรับ create operation ที่ client อาจ retry:

```text
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
```

Server ต้องเก็บผลลัพธ์ตาม key และป้องกันการประมวลผลซ้ำภายในช่วงเวลาที่กำหนด

---

## 9. Circuit Breaker Concept

State:

- Closed — เรียกตามปกติ
- Open — ปฏิเสธเร็วเมื่อปลายทางล้มเหลวต่อเนื่อง
- Half-open — ทดลอง request จำนวนจำกัด

Circuit breaker ป้องกัน resource exhaustion แต่ไม่แทน timeout, retry หรือ fallback

---

## 10. cURL Multi

`curl_multi_init()` ช่วยประมวลผลหลาย handles พร้อมกัน เหมาะกับ independent requests

ข้อควรระวัง:

- จำกัด concurrency
- แยก timeout ต่อ request
- เก็บ correlation ID
- อย่าส่ง burst ไปทำร้าย downstream
- จัดการ partial success

---

## 11. PSR-7, PSR-17 และ PSR-18

| Standard | หน้าที่ |
|---|---|
| PSR-7 | HTTP request/response/stream/URI interfaces |
| PSR-17 | Factories สำหรับสร้าง PSR-7 objects |
| PSR-18 | HTTP client interface |

ตัวอย่าง dependency-injected client:

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final readonly class DirectoryClient
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
    ) {
    }

    public function findEmployee(int $id): array
    {
        $request = $this->requestFactory
            ->createRequest('GET', "https://api.example.com/employees/{$id}")
            ->withHeader('Accept', 'application/json');

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Directory service failed.');
        }

        return json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }
}
```

ประโยชน์คือเปลี่ยน HTTP client implementation และใช้ fake client ใน tests ได้ง่าย

---

## 12. Observability

เก็บ metrics:

- Request count
- Latency
- Timeout count
- Status class
- Retry count
- Circuit state
- Response size

Log fields:

- Operation name
- Downstream service
- Correlation ID
- Duration
- Status/error category

ห้าม log token, password หรือ full personal payload

---

## แบบฝึกปฏิบัติ

1. สร้าง HTTP client ที่มี timeout และ JSON error handling
2. เขียน SSRF allowlist tests
3. เพิ่ม retry เฉพาะ GET/429/503
4. สร้าง PSR-18 client wrapper และ fake test client
5. เปรียบเทียบ sequential กับ cURL multi
6. ออกแบบ idempotency flow สำหรับ create employee API

---

## Production Checklist

- [ ] มี connect และ total timeout
- [ ] TLS verification เปิดอยู่
- [ ] Redirect ถูกควบคุม
- [ ] URL ผ่าน SSRF allowlist
- [ ] Retry เฉพาะ operation ที่ปลอดภัย
- [ ] POST สำคัญมี idempotency
- [ ] จำกัด response size/concurrency
- [ ] Secrets ถูก redact
- [ ] มี metrics และ correlation ID
- [ ] HTTP client test ได้ผ่าน interface

---

## References

- PHP cURL — https://www.php.net/manual/en/book.curl.php
- PHP cURL Multi — https://www.php.net/manual/en/function.curl-multi-init.php
- PHP 8.5 Release — https://www.php.net/releases/8.5/en.php
- PSR-7 — https://www.php-fig.org/psr/psr-7/
- PSR-17 — https://www.php-fig.org/psr/psr-17/
- PSR-18 — https://www.php-fig.org/psr/psr-18/
