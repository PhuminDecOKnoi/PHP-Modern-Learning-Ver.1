File Name: Authentication and Password Hashing in PHP.md

# Authentication and Password Hashing in PHP

## บทนำ

หัวข้อนี้คือพื้นฐานสำคัญของการสร้างระบบล็อกอินใน PHP แบบ modern เพราะระบบที่ดีต้องไม่เก็บรหัสผ่านแบบ plain text และต้องตรวจสอบตัวตนผู้ใช้อย่างปลอดภัย แนวทางมาตรฐานใน PHP คือใช้ `password_hash()` สำหรับสร้าง hash, ใช้ `password_verify()` สำหรับตรวจสอบรหัสผ่าน, และใช้ session เพื่อเก็บสถานะการล็อกอินหลังยืนยันตัวตนสำเร็จ

บทเรียนนี้เหมาะสำหรับผู้เริ่มต้นถึงระดับกลางที่ต้องการเข้าใจทั้งภาพรวมของ **authentication flow**, การจัดเก็บรหัสผ่านอย่างปลอดภัย, การตรวจสอบการเข้าสู่ระบบ, และแนวทางที่ควรใช้จริงในโปรเจกต์ปัจจุบัน

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจความต่างระหว่าง plain password กับ password hash
- ใช้ `password_hash()` และ `password_verify()` ได้อย่างถูกต้อง
- ใช้ `password_needs_rehash()` เพื่ออัปเกรด hash ในอนาคต
- สร้าง flow สมัครสมาชิกและล็อกอินแบบพื้นฐานได้
- ใช้ session เพื่อเก็บสถานะผู้ใช้หลัง login
- เข้าใจ security basics ที่ควรใช้กับระบบ auth

---

## หัวข้อย่อยที่ 1: Authentication คืออะไร

Authentication คือกระบวนการยืนยันว่า “ผู้ใช้คนนี้เป็นใคร” โดยในระบบพื้นฐาน ผู้ใช้จะกรอก `email` หรือ `username` พร้อม `password` จากนั้นระบบจะตรวจสอบข้อมูลกับฐานข้อมูล ถ้าตรงกันจึงอนุญาตให้เข้าใช้งาน

แนวคิดสำคัญคือ **ไม่ควรเก็บรหัสผ่านจริงของผู้ใช้ลงฐานข้อมูล** แต่ควรเก็บเป็น hash ที่ย้อนกลับไม่ได้ เพื่อให้แม้ข้อมูลรั่วไหล ความเสี่ยงก็ยังต่ำกว่าการเก็บรหัสผ่านแบบตรง ๆ

```php
<?php

declare(strict_types=1);

// ตัวอย่างข้อมูลรับเข้าจากฟอร์ม
$email = 'user@example.com';
$password = 'MySecurePassword123!';

// ในระบบจริง จะนำข้อมูลนี้ไปตรวจสอบกับฐานข้อมูล
if ($email !== '' && $password !== '') {
    echo 'Ready to authenticate';
}
```

สิ่งที่ควรเข้าใจ:
- Authentication คือการยืนยันตัวตน
- Password ไม่ควรถูกเก็บแบบ plain text
- ระบบ login ที่ดีต้องมีทั้งการตรวจสอบข้อมูลและการจัดการ session

---

## หัวข้อย่อยที่ 2: ทำไมต้องใช้ Password Hashing

การ hash คือการแปลงรหัสผ่านให้เป็นข้อความอีกแบบที่ไม่สามารถย้อนกลับเป็นค่าดั้งเดิมได้โดยตรง ใน PHP ควรใช้ `password_hash()` แทนการใช้ `md5()` หรือ `sha1()` สำหรับรหัสผ่าน เพราะฟังก์ชัน `password_*` ถูกออกแบบมาสำหรับงาน password โดยเฉพาะ

อีกจุดสำคัญคือ hash ที่ได้จาก `password_hash()` จะบันทึกข้อมูลที่จำเป็นสำหรับการตรวจสอบไว้ใน hash เอง ทำให้เวลา verify ไม่ต้องเก็บ salt แยกต่างหาก

```php
<?php

declare(strict_types=1);

$password = 'MySecurePassword123!';

// สร้าง hash จากรหัสผ่าน
$hash = password_hash($password, PASSWORD_DEFAULT);

echo $hash;
```

สิ่งที่ควรเข้าใจ:
- เก็บ hash แทนรหัสผ่านจริง
- ไม่ต้องเขียนระบบ salt เองเมื่อใช้ `password_hash()`
- `PASSWORD_DEFAULT` คือจุดเริ่มต้นที่เหมาะกับงานทั่วไป

---

## หัวข้อย่อยที่ 3: สมัครสมาชิกและบันทึกรหัสผ่านอย่างปลอดภัย

ตอนสมัครสมาชิก ระบบควรรับรหัสผ่านจากผู้ใช้, ตรวจสอบความเหมาะสมเบื้องต้น, จากนั้นสร้าง hash แล้วค่อยบันทึกลงฐานข้อมูล ไม่ควรบันทึกรหัสผ่านดิบแม้เพียงชั่วคราวเกินความจำเป็น

ตัวอย่างนี้แสดงเฉพาะ logic ฝั่ง PHP เพื่อให้เห็นแนวคิดหลักก่อนเชื่อมกับฐานข้อมูลจริง

```php
<?php

declare(strict_types=1);

$email = 'user@example.com';
$password = 'MySecurePassword123!';

// ตรวจสอบข้อมูลเบื้องต้น
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Invalid email');
}

if (strlen($password) < 12) {
    exit('Password must be at least 12 characters');
}

// สร้าง password hash ก่อนบันทึกลงฐานข้อมูล
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// ตัวอย่างข้อมูลที่จะนำไปบันทึก
$userData = [
    'email' => $email,
    'password_hash' => $passwordHash,
];

print_r($userData);
```

สิ่งที่ควรเข้าใจ:
- ตรวจสอบ input ก่อนเก็บข้อมูล
- เก็บ `password_hash` แทน `password`
- ควรกำหนดนโยบายรหัสผ่านให้เหมาะกับระบบ

---

## หัวข้อย่อยที่ 4: ตรวจสอบรหัสผ่านด้วย `password_verify()`

เมื่อผู้ใช้ล็อกอิน ระบบต้องดึง hash จากฐานข้อมูล แล้วใช้ `password_verify()` ตรวจสอบว่ารหัสผ่านที่ผู้ใช้กรอกตรงกับ hash หรือไม่ ไม่ควรเอารหัสผ่านที่กรอกมา hash ใหม่แล้วเทียบตรง ๆ ด้วยตัวเอง

```php
<?php

declare(strict_types=1);

// สมมติว่า hash นี้ดึงมาจากฐานข้อมูล
$storedHash = password_hash('MySecurePassword123!', PASSWORD_DEFAULT);

// รหัสผ่านที่ผู้ใช้กรอกจากฟอร์ม login
$inputPassword = 'MySecurePassword123!';

if (password_verify($inputPassword, $storedHash)) {
    echo 'Login success';
} else {
    echo 'Invalid credentials';
}
```

สิ่งที่ควรเข้าใจ:
- ใช้ `password_verify()` เพื่อตรวจสอบรหัสผ่าน
- ห้ามเปรียบเทียบ password ด้วย `==` หรือ `===` กับ hash โดยตรง
- ถ้า verify ไม่ผ่าน ควรตอบข้อความแบบกลาง ๆ เช่น `Invalid credentials`

---

## หัวข้อย่อยที่ 5: อัปเกรด Hash ด้วย `password_needs_rehash()`

เมื่อเวลาผ่านไป algorithm หรือ options ที่เหมาะสมอาจเปลี่ยนได้ PHP จึงมี `password_needs_rehash()` สำหรับตรวจสอบว่า hash เดิมควรถูกสร้างใหม่หรือไม่ แนวคิดนี้ช่วยให้ระบบค่อย ๆ อัปเกรดความปลอดภัยได้โดยไม่บังคับให้ผู้ใช้เปลี่ยนรหัสผ่านทันที

มักใช้หลังจาก `password_verify()` สำเร็จแล้ว

```php
<?php

declare(strict_types=1);

$password = 'MySecurePassword123!';
$storedHash = password_hash($password, PASSWORD_DEFAULT);

if (password_verify($password, $storedHash)) {
    // ถ้า hash เก่าไม่ตรงกับมาตรฐานปัจจุบัน ให้สร้างใหม่
    if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        // ในระบบจริง ควรอัปเดต $newHash กลับลงฐานข้อมูล
        echo 'Hash was refreshed';
    } else {
        echo 'Hash is still OK';
    }
}
```

สิ่งที่ควรเข้าใจ:
- ใช้ rehash หลัง login สำเร็จ
- ช่วยให้ระบบอัปเดต hash ได้แบบค่อยเป็นค่อยไป
- ทำให้ระบบพร้อมรับการเปลี่ยนแปลงของ algorithm ในอนาคต

---

## หัวข้อย่อยที่ 6: ใช้ Session เพื่อเก็บสถานะการล็อกอิน

หลังจากยืนยันตัวตนสำเร็จแล้ว ระบบมักใช้ session เพื่อเก็บสถานะว่า “ผู้ใช้นี้ล็อกอินแล้ว” โดยต้องเรียก `session_start()` ก่อนใช้งาน `$_SESSION`

แนวทางพื้นฐานคือเก็บเฉพาะข้อมูลจำเป็น เช่น `user_id`, `email`, `role` และไม่ควรเก็บข้อมูลลับที่ไม่จำเป็นลงใน session

```php
<?php

declare(strict_types=1);

session_start();

// สมมติว่าล็อกอินสำเร็จแล้ว
$_SESSION['user_id'] = 101;
$_SESSION['email'] = 'user@example.com';
$_SESSION['is_authenticated'] = true;

echo 'User logged in';
```

ตัวอย่างตรวจสอบหน้า protected page:

```php
<?php

declare(strict_types=1);

session_start();

if (!($_SESSION['is_authenticated'] ?? false)) {
    http_response_code(401);
    exit('Unauthorized');
}

echo 'Welcome to dashboard';
```

สิ่งที่ควรเข้าใจ:
- เรียก `session_start()` ก่อนใช้ session
- ใช้ session เก็บสถานะหลัง login
- ตรวจสอบ session ทุกครั้งก่อนให้เข้าหน้าสำคัญ

---

## หัวข้อย่อยที่ 7: ตัวอย่าง Login Flow แบบง่าย

ตัวอย่างนี้รวมขั้นตอนหลักของระบบ login แบบพื้นฐานเข้าด้วยกัน ได้แก่ รับค่าจากฟอร์ม, ค้นหาผู้ใช้, ตรวจสอบรหัสผ่าน, แล้วค่อยสร้าง session

```php
<?php

declare(strict_types=1);

session_start();

// จำลองข้อมูลผู้ใช้จากฐานข้อมูล
$user = [
    'id' => 1,
    'email' => 'user@example.com',
    'password_hash' => password_hash('MySecurePassword123!', PASSWORD_DEFAULT),
];

// ข้อมูลจากฟอร์ม login
$inputEmail = 'user@example.com';
$inputPassword = 'MySecurePassword123!';

// ตรวจสอบ email ก่อน
if ($inputEmail !== $user['email']) {
    exit('Invalid credentials');
}

// ตรวจสอบ password กับ hash
if (!password_verify($inputPassword, $user['password_hash'])) {
    exit('Invalid credentials');
}

// สร้าง session หลัง login สำเร็จ
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['is_authenticated'] = true;

echo 'Login successful';
```

สิ่งที่ควรเข้าใจ:
- ตรวจสอบข้อมูลทีละขั้น
- อย่าเปิดเผยว่าผิดที่ email หรือ password
- ค่อยสร้าง session หลังจาก verify สำเร็จแล้วเท่านั้น

---

## หัวข้อย่อยที่ 8: Logout อย่างถูกต้อง

การ logout ไม่ใช่แค่ redirect ออกไป แต่ควรล้างข้อมูล session ที่เกี่ยวข้องด้วย เพื่อให้สถานะการล็อกอินสิ้นสุดจริง

```php
<?php

declare(strict_types=1);

session_start();

// ล้างค่าทั้งหมดใน session
$_SESSION = [];

// ทำลาย session ปัจจุบัน
session_destroy();

echo 'Logged out';
```

สิ่งที่ควรเข้าใจ:
- logout ควรล้าง session
- ไม่ควรเหลือข้อมูล auth ค้างไว้
- ถ้าระบบเข้มงวดขึ้น อาจล้าง cookie ของ session เพิ่มด้วย

---

## หัวข้อย่อยที่ 9: Security Basics ที่ควรใช้จริง

ระบบ auth ที่ดีควรมีมากกว่า password hashing อย่างน้อยควรคำนึงถึงเรื่องต่อไปนี้:

### 1) Validate และ sanitize input
ตรวจสอบ `email`, `password` และข้อมูลรับเข้าจากฟอร์มทุกครั้ง

### 2) ใช้ HTTPS ในระบบจริง
เพื่อป้องกันข้อมูล login ถูกดักระหว่างทาง

### 3) อย่าเก็บ password เดิมไว้ที่ใดเลย
แม้ใน log หรือ debug output

### 4) ใช้ข้อความ error แบบกลาง
เช่น `Invalid credentials` แทนการบอกว่า “ไม่พบ email” หรือ “password ไม่ถูกต้อง”

### 5) ควรพิจารณา regenerate session ID หลัง login สำเร็จ
เพื่อลดความเสี่ยงด้าน session fixation

```php
<?php

declare(strict_types=1);

session_start();

// หลัง login สำเร็จ ควรเปลี่ยน session id
session_regenerate_id(true);

$_SESSION['user_id'] = 1;
$_SESSION['is_authenticated'] = true;

echo 'Secure login session created';
```

สิ่งที่ควรเข้าใจ:
- Password hashing เป็นแค่ส่วนหนึ่งของ auth security
- Session security ก็สำคัญไม่แพ้กัน
- ระบบจริงควรมี rate limiting และ CSRF protection เพิ่มเติมในจุดที่เกี่ยวข้อง

---

## หัวข้อย่อยที่ 10: ตัวอย่างเชื่อมกับ PDO แบบสั้น

ในงานจริง ระบบมักดึงผู้ใช้จากฐานข้อมูลด้วย PDO แล้วค่อยตรวจสอบรหัสผ่าน ตัวอย่างนี้เป็นแนวทางแบบย่อเพื่อให้เห็น flow หลัก

```php
<?php

declare(strict_types=1);

// สมมติว่าเชื่อมต่อฐานข้อมูลแล้วด้วย PDO
// $pdo = new PDO(...);

$email = 'user@example.com';
$password = 'MySecurePassword123!';

// ตัวอย่าง query แบบ prepared statement
$sql = 'SELECT id, email, password_hash FROM users WHERE email = :email LIMIT 1';
$stmt = $pdo->prepare($sql);
$stmt->execute(['email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit('Invalid credentials');
}

if (!password_verify($password, $user['password_hash'])) {
    exit('Invalid credentials');
}

session_start();
session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['is_authenticated'] = true;

echo 'Login successful';
```

สิ่งที่ควรเข้าใจ:
- ใช้ prepared statements กับฐานข้อมูล
- ดึง hash ออกมาแล้ว verify ด้วย `password_verify()`
- สร้าง session หลังตรวจสอบสำเร็จ

---

## Best Practices สำหรับงานจริง

- ใช้ `password_hash($password, PASSWORD_DEFAULT)` เป็นค่าเริ่มต้น
- ใช้ `password_verify()` ทุกครั้งที่ตรวจสอบรหัสผ่าน
- ใช้ `password_needs_rehash()` หลัง login สำเร็จเมื่อเหมาะสม
- อย่าเก็บ password จริงลงฐานข้อมูล, log หรือ debug output
- ใช้ `session_start()` และพิจารณา `session_regenerate_id(true)` หลัง login
- ใช้ prepared statements เมื่อติดต่อฐานข้อมูล
- แยก logic ของ register, login, logout และ authorization ให้ชัดเจน
- เพิ่ม rate limiting, CSRF protection และ HTTPS ในระบบจริง

---

## สรุป

หัวข้อ **Authentication and Password Hashing in PHP** เป็นพื้นฐานสำคัญของงานพัฒนาเว็บ เพราะเกือบทุกระบบต้องมีการยืนยันตัวตนผู้ใช้ หากคุณเข้าใจการสร้าง hash, การ verify password, การ rehash และการจัดการ session อย่างถูกต้อง คุณจะสามารถสร้างระบบ login ที่ปลอดภัยขึ้นและพร้อมต่อยอดไปยังหัวข้อที่ซับซ้อนกว่า เช่น role-based access control, remember me, email verification และ multi-factor authentication ได้ง่ายขึ้น

## References

- PHP Manual: Password Hashing Functions
- PHP Manual: `password_hash()`
- PHP Manual: `password_verify()`
- PHP Manual: `password_needs_rehash()`
- PHP Manual: Sessions / `session_start()`
