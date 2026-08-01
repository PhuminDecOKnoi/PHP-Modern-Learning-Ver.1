# 07 — Database Drivers and Portable Data Access

## บทนำ

การใช้ PDO ไม่ได้ทำให้ SQL ทุกฐานข้อมูลเหมือนกันทั้งหมด แต่ทำให้ **รูปแบบการเชื่อมต่อ การเตรียมคำสั่ง การส่ง parameter และการจัดการ transaction มี API ที่สม่ำเสมอขึ้น** ผู้พัฒนาระดับมืออาชีพจึงต้องแยกให้ออกระหว่าง:

1. **Portable application code** — โค้ดที่ไม่ผูกกับฐานข้อมูลใดมากเกินไป
2. **Vendor-specific SQL** — ความสามารถเฉพาะของ MySQL, PostgreSQL, SQLite หรือระบบอื่น
3. **Data access boundary** — จุดที่แยก business logic ออกจากรายละเอียดการจัดเก็บข้อมูล

บทนี้วางรากฐานสำหรับการออกแบบระบบที่เปลี่ยนฐานข้อมูลได้ง่ายขึ้น ทดสอบได้ และไม่กระจาย SQL ไปทั่วทั้ง application

---

## ผลลัพธ์การเรียนรู้

เมื่อเรียนจบบทนี้ ผู้เรียนควรสามารถ:

- ตรวจสอบ PDO drivers ที่ติดตั้งอยู่ได้
- แยกข้อแตกต่างระหว่าง PDO API กับ SQL dialect
- ออกแบบ connection factory ที่ปลอดภัย
- ใช้ Repository pattern เพื่อลดการผูก business logic กับฐานข้อมูล
- จัดการ transaction boundary อย่างถูกต้อง
- ออกแบบ migration strategy และ integration tests
- ตัดสินใจได้ว่าเมื่อใดควรใช้ abstraction และเมื่อใดควรใช้ความสามารถเฉพาะฐานข้อมูล

---

## 1. PDO Driver คืออะไร

PDO เป็น interface กลางสำหรับเข้าถึงฐานข้อมูล แต่ต้องมี driver ที่ตรงกับฐานข้อมูลปลายทาง เช่น:

| Driver | ฐานข้อมูล |
|---|---|
| `pdo_mysql` | MySQL / MariaDB |
| `pdo_pgsql` | PostgreSQL |
| `pdo_sqlite` | SQLite |
| `pdo_sqlsrv` | Microsoft SQL Server |
| `pdo_oci` | Oracle |
| `pdo_odbc` | ฐานข้อมูลที่มี ODBC driver |

ตรวจสอบ drivers ที่พร้อมใช้งาน:

```php
<?php

declare(strict_types=1);

print_r(PDO::getAvailableDrivers());
```

ตรวจสอบ extension จาก CLI:

```bash
php -m
php --ri pdo_mysql
php --ri pdo_pgsql
php --ri pdo_sqlite
```

> การมี `ext-pdo` อย่างเดียวไม่เพียงพอ ต้องมี driver ของฐานข้อมูลที่ต้องใช้ด้วย

---

## 2. Connection Factory แบบชัดเจน

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use RuntimeException;

final class PdoFactory
{
    /**
     * @param array{
     *   dsn: string,
     *   username?: string,
     *   password?: string
     * } $config
     */
    public static function create(array $config): PDO
    {
        $dsn = $config['dsn'] ?? '';

        if ($dsn === '') {
            throw new RuntimeException('Database DSN is required.');
        }

        return new PDO(
            $dsn,
            $config['username'] ?? null,
            $config['password'] ?? null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        );
    }
}
```

ตัวอย่าง DSN:

```text
mysql:host=127.0.0.1;port=3306;dbname=app;charset=utf8mb4
pgsql:host=127.0.0.1;port=5432;dbname=app
sqlite:/absolute/path/to/app.sqlite
sqlite::memory:
```

### หลักปฏิบัติ

- เก็บ DSN และ credentials ใน environment variables
- ห้าม commit password หรือ connection string ลง repository
- ตั้ง timeout ตาม driver และ deployment environment
- ทดสอบ connection failure path เสมอ
- ใช้บัญชีฐานข้อมูลตามหลัก least privilege

---

## 3. PDO ไม่ได้แปลง SQL Dialect

SQL ต่อไปนี้อาจแตกต่างกันตามฐานข้อมูล:

- Auto-increment key
- Upsert
- Returning inserted rows
- JSON operators
- Full-text search
- Date/time functions
- Identifier quoting
- Limit/offset behavior
- Generated columns
- Index types

ตัวอย่างการคืนค่า ID:

```php
// MySQL มักใช้ lastInsertId()
$id = (int) $pdo->lastInsertId();
```

```sql
-- PostgreSQL สามารถใช้ RETURNING
INSERT INTO users (email)
VALUES (:email)
RETURNING id;
```

ดังนั้น portability ที่แท้จริงต้องเกิดจาก **การควบคุมขอบเขต SQL** ไม่ใช่การสมมติว่า query เดียวจะใช้ได้ทุกระบบ

---

## 4. Repository Pattern

Repository ทำหน้าที่เป็น boundary ระหว่าง domain/application กับ persistence

```php
<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}
```

PDO implementation:

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\User\User;
use App\Domain\User\UserRepository;
use PDO;

final readonly class PdoUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email FROM users WHERE id = :id',
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return $row === false
            ? null
            : new User((int) $row['id'], (string) $row['email']);
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email FROM users WHERE email = :email',
        );
        $statement->execute(['email' => $email]);

        $row = $statement->fetch();

        return $row === false
            ? null
            : new User((int) $row['id'], (string) $row['email']);
    }

    public function save(User $user): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (email) VALUES (:email)',
        );
        $statement->execute(['email' => $user->email]);
    }
}
```

### ประโยชน์

- Business logic ไม่รู้จัก SQL
- เปลี่ยน implementation สำหรับ test ได้
- จำกัดจุดที่ต้องแก้เมื่อ schema เปลี่ยน
- ตรวจสอบ query และ performance ได้ง่ายขึ้น

### ข้อควรระวัง

Repository ไม่ควรกลายเป็น class ขนาดใหญ่ที่มี query ทุกอย่าง ควรแบ่งตาม aggregate หรือ use case ที่มีขอบเขตชัดเจน

---

## 5. Transaction Boundary

Transaction ควรครอบคลุม business operation หนึ่งชุดที่ต้องสำเร็จหรือยกเลิกพร้อมกัน

```php
<?php

declare(strict_types=1);

function transferCredit(PDO $pdo, int $fromId, int $toId, int $amount): void
{
    $pdo->beginTransaction();

    try {
        $debit = $pdo->prepare(
            'UPDATE wallets SET balance = balance - :amount
             WHERE id = :id AND balance >= :amount',
        );
        $debit->execute(['amount' => $amount, 'id' => $fromId]);

        if ($debit->rowCount() !== 1) {
            throw new RuntimeException('Insufficient balance or wallet not found.');
        }

        $credit = $pdo->prepare(
            'UPDATE wallets SET balance = balance + :amount WHERE id = :id',
        );
        $credit->execute(['amount' => $amount, 'id' => $toId]);

        if ($credit->rowCount() !== 1) {
            throw new RuntimeException('Target wallet not found.');
        }

        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $error;
    }
}
```

### Professional rules

- อย่าเปิด transaction แล้วรอ network call ภายนอกนาน ๆ
- อย่า catch exception แล้ว commit ต่อ
- เตรียมรับ deadlock และ serialization failure ด้วย retry policy ที่มีขอบเขต
- อย่า retry ทุก error โดยอัตโนมัติ
- กำหนด idempotency สำหรับ operation ที่อาจถูกส่งซ้ำ

---

## 6. Migration Strategy

Schema migration ควรมี:

- หมายเลขหรือลำดับที่แน่นอน
- Up migration
- Down migration เมื่อปลอดภัย
- Review ก่อน production
- Backup หรือ recovery plan
- Compatibility ระหว่าง application version เก่าและใหม่

แนวทาง deployment ที่ปลอดภัย:

1. เพิ่ม column ใหม่แบบ nullable หรือมี default ที่ปลอดภัย
2. Deploy application ที่อ่านได้ทั้ง schema เก่าและใหม่
3. Backfill ข้อมูล
4. บังคับ constraint เมื่อข้อมูลพร้อม
5. ลบโค้ดหรือ column เก่าในรอบถัดไป

รูปแบบนี้เรียกว่า **expand-and-contract migration**

---

## 7. Testing Strategy

| ระดับ | เป้าหมาย |
|---|---|
| Unit test | ทดสอบ business logic โดยใช้ fake repository |
| Integration test | ทดสอบ SQL กับฐานข้อมูลจริง |
| Migration test | ทดสอบ schema จากฐานว่างและฐานข้อมูลรุ่นก่อน |
| Contract test | ยืนยันว่า repository implementations ให้พฤติกรรมเดียวกัน |
| Load test | ตรวจ query latency, connection pool และ lock contention |

SQLite in-memory test มีประโยชน์ แต่ไม่สามารถแทน MySQL/PostgreSQL integration test ได้ทั้งหมด เพราะ SQL dialect และ concurrency semantics ต่างกัน

---

## 8. Decision Matrix

| สถานการณ์ | แนวทาง |
|---|---|
| CRUD ทั่วไป | ใช้ PDO + Repository |
| Query ซับซ้อนเฉพาะ PostgreSQL | ใช้ SQL เฉพาะฐานข้อมูลอย่างชัดเจน |
| ต้องรองรับหลายฐานข้อมูลจริง | สร้าง contract tests แยกแต่ละ driver |
| ต้องเปลี่ยนฐานข้อมูลในอนาคตแบบไม่แน่นอน | อย่า over-engineer; จำกัด SQL ไว้ใน infrastructure layer |
| Analytics ขนาดใหญ่ | พิจารณา datastore แยกจาก transactional database |

---

## แบบฝึกปฏิบัติ

1. สร้าง `UserRepository` interface และ implementation สำหรับ MySQL กับ SQLite
2. เขียน contract test ชุดเดียวให้รันกับทั้งสอง implementation
3. ทดลอง query ที่ใช้ upsert แล้วบันทึกความแตกต่างของแต่ละฐานข้อมูล
4. ออกแบบ migration เพิ่ม `status` ให้ตาราง `users` โดยใช้ expand-and-contract
5. วิเคราะห์ว่า transaction ใดในระบบ HR ควรเป็น atomic operation

---

## Checklist ก่อน Production

- [ ] ใช้ prepared statements กับข้อมูลภายนอก
- [ ] Credentials ไม่อยู่ใน source code
- [ ] Database user มีสิทธิ์เท่าที่จำเป็น
- [ ] Transaction สั้นและมี rollback path
- [ ] มี indexes รองรับ query หลัก
- [ ] มี integration tests กับ database จริง
- [ ] Migration มี backup/recovery plan
- [ ] Log ไม่บันทึก password, token หรือข้อมูลส่วนบุคคลเกินจำเป็น
- [ ] มี timeout และ retry policy ที่จำแนกชนิด error

---

## References

- PHP Manual: PDO — https://www.php.net/manual/en/book.pdo.php
- PHP Manual: PDO Drivers — https://www.php.net/manual/en/pdo.drivers.php
- PostgreSQL Documentation — https://www.postgresql.org/docs/current/
- SQLite Documentation — https://www.sqlite.org/docs.html
- MySQL Documentation — https://dev.mysql.com/doc/
