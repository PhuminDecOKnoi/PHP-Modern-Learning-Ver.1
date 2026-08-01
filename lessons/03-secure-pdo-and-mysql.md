# 03 — Secure PDO and MySQL

## บทนำ

บทนี้สอนการเชื่อมต่อ PHP กับ MySQL ผ่าน PDO โดยเน้นความปลอดภัย การจัดการ configuration การใช้ prepared statements และ transactions

## เป้าหมายการเรียนรู้

- สร้าง PDO connection แบบ reusable
- เก็บ credentials ผ่าน environment variables
- ใช้ prepared statements
- ปิด emulated prepares เมื่อเหมาะสม
- ใช้ transactions อย่างถูกต้อง
- จัดการ exception โดยไม่เปิดเผยข้อมูลลับ

## 1. Environment Configuration

ตัวอย่าง environment variables:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=app_db
DB_USER=app_user
DB_PASSWORD=change-me
```

ห้าม commit `.env` จริง และไม่ควร hard-code credentials ใน source code

## 2. Database Factory

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

final class DatabaseFactory
{
    public static function createFromEnvironment(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $database = getenv('DB_NAME') ?: '';
        $username = getenv('DB_USER') ?: '';
        $password = getenv('DB_PASSWORD') ?: '';

        if ($database === '' || $username === '') {
            throw new \RuntimeException('Database configuration is incomplete.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
```

### เหตุผลของ options

| Option | เหตุผล |
|---|---|
| `ERRMODE_EXCEPTION` | จัดการ error ด้วย exception |
| `FETCH_ASSOC` | ผลลัพธ์เป็น associative array |
| `EMULATE_PREPARES=false` | ใช้ native prepared statements เมื่อ driver รองรับ |

## 3. Schema ตัวอย่าง

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

หลักสำคัญ:

- ใช้ `utf8mb4`
- เก็บ password hash ใน `VARCHAR(255)`
- ใช้ unique constraint กับ email เมื่อ business rule กำหนด
- ใช้ InnoDB สำหรับ transactions และ foreign keys

## 4. INSERT ด้วย Prepared Statement

```php
<?php

declare(strict_types=1);

use PDO;

function createUser(PDO $pdo, string $name, string $email, string $password): int
{
    $cleanName = trim($name);
    $cleanEmail = strtolower(trim($email));

    if ($cleanName === '') {
        throw new InvalidArgumentException('Name is required.');
    }

    if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('Email is invalid.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash)
         VALUES (:name, :email, :password_hash)'
    );

    $statement->execute([
        'name' => $cleanName,
        'email' => $cleanEmail,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) $pdo->lastInsertId();
}
```

Prepared statement ป้องกันการนำ input ไปต่อ SQL โดยตรง แต่ validation และ authorization ยังคงต้องทำแยกต่างหาก

## 5. SELECT หนึ่งแถว

```php
<?php

declare(strict_types=1);

use PDO;

function findUserByEmail(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, name, email, password_hash, created_at
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $statement->execute([
        'email' => strtolower(trim($email)),
    ]);

    $user = $statement->fetch();

    return $user === false ? null : $user;
}
```

## 6. Dynamic Queries อย่างปลอดภัย

Placeholder ใช้แทน **values** ไม่ใช่ชื่อ column หรือ SQL keywords

ไม่ถูกต้อง:

```php
$statement = $pdo->prepare('ORDER BY :column');
```

ใช้ allowlist:

```php
<?php

declare(strict_types=1);

$allowedColumns = ['name', 'email', 'created_at'];
$requestedColumn = $_GET['sort'] ?? 'created_at';
$sortColumn = in_array($requestedColumn, $allowedColumns, true)
    ? $requestedColumn
    : 'created_at';

$sql = "SELECT id, name, email FROM users ORDER BY {$sortColumn} DESC";
$rows = $pdo->query($sql)->fetchAll();
```

เพราะชื่อ column มาจาก allowlist ที่ระบบกำหนด ไม่ได้นำ input ไปใช้โดยตรง

## 7. Transactions

```php
<?php

declare(strict_types=1);

use PDO;
use Throwable;

function transferBudget(PDO $pdo, int $fromId, int $toId, float $amount): void
{
    if ($amount <= 0) {
        throw new InvalidArgumentException('Amount must be positive.');
    }

    $pdo->beginTransaction();

    try {
        $debit = $pdo->prepare(
            'UPDATE budgets SET balance = balance - :amount WHERE id = :id'
        );
        $debit->execute(['amount' => $amount, 'id' => $fromId]);

        $credit = $pdo->prepare(
            'UPDATE budgets SET balance = balance + :amount WHERE id = :id'
        );
        $credit->execute(['amount' => $amount, 'id' => $toId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}
```

Transaction เหมาะเมื่อหลาย statement ต้องสำเร็จหรือย้อนกลับร่วมกัน

## 8. Error Handling

```php
<?php

declare(strict_types=1);

try {
    $pdo = DatabaseFactory::createFromEnvironment();
} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);
    echo 'Service temporarily unavailable.';
}
```

Production ไม่ควรแสดง DSN, username, stack trace หรือ SQL detail ต่อผู้ใช้

## 9. Repository Pattern เบื้องต้น

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function existsByEmail(string $email): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => strtolower(trim($email))]);

        return $statement->fetchColumn() !== false;
    }
}
```

Repository แยก database access ออกจาก controller และ business logic

## 10. Checklist

- [ ] credentials มาจาก environment
- [ ] DSN ใช้ `charset=utf8mb4`
- [ ] เปิด exception mode
- [ ] ใช้ prepared statements กับ values
- [ ] ใช้ allowlist กับ identifiers
- [ ] ใช้ transaction เมื่อจำเป็น
- [ ] ไม่แสดง exception detail ใน production
- [ ] database user ใช้สิทธิ์เท่าที่จำเป็น

## แบบฝึกหัด

1. สร้าง `UserRepository::findById()`
2. เพิ่ม transaction สำหรับสร้าง order และ order items
3. ตรวจ duplicate email และจัดการ unique constraint
4. แยก DatabaseFactory ไปไว้ใน `src/Infrastructure/`

## References

- https://www.php.net/manual/en/book.pdo.php
- https://www.php.net/manual/en/ref.pdo-mysql.php
- https://dev.mysql.com/doc/
