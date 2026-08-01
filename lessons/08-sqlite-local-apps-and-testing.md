# 08 — SQLite for Local Applications and Testing

## บทนำ

SQLite เป็น relational database ที่เก็บข้อมูลหลักไว้ในไฟล์เดียวและทำงานแบบ embedded โดยไม่ต้องติดตั้ง database server แยก เหมาะกับงานที่ต้องการ deployment ง่าย มีขนาดเล็ก หรือทำงานแบบ local-first เช่น CLI tools, desktop utilities, prototypes, test environments และ edge applications

SQLite ไม่ใช่ “MySQL รุ่นเล็ก” แต่มี transaction model, locking model, type system และข้อจำกัดที่ต่างกัน ผู้พัฒนาจึงต้องเข้าใจว่า SQLite เหมาะกับงานใด และไม่ควรใช้ในสถานการณ์ใด

---

## ผลลัพธ์การเรียนรู้

- เชื่อมต่อ SQLite ผ่าน `pdo_sqlite`
- ใช้ file database และ in-memory database
- เปิดใช้งาน foreign key enforcement อย่างชัดเจน
- เข้าใจ transaction, journal และ WAL mode
- ออกแบบ schema ที่เหมาะกับ SQLite
- ใช้ SQLite สำหรับ integration tests โดยไม่เข้าใจผิดว่าแทน production database ได้ทั้งหมด
- จัดการ concurrent writes และ database locking อย่างมีเหตุผล

---

## 1. ตรวจสอบ Environment

```bash
php --ri pdo_sqlite
php -r "print_r(PDO::getAvailableDrivers());"
```

สร้าง connection แบบไฟล์:

```php
<?php

declare(strict_types=1);

use PDO;

$databasePath = __DIR__ . '/../var/app.sqlite';
$pdo = new PDO('sqlite:' . $databasePath, options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

สร้าง in-memory database:

```php
$pdo = new PDO('sqlite::memory:', options: [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
```

> In-memory database จะมีอายุเท่ากับ connection นั้น เมื่อ connection ถูกทำลาย ข้อมูลจะหายไป

---

## 2. Foreign Keys ต้องเปิดใช้งานต่อ Connection

SQLite รองรับ foreign keys แต่ควรเปิดอย่างชัดเจนในทุก connection:

```php
$pdo->exec('PRAGMA foreign_keys = ON');
```

ตรวจสอบสถานะ:

```php
$status = $pdo->query('PRAGMA foreign_keys')->fetchColumn();

if ((int) $status !== 1) {
    throw new RuntimeException('SQLite foreign key enforcement is disabled.');
}
```

ตัวอย่าง schema:

```sql
CREATE TABLE departments (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE employees (
    id INTEGER PRIMARY KEY,
    department_id INTEGER NOT NULL,
    employee_code TEXT NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    FOREIGN KEY (department_id)
        REFERENCES departments(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);
```

### Professional rule

อย่าสมมติว่า constraint ถูก enforce เพราะเขียน `FOREIGN KEY` ไว้ใน schema ต้องตรวจ runtime configuration ด้วย

---

## 3. Transaction และ Atomicity

SQLite ทำงานภายใน transaction เสมอ ทั้งแบบ implicit และ explicit

```php
$pdo->beginTransaction();

try {
    $department = $pdo->prepare(
        'INSERT INTO departments (name) VALUES (:name)',
    );
    $department->execute(['name' => 'Compliance']);

    $departmentId = (int) $pdo->lastInsertId();

    $employee = $pdo->prepare(
        'INSERT INTO employees
            (department_id, employee_code, full_name)
         VALUES
            (:department_id, :employee_code, :full_name)',
    );
    $employee->execute([
        'department_id' => $departmentId,
        'employee_code' => 'EMP-0001',
        'full_name' => 'Example Employee',
    ]);

    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $error;
}
```

SQLite ไม่รองรับ nested `BEGIN...COMMIT` แบบตรง ๆ แต่สามารถใช้ `SAVEPOINT`, `RELEASE` และ `ROLLBACK TO` ได้

---

## 4. Journal Mode และ WAL

SQLite ใช้ journal เพื่อรักษาความถูกต้องเมื่อ transaction ล้มเหลว โดยมีสองแนวทางหลัก:

- Rollback journal
- Write-Ahead Logging (WAL)

เปิด WAL:

```php
$journalMode = $pdo->query('PRAGMA journal_mode = WAL')->fetchColumn();
```

WAL ช่วยให้ reader และ writer ทำงานร่วมกันได้ดีขึ้นในหลายกรณี แต่ไม่ได้ทำให้ SQLite กลายเป็นระบบ distributed database และยังคงมี writer หลักทีละหนึ่ง transaction

### สิ่งที่ต้องพิจารณา

- ไฟล์ `-wal` และ `-shm` เป็นส่วนหนึ่งของสถานะขณะทำงาน
- การ backup ต้องใช้วิธีที่รับรู้ transaction
- Network filesystem บางชนิดไม่เหมาะกับ SQLite locking/WAL
- ต้องมี checkpoint strategy สำหรับระบบที่เขียนต่อเนื่อง

---

## 5. Busy Timeout และ Lock Contention

เมื่อมี concurrent writes อาจพบ `database is locked`

```php
$pdo->exec('PRAGMA busy_timeout = 5000');
```

หรือกำหนดผ่าน application retry policy:

```php
function retryLockedOperation(callable $operation, int $maxAttempts = 3): mixed
{
    $attempt = 0;

    while (true) {
        try {
            return $operation();
        } catch (PDOException $error) {
            $attempt++;

            $isLocked = str_contains(
                strtolower($error->getMessage()),
                'database is locked',
            );

            if (!$isLocked || $attempt >= $maxAttempts) {
                throw $error;
            }

            usleep(50_000 * $attempt);
        }
    }
}
```

> การตรวจข้อความ exception เป็นเพียงตัวอย่างเชิงการเรียนรู้ ใน production ควรตรวจ driver code และกำหนด retry policy ให้แม่นยำ

---

## 6. Type System และ STRICT Tables

SQLite มี dynamic type system ซึ่งต่างจากฐานข้อมูล server หลายระบบ ควรกำหนด schema และ validation อย่างรอบคอบ

ตัวอย่าง STRICT table:

```sql
CREATE TABLE audit_findings (
    id INTEGER PRIMARY KEY,
    code TEXT NOT NULL,
    severity INTEGER NOT NULL CHECK (severity BETWEEN 1 AND 5),
    description TEXT NOT NULL,
    created_at TEXT NOT NULL
) STRICT;
```

### แนวทาง

- ใช้ `CHECK` constraints
- ใช้ `STRICT` เมื่อ compatibility อนุญาต
- กำหนดรูปแบบวันเวลาให้ชัด เช่น ISO 8601 UTC
- อย่าเก็บ boolean/date แบบกำกวมโดยไม่มี convention

---

## 7. SQLite สำหรับ Tests

ตัวอย่าง test fixture:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

abstract class SqliteTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                email TEXT NOT NULL UNIQUE
            ) STRICT',
        );
    }
}
```

### ข้อดี

- เร็ว
- ไม่ต้องสร้าง service ภายนอก
- แยก test ได้ง่าย
- เหมาะกับ repository contract tests บางชนิด

### ข้อจำกัด

SQLite ไม่สามารถยืนยันพฤติกรรมของ:

- MySQL collation
- PostgreSQL JSONB
- Vendor-specific locking
- Isolation level
- Generated columns บางรูปแบบ
- Full-text search engine เฉพาะระบบ
- Query planner และ index behavior ของ production database

ดังนั้น test suite ที่ดีควรมีทั้ง:

1. Fast SQLite tests
2. Production-database integration tests

---

## 8. Backup ที่ปลอดภัย

อย่า copy ไฟล์ database ขณะที่มี transaction เขียนอยู่โดยไม่เข้าใจ journal state

แนวทางที่เหมาะสม:

- ใช้ SQLite backup API หรือ CLI `.backup`
- ทำ checkpoint เมื่อใช้ WAL ตามความเหมาะสม
- ตรวจสอบ integrity หลัง backup
- เก็บไฟล์ database, WAL และ metadata อย่างสอดคล้อง
- ทดสอบ restore จริงเป็นระยะ

ตรวจสอบ integrity:

```sql
PRAGMA integrity_check;
PRAGMA foreign_key_check;
```

---

## 9. เมื่อใดควรใช้ SQLite

### เหมาะ

- Local application
- Single-device application
- CLI/data-processing tool
- Prototype
- Test database
- Read-heavy content package
- Edge node ที่ sync ภายหลัง

### ไม่เหมาะหรือควรประเมินอย่างเข้มงวด

- ระบบที่มี concurrent writers จำนวนมาก
- ระบบ multi-region
- ฐานข้อมูลบน shared network filesystem ที่ locking ไม่น่าเชื่อถือ
- ระบบที่ต้องมี role/permission ภายใน database server
- ระบบที่ต้อง scale write throughput แนวนอน
- ระบบที่มี operational team ต้องการ replication/failover ระดับ server

---

## แบบฝึกปฏิบัติ

1. สร้างฐานข้อมูล SQLite สำหรับบันทึก audit findings
2. เปิด foreign keys และเขียน test ที่ยืนยันว่า orphan record ถูกปฏิเสธ
3. เปรียบเทียบ query เดียวกันระหว่าง SQLite และ MySQL
4. เปิด WAL แล้วทดลอง read ขณะมี write transaction
5. สร้าง integration test โดยใช้ `sqlite::memory:` และอธิบายข้อจำกัด
6. ออกแบบ backup/restore checklist

---

## Production Checklist

- [ ] เปิด `PRAGMA foreign_keys = ON`
- [ ] กำหนด `busy_timeout`
- [ ] เลือก journal mode อย่างมีเหตุผล
- [ ] ไม่วางไฟล์บน storage ที่ locking ไม่น่าเชื่อถือ
- [ ] มี backup และ restore test
- [ ] ใช้ constraints และ validation
- [ ] ประเมิน concurrent writer load
- [ ] ไม่ใช้ SQLite test แทน production DB tests ทั้งหมด

---

## References

- SQLite Documentation — https://www.sqlite.org/docs.html
- SQLite Transactions — https://www.sqlite.org/lang_transaction.html
- SQLite Foreign Keys — https://www.sqlite.org/foreignkeys.html
- SQLite WAL — https://www.sqlite.org/wal.html
- PHP Manual: PDO SQLite — https://www.php.net/manual/en/ref.pdo-sqlite.php
