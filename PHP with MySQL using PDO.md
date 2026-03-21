File Name: PHP with MySQL using PDO.md

# PHP with MySQL using PDO

## บทนำ

หัวข้อนี้อธิบายการเชื่อมต่อ **PHP กับ MySQL ด้วย PDO (PHP Data Objects)** ซึ่งเป็นแนวทางที่นิยมในงานปัจจุบัน เพราะทำงานกับฐานข้อมูลได้เป็นระบบ รองรับ **prepared statements**, **transactions**, และการจัดการข้อผิดพลาดได้ดี เหมาะทั้งกับงานเว็บทั่วไป ระบบหลังบ้าน และ API

ในงานจริง แนวทางที่ควรใช้คือ:
- เชื่อมต่อฐานข้อมูลผ่าน `PDO`
- ใช้ `prepared statements` แทนการต่อ SQL string โดยตรง
- เปิดโหมด exception เพื่อจัดการ error ได้ชัดเจน
- ใช้ `password_hash()` และ `password_verify()` สำหรับรหัสผ่าน
- แยก logic การเชื่อมต่อและ query ออกจากหน้าจอหรือ route handler

---

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจว่า PDO คืออะไร และทำไมจึงเหมาะกับงาน modern PHP
- เชื่อมต่อ MySQL ด้วย DSN อย่างถูกต้อง
- ใช้ `prepare()` และ `execute()` อย่างปลอดภัย
- ดึงข้อมูลด้วย `fetch()` และ `fetchAll()`
- ใช้ transaction ด้วย `beginTransaction()`, `commit()`, `rollBack()`
- จัดการ error ด้วย `PDOException`
- ใช้ PDO ร่วมกับการเก็บรหัสผ่านแบบปลอดภัย

---

## หัวข้อย่อยที่ 1: PDO คืออะไร

PDO คือ abstraction layer สำหรับการเข้าถึงฐานข้อมูลใน PHP โดยใช้ API ที่สม่ำเสมอขึ้น ทำให้โค้ดอ่านง่ายและจัดการ query ได้เป็นระบบมากขึ้น

สำหรับงานที่เชื่อมต่อ MySQL แบบ modern PHP, PDO มักเป็นตัวเลือกที่ดีเพราะ:
- รองรับ prepared statements
- จัดการ exception ได้ง่าย
- เขียนโค้ดให้ reusable ได้สะดวก
- ขยายไปสู่โครงสร้าง service / repository ได้ง่าย

```php
<?php

declare(strict_types=1);

use PDO;
use PDOException;
```

สิ่งที่ควรเข้าใจ:
- `PDO` คือ class หลักสำหรับเชื่อมต่อฐานข้อมูล
- `PDOException` ใช้จัดการข้อผิดพลาดที่มาจาก PDO
- ในไฟล์จริงมักแยกส่วนเชื่อมต่อฐานข้อมูลออกจาก business logic

---

## หัวข้อย่อยที่ 2: การเชื่อมต่อ MySQL ด้วย PDO

การเชื่อมต่อฐานข้อมูลด้วย PDO ใช้ **DSN (Data Source Name)** เช่น `mysql:host=127.0.0.1;dbname=app_db;charset=utf8mb4` แล้วส่ง username และ password เข้า constructor

ตัวอย่างฟังก์ชันสำหรับสร้าง connection:

```php
<?php

declare(strict_types=1);

function createPdo(): PDO
{
    $host = '127.0.0.1';
    $dbName = 'app_db';
    $username = 'root';
    $password = 'secret';

    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        // ให้ PDO โยน exception เมื่อเกิดข้อผิดพลาด
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // กำหนดรูปแบบผลลัพธ์เริ่มต้นเป็น associative array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
```

สิ่งที่ควรเข้าใจ:
- `charset=utf8mb4` ควรใส่ใน DSN เพื่อรองรับ Unicode ได้ครบ
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` ช่วยให้ debug และจัดการข้อผิดพลาดง่ายขึ้น
- `PDO::FETCH_ASSOC` ทำให้ผลลัพธ์อ่านง่าย เช่น `$row['email']`

---

## หัวข้อย่อยที่ 3: ทดสอบการเชื่อมต่อแบบง่าย

ตัวอย่างนี้ใช้ `try-catch` เพื่อจับ error ระหว่างเชื่อมต่อ

```php
<?php

declare(strict_types=1);

try {
    $pdo = createPdo();
    echo 'Database connected successfully';
} catch (PDOException $e) {
    // ใน production ไม่ควรแสดงรายละเอียดทั้งหมดออกหน้าจอ
    echo 'Database connection failed';
}
```

สิ่งที่ควรเข้าใจ:
- `PDO::__construct()` อาจ throw exception เมื่อเชื่อมต่อไม่สำเร็จ
- production ควร log รายละเอียดไว้ภายในระบบ แทนการ echo error จริงออกไป
- การแยกฟังก์ชัน `createPdo()` ทำให้ reuse ได้ง่าย

---

## หัวข้อย่อยที่ 4: สร้างตารางตัวอย่างใน MySQL

ก่อนเริ่ม query เรามีตารางตัวอย่างชื่อ `users`

```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

สิ่งที่ควรเข้าใจ:
- `email` มักตั้งเป็น `UNIQUE` เพื่อป้องกันข้อมูลซ้ำ
- ไม่ควรเก็บรหัสผ่านจริงในฐานข้อมูล
- ควรเก็บ hash จาก `password_hash()` แทน

---

## หัวข้อย่อยที่ 5: INSERT ข้อมูลด้วย Prepared Statement

การเพิ่มข้อมูลที่รับมาจากผู้ใช้ควรใช้ `prepare()` และ `execute()` เสมอ เพื่อหลีกเลี่ยงการนำ input ไปต่อกับ SQL โดยตรง

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

$name = 'Phumin';
$email = 'phumin@example.com';
$password = 'MySecurePassword123';

// สร้าง password hash ก่อนบันทึกลงฐานข้อมูล
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = 'INSERT INTO users (name, email, password_hash)
        VALUES (:name, :email, :password_hash)';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':password_hash' => $passwordHash,
]);

echo 'User created successfully';
```

สิ่งที่ควรเข้าใจ:
- ใช้ **named placeholders** เช่น `:name`, `:email`
- `password_hash()` ใช้สำหรับสร้าง hash ที่พร้อมเก็บลงฐานข้อมูล
- อย่าต่อ string SQL ด้วยค่าจากผู้ใช้โดยตรง

---

## หัวข้อย่อยที่ 6: SELECT ข้อมูลแบบ 1 แถวด้วย `fetch()`

เมื่อ query ข้อมูล 1 รายการ เช่น ค้นหาผู้ใช้ตาม email สามารถใช้ `fetch()` ได้

```php
<?php

declare(strict_types=1);

$pdo = createPdo();
$email = 'phumin@example.com';

$sql = 'SELECT id, name, email, password_hash
        FROM users
        WHERE email = :email';

$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);

$user = $stmt->fetch();

if ($user) {
    echo $user['name'];
} else {
    echo 'User not found';
}
```

สิ่งที่ควรเข้าใจ:
- `fetch()` เหมาะกับกรณีคาดว่าจะได้ 1 แถว
- ถ้าไม่พบข้อมูล มักได้ `false`
- เมื่อกำหนด `PDO::FETCH_ASSOC` แล้ว จะเข้าถึงข้อมูลแบบ key-value ได้ง่าย

---

## หัวข้อย่อยที่ 7: SELECT หลายแถวด้วย `fetchAll()`

ถ้าต้องการรายการหลายแถว เช่น รายชื่อผู้ใช้ทั้งหมด สามารถใช้ `fetchAll()` ได้

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

$sql = 'SELECT id, name, email, created_at
        FROM users
        ORDER BY id DESC';

$stmt = $pdo->query($sql);
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo $user['id'] . ' - ' . $user['email'] . PHP_EOL;
}
```

สิ่งที่ควรเข้าใจ:
- `query()` เหมาะกับ SQL ที่ไม่มี user input
- ถ้ามีค่าจากผู้ใช้เข้ามา ควรใช้ `prepare()` แทน
- `fetchAll()` เหมาะเมื่อจำนวนข้อมูลไม่มากเกินไป

---

## หัวข้อย่อยที่ 8: UPDATE และ DELETE แบบปลอดภัย

คำสั่ง `UPDATE` และ `DELETE` ก็ต้องใช้ prepared statements เช่นเดียวกัน

### UPDATE

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

$sql = 'UPDATE users SET name = :name WHERE id = :id';
$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':name' => 'Phumin Decoknoi',
    ':id' => 1,
]);

echo 'User updated';
```

### DELETE

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

$sql = 'DELETE FROM users WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => 1]);

echo 'User deleted';
```

สิ่งที่ควรเข้าใจ:
- แม้เป็น `id` ก็ยังควร bind parameter
- ช่วยลดความเสี่ยงจาก SQL injection
- โค้ดอ่านง่ายและดูแลต่อได้ดีขึ้น

---

## หัวข้อย่อยที่ 9: Login ด้วย `password_verify()`

เมื่อเก็บรหัสผ่านแบบ hash แล้ว เวลาตรวจสอบการ login ต้องใช้ `password_verify()`

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

$email = 'phumin@example.com';
$plainPassword = 'MySecurePassword123';

$sql = 'SELECT id, name, email, password_hash
        FROM users
        WHERE email = :email';

$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);

$user = $stmt->fetch();

if ($user && password_verify($plainPassword, $user['password_hash'])) {
    echo 'Login success';
} else {
    echo 'Invalid credentials';
}
```

สิ่งที่ควรเข้าใจ:
- ห้ามนำรหัสผ่านมาทำ hash ใหม่แล้วเปรียบเทียบแบบตรง ๆ
- `password_verify()` ถูกออกแบบมาสำหรับตรวจสอบ hash โดยตรง
- แนวทางนี้ปลอดภัยกว่าการเก็บ plain text อย่างมาก

---

## หัวข้อย่อยที่ 10: Transactions สำหรับงานที่มีหลายขั้นตอน

ถ้าการทำงานหนึ่งครั้งมีหลาย query ที่ต้องสำเร็จพร้อมกัน เช่น สร้าง order และหัก stock ควรใช้ transaction

```php
<?php

declare(strict_types=1);

$pdo = createPdo();

try {
    // เริ่ม transaction
    $pdo->beginTransaction();

    $insertOrder = $pdo->prepare(
        'INSERT INTO orders (customer_name, total_amount) VALUES (:customer_name, :total_amount)'
    );

    $insertOrder->execute([
        ':customer_name' => 'Phumin',
        ':total_amount' => 2500.00,
    ]);

    $updateStock = $pdo->prepare(
        'UPDATE products SET stock = stock - :qty WHERE id = :product_id'
    );

    $updateStock->execute([
        ':qty' => 1,
        ':product_id' => 10,
    ]);

    // ยืนยันการเปลี่ยนแปลงทั้งหมด
    $pdo->commit();

    echo 'Transaction completed';
} catch (PDOException $e) {
    // หากมีข้อผิดพลาด ให้ย้อนกลับทั้งหมด
    $pdo->rollBack();
    echo 'Transaction failed';
}
```

สิ่งที่ควรเข้าใจ:
- `beginTransaction()` เริ่มชุดคำสั่งที่ต้องสำเร็จพร้อมกัน
- `commit()` ใช้ยืนยัน
- `rollBack()` ใช้ย้อนกลับเมื่อมีปัญหา
- Transaction สำคัญมากในงานด้านการเงิน, stock, และงานที่มีหลายขั้นตอน

---

## หัวข้อย่อยที่ 11: แยก Database Layer ให้ Reusable

ในโปรเจกต์จริงไม่ควรเขียน query กระจายทุกไฟล์ แต่ควรแยก class หรือ function ให้เรียกใช้ซ้ำได้

```php
<?php

declare(strict_types=1);

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): array|false
    {
        $sql = 'SELECT id, name, email, password_hash
                FROM users
                WHERE email = :email';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch();
    }
}
```

สิ่งที่ควรเข้าใจ:
- แยก repository หรือ service ช่วยให้โค้ดเป็นระบบ
- ทดสอบง่ายขึ้น
- เปลี่ยน query หรือโครงสร้างฐานข้อมูลได้สะดวกกว่าเดิม

---

## หัวข้อย่อยที่ 12: Best Practices ที่ควรใช้จริง

แนวทางที่ควรยึดเมื่อใช้ PHP กับ MySQL ผ่าน PDO:

- ใช้ `utf8mb4` ใน DSN เสมอ
- เปิด `PDO::ERRMODE_EXCEPTION`
- ใช้ `prepare()` กับค่าที่มาจากผู้ใช้เสมอ
- ใช้ `password_hash()` และ `password_verify()` สำหรับรหัสผ่าน
- ใช้ transaction เมื่อมีหลาย query ที่ต้องสำเร็จพร้อมกัน
- แยก connection logic และ query logic ออกจาก view หรือ controller
- อย่าแสดงข้อความ error ดิบออกหน้าจอใน production

---

## สรุป

การใช้ **PHP กับ MySQL ผ่าน PDO** เป็นพื้นฐานสำคัญของการพัฒนาเว็บแบบ modern PHP เพราะช่วยให้โค้ดมีโครงสร้างดีขึ้น ปลอดภัยขึ้น และดูแลง่ายขึ้น โดยหัวใจหลักของการใช้งานคือ:
- เชื่อมต่อด้วย DSN ที่ถูกต้อง
- ใช้ prepared statements แทนการต่อ SQL เอง
- จัดการ error ด้วย exception
- ใช้ transactions เมื่อมีหลายขั้นตอน
- เก็บรหัสผ่านด้วย hash อย่างถูกต้อง

เมื่อเข้าใจหัวข้อนี้แล้ว คุณจะพร้อมต่อยอดไปยังเรื่อง:
- CRUD แบบแยกชั้น
- Authentication system
- Repository pattern
- REST API with PHP
- MVC structure

---

## References

- PHP Manual: PDO
- PHP Manual: PDO::__construct
- PHP Manual: PDO::prepare
- PHP Manual: PDOStatement::execute
- PHP Manual: PDO Error Handling
- PHP Manual: PDO Transactions
- PHP Manual: password_hash
- PHP Manual: password_verify
