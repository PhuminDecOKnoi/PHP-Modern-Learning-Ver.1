# 09 — PostgreSQL: Advanced SQL, JSONB and Concurrency

## บทนำ

PostgreSQL เป็น relational database ที่เด่นด้าน data integrity, advanced SQL, transaction isolation, extensible data types, JSONB, full-text search และ query planning เหมาะกับระบบที่มี business rules ซับซ้อน ต้องการ constraint ที่เข้มแข็ง หรือมี query เชิงวิเคราะห์มากกว่าการ CRUD ทั่วไป

การใช้ PostgreSQL อย่างมืออาชีพไม่ใช่เพียงเปลี่ยน DSN จาก MySQL แต่ต้องเข้าใจ SQL dialect, transaction semantics, MVCC, locking, index strategy และวิธีรับมือ serialization failure

---

## ผลลัพธ์การเรียนรู้

- เชื่อมต่อ PostgreSQL ผ่าน `pdo_pgsql`
- ใช้ `RETURNING`, `ON CONFLICT` และ parameterized SQL
- ออกแบบ JSONB โดยไม่ทิ้งหลัก relational modeling
- ใช้ indexes และ `EXPLAIN`
- เข้าใจ Read Committed, Repeatable Read และ Serializable
- รับมือ deadlock และ serialization failure
- ใช้ full-text search และ window functions เบื้องต้น
- แยก OLTP query ออกจาก reporting workload

---

## 1. เชื่อมต่อด้วย PDO

```php
<?php

declare(strict_types=1);

use PDO;

$pdo = new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=app',
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
);
```

ตรวจสอบ extension:

```bash
php --ri pdo_pgsql
```

### Security baseline

- ใช้ TLS เมื่อเชื่อมข้าม host/network ที่ไม่เชื่อถือ
- แยก role สำหรับ migration, application และ read-only reporting
- ไม่ใช้ superuser account กับ web application
- กำหนด `statement_timeout` และ `lock_timeout` ตาม workload

ตัวอย่าง session settings:

```sql
SET statement_timeout = '5s';
SET lock_timeout = '2s';
SET idle_in_transaction_session_timeout = '30s';
```

---

## 2. INSERT ... RETURNING

PostgreSQL สามารถคืนค่าจาก row ที่เพิ่มหรือแก้ไขได้ทันที:

```php
$statement = $pdo->prepare(
    'INSERT INTO employees (employee_code, full_name, metadata)
     VALUES (:employee_code, :full_name, CAST(:metadata AS jsonb))
     RETURNING id, created_at',
);

$statement->execute([
    'employee_code' => 'EMP-0001',
    'full_name' => 'Example Employee',
    'metadata' => json_encode(
        ['source' => 'hr-import'],
        JSON_THROW_ON_ERROR,
    ),
]);

$created = $statement->fetch();
```

ประโยชน์:

- ไม่ต้อง query ซ้ำเพื่อหา generated ID
- คืนค่า generated/default columns ได้
- ลด race condition จากการค้นหา row ภายหลัง

---

## 3. Upsert ด้วย ON CONFLICT

```sql
INSERT INTO employee_balances (
    employee_id,
    balance_type,
    amount
)
VALUES (
    :employee_id,
    :balance_type,
    :amount
)
ON CONFLICT (employee_id, balance_type)
DO UPDATE SET
    amount = EXCLUDED.amount,
    updated_at = CURRENT_TIMESTAMP
RETURNING employee_id, balance_type, amount;
```

คำว่า `EXCLUDED` หมายถึงค่าที่พยายาม insert เข้ามา

### ข้อควรระวัง

- ต้องมี unique constraint/index ที่ตรงกับ conflict target
- Upsert ไม่ได้แทน transaction สำหรับหลายตาราง
- ต้องกำหนด business rule ว่า overwrite ได้หรือไม่
- ระวัง lost update หาก update โดยไม่ตรวจ version

---

## 4. Optimistic Concurrency Control

เพิ่ม version column:

```sql
ALTER TABLE employee_profiles
ADD COLUMN version INTEGER NOT NULL DEFAULT 1;
```

Update แบบตรวจ version:

```sql
UPDATE employee_profiles
SET
    full_name = :full_name,
    version = version + 1,
    updated_at = CURRENT_TIMESTAMP
WHERE id = :id
  AND version = :expected_version
RETURNING version;
```

PHP:

```php
$statement->execute([
    'id' => $id,
    'full_name' => $fullName,
    'expected_version' => $expectedVersion,
]);

$newVersion = $statement->fetchColumn();

if ($newVersion === false) {
    throw new RuntimeException(
        'The record was changed by another transaction.',
    );
}
```

เหมาะกับ form editing และ API update ที่ไม่ต้อง lock row ไว้นาน

---

## 5. Transaction Isolation

PostgreSQL รองรับ:

| Level | พฤติกรรมหลัก |
|---|---|
| Read Committed | แต่ละ statement เห็นข้อมูลที่ commit ก่อน statement เริ่ม |
| Repeatable Read | transaction เห็น snapshot เดิมตลอด transaction |
| Serializable | ตรวจให้ผลลัพธ์เทียบเท่าการรันทีละ transaction |

กำหนด isolation:

```php
$pdo->beginTransaction();
$pdo->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
```

Serializable transaction อาจถูกยกเลิกด้วย serialization failure ซึ่งเป็นพฤติกรรมปกติเพื่อรักษาความถูกต้อง application ต้อง retry transaction ทั้งชุดเมื่อ operation นั้น retry ได้อย่างปลอดภัย

```php
function runSerializable(PDO $pdo, callable $operation, int $maxAttempts = 3): mixed
{
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
            $pdo->beginTransaction();
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

            $result = $operation($pdo);
            $pdo->commit();

            return $result;
        } catch (PDOException $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // PostgreSQL SQLSTATE 40001 = serialization_failure
            $retryable = $error->getCode() === '40001';

            if (!$retryable || $attempt === $maxAttempts) {
                throw $error;
            }

            usleep(random_int(20_000, 80_000) * $attempt);
        }
    }

    throw new LogicException('Unreachable code.');
}
```

### Retry rules

- Retry transaction ทั้งชุด ไม่ใช่ statement เดียว
- ใช้ backoff และ jitter
- operation ภายนอก transaction ต้อง idempotent
- อย่าส่งอีเมลหรือเรียก payment API ภายใน transaction แล้ว retry แบบไม่ควบคุม

---

## 6. Row Locking

```sql
SELECT id, balance
FROM leave_balances
WHERE employee_id = :employee_id
FOR UPDATE;
```

ใช้เมื่อต้องป้องกัน row จาก concurrent update ภายใน transaction

### ข้อควรระวัง

- Lock ตามลำดับเดียวกันทุก code path เพื่อลด deadlock
- Transaction ต้องสั้น
- หลีกเลี่ยง user interaction ขณะถือ lock
- มี lock timeout
- ตรวจ query plan เพื่อไม่ lock เกินขอบเขต

---

## 7. JSONB อย่างมีวินัย

JSONB เหมาะกับ attributes ที่เปลี่ยนแปลงได้หรือไม่เหมาะกับ columns จำนวนมาก แต่ไม่ควรใช้แทน relational schema ทั้งหมด

```sql
CREATE TABLE audit_events (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    event_type TEXT NOT NULL,
    actor_id BIGINT,
    payload JSONB NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX audit_events_payload_gin
ON audit_events USING GIN (payload);
```

Query:

```sql
SELECT id, event_type, payload
FROM audit_events
WHERE payload @> '{"severity":"high"}'::jsonb;
```

### ใช้ JSONB เมื่อ

- โครงสร้างย่อยมี variation สูง
- เก็บ event payload
- เก็บ metadata ที่ไม่ได้ join บ่อย
- ต้อง query key บางส่วน

### ไม่ควรใช้เมื่อ

- field เป็น business key สำคัญ
- ต้อง foreign key
- ต้อง aggregate/report บ่อย
- ต้อง validation เชิง schema เข้มแข็ง
- ต้อง join เป็นหลัก

---

## 8. Full-Text Search

```sql
ALTER TABLE policies
ADD COLUMN search_vector tsvector
GENERATED ALWAYS AS (
    to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(content, ''))
) STORED;

CREATE INDEX policies_search_idx
ON policies USING GIN (search_vector);
```

ค้นหา:

```sql
SELECT id, title,
       ts_rank(search_vector, plainto_tsquery('simple', :query)) AS rank
FROM policies
WHERE search_vector @@ plainto_tsquery('simple', :query)
ORDER BY rank DESC;
```

สำหรับภาษาไทยต้องประเมิน tokenizer/dictionary และคุณภาพ segmentation เพิ่มเติม เพราะ configuration มาตรฐานอาจไม่ให้ผลเหมือนภาษาอังกฤษ

---

## 9. Window Functions

ตัวอย่างจัดอันดับยอดวันลาตามหน่วยงาน:

```sql
SELECT
    department_id,
    employee_id,
    total_days,
    RANK() OVER (
        PARTITION BY department_id
        ORDER BY total_days DESC
    ) AS department_rank
FROM yearly_leave_summary;
```

Window function ช่วยทำ:

- Ranking
- Running total
- Moving average
- Previous/next row comparison
- Partitioned analytics

โดยไม่ต้องยุบทุก row เหมือน `GROUP BY`

---

## 10. EXPLAIN และ Index Strategy

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT id, email
FROM users
WHERE lower(email) = lower(:email);
```

Expression index:

```sql
CREATE UNIQUE INDEX users_email_lower_unique
ON users (lower(email));
```

### อ่านผลอย่างมีหลัก

- Actual time
- Rows estimated vs actual
- Sequential scan vs index scan
- Filtered rows
- Buffers
- Sort method
- Nested loop/hash join/merge join

อย่าสร้าง index ทุก column เพราะ index เพิ่มต้นทุนในการ insert/update และใช้พื้นที่

---

## 11. Schema Design Principles

- ใช้ `TIMESTAMPTZ` สำหรับเหตุการณ์ที่เกิดขึ้นจริงตามเวลา
- ใช้ `DATE` สำหรับวันที่ไม่มี time-of-day
- ใช้ `NUMERIC` สำหรับจำนวนเงินที่ต้องการความแม่นยำ decimal
- ใช้ `CHECK` constraints สำหรับ domain rules
- ใช้ `NOT NULL` เมื่อข้อมูลจำเป็นจริง
- ใช้ `GENERATED ... AS IDENTITY` แทน sequence handling แบบเก่าในงานใหม่
- ใช้ enum อย่างระมัดระวังเมื่อสถานะเปลี่ยนบ่อย

ตัวอย่าง constraint:

```sql
CREATE TABLE corrective_actions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    finding_id BIGINT NOT NULL REFERENCES audit_findings(id),
    due_date DATE NOT NULL,
    status TEXT NOT NULL CHECK (
        status IN ('open', 'in_progress', 'verified', 'closed')
    )
);
```

---

## แบบฝึกปฏิบัติ

1. สร้างตาราง audit events ที่มี JSONB payload และ GIN index
2. เขียน upsert สำหรับยอดคงเหลือวันลา
3. จำลอง concurrent update แล้วแก้ด้วย optimistic locking
4. รัน `EXPLAIN (ANALYZE, BUFFERS)` กับ query ที่ไม่มี index และมี index
5. สร้าง transaction แบบ Serializable และจำลอง retry
6. ใช้ window function สร้าง ranking ตามหน่วยงาน

---

## Production Checklist

- [ ] มี statement/lock timeout
- [ ] ใช้ role แยกตามหน้าที่
- [ ] Transaction สั้นและ retry-aware
- [ ] JSONB ใช้เฉพาะข้อมูลที่เหมาะสม
- [ ] Query สำคัญผ่าน EXPLAIN review
- [ ] Index มีหลักฐานจาก workload
- [ ] มี backup, restore และ point-in-time recovery plan
- [ ] Monitor long-running transaction และ lock wait
- [ ] Integration tests ใช้ PostgreSQL จริง

---

## References

- PostgreSQL Current Documentation — https://www.postgresql.org/docs/current/
- Transaction Isolation — https://www.postgresql.org/docs/current/transaction-iso.html
- JSON Types — https://www.postgresql.org/docs/current/datatype-json.html
- Full-Text Search — https://www.postgresql.org/docs/current/textsearch.html
- PHP Manual: PDO PostgreSQL — https://www.php.net/manual/en/ref.pdo-pgsql.php
