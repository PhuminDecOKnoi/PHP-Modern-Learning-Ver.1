# Migration Guide — PHP 8.4–8.5 Course Edition

เอกสารนี้ใช้สำหรับตรวจบทเรียนหรือโปรเจกต์ PHP รุ่นเดิมก่อนย้ายมาใช้ baseline ของ repository

```text
Minimum PHP: 8.4
Primary PHP: 8.5
Testing: PHPUnit 13
```

## 1. เตรียมก่อน Migration

- [ ] สร้าง branch แยก
- [ ] สำรองฐานข้อมูลและไฟล์สำคัญ
- [ ] บันทึก PHP, extension และ dependency versions เดิม
- [ ] รัน test suite เดิมให้ผ่านก่อนเปลี่ยน environment
- [ ] เปิด `E_ALL` ใน development เพื่อค้นหา deprecations
- [ ] ตรวจ compatibility ของ hosting และ PHP extensions

คำสั่งพื้นฐาน:

```bash
php -v
php -m
composer show --direct
composer outdated --direct
```

## 2. ปรับ Composer Requirement

ตัวอย่าง:

```json
{
  "require": {
    "php": ">=8.4 <8.6"
  },
  "require-dev": {
    "phpunit/phpunit": "^13.2"
  }
}
```

จากนั้น:

```bash
composer validate --strict
composer update
composer check-platform-reqs
```

อย่าอัปเดต dependencies จำนวนมากพร้อมกันโดยไม่มี tests และ rollback plan

## 3. Strict Types และ Type Declarations

เพิ่มในไฟล์ใหม่:

```php
<?php

declare(strict_types=1);
```

แก้ implicitly nullable parameter:

```php
// เดิม: ไม่ชัดเจนและอาจมี deprecation
function findUser(string $email = null): void
{
}

// ใหม่
function findUser(?string $email = null): void
{
}
```

ตรวจ parameter types, return types และ property types โดยไม่บังคับ type จนผิดกับ business behavior เดิม

## 4. Deprecated และ Removed Behavior

ค้นหาประเด็นต่อไปนี้:

- syntax หรือ function ที่ deprecated ใน PHP 8.4/8.5
- magic methods `__sleep()` / `__wakeup()` ที่ควรพิจารณา `__serialize()` / `__unserialize()`
- non-canonical casts เช่น `(integer)` หรือ `(boolean)`
- nullable values ที่ส่งไปยัง internal functions โดยไม่ตั้งใจ
- dynamic properties ใน class ที่ไม่ได้ออกแบบรองรับ
- library versions ที่ไม่รองรับ PHP 8.4 หรือ 8.5

รัน application และ tests ด้วย:

```ini
error_reporting=E_ALL
log_errors=On
```

## 5. Database Layer

แทนที่ SQL string concatenation:

```php
// ไม่แนะนำ
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";
```

ด้วย prepared statement:

```php
$statement = $pdo->prepare(
    'SELECT id, email FROM users WHERE email = :email LIMIT 1'
);
$statement->execute([
    'email' => strtolower(trim((string) $_POST['email'])),
]);
```

ตรวจ PDO options:

```php
[
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]
```

ย้าย credentials ไป environment variables และใช้ database account ที่มีสิทธิ์เท่าที่จำเป็น

## 6. JSON API

แทนการตรวจ `json_last_error()` แบบกระจัดกระจายด้วย exception flow:

```php
try {
    $data = json_decode(
        $rawBody,
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    // return 400 response
}
```

ตรวจเพิ่มเติม:

- `Content-Type: application/json`
- status codes
- validation errors
- authorization
- rate limiting
- error response ที่ไม่เปิดเผย stack trace

## 7. Authentication และ Session

- [ ] password เก็บด้วย `password_hash()`
- [ ] login ใช้ `password_verify()`
- [ ] ใช้ `password_needs_rehash()` หลัง login สำเร็จ
- [ ] regenerate session ID หลัง authentication
- [ ] cookie เป็น Secure, HttpOnly และ SameSite
- [ ] เปิด `session.use_strict_mode`
- [ ] เพิ่ม CSRF token
- [ ] เพิ่ม rate limiting
- [ ] reset-password token มีอายุและใช้ได้ครั้งเดียว

ตัวอย่าง session baseline:

```php
session_start([
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
]);
```

## 8. PHPUnit 13

PHPUnit 13 ต้องใช้ PHP 8.4 ขึ้นไป

ติดตั้ง:

```bash
composer require --dev phpunit/phpunit:^13.2
```

Migration strategy:

1. ทำ test suite บน PHPUnit major เดิมให้ผ่าน
2. แก้ deprecation warnings ของรุ่นเดิม
3. อัปเกรดทีละ major เมื่อโปรเจกต์เก่ามาก
4. แยก stub และ mock ตามเจตนา
5. รัน CI บน PHP 8.4 และ 8.5

## 9. ฟีเจอร์ PHP 8.5

ฟีเจอร์ เช่น Pipe Operator และ Clone With ทำให้ไฟล์ parse ไม่ผ่านบน PHP 8.4

แนวทาง:

- ใช้ในไฟล์หรือ module ที่กำหนด PHP 8.5 เท่านั้น
- เก็บตัวอย่าง version-specific แยก directory
- อย่านำ syntax 8.5 ไปไว้ใน executable source ที่ CI ต้องรันบน 8.4

## 10. Production Configuration

- `display_errors=Off`
- `log_errors=On`
- ใช้ HTTPS
- กำหนด file permissions อย่างเหมาะสม
- ไม่เปิดเผย `.env`, logs, backups หรือ source configuration
- ตั้ง health check และ monitoring
- มี rollback plan
- ใช้ PHP patch release ล่าสุดของ branch

## 11. Verification Matrix

| Check | PHP 8.4 | PHP 8.5 |
|---|:---:|:---:|
| Composer install | ☐ | ☐ |
| Syntax lint | ☐ | ☐ |
| PHPUnit | ☐ | ☐ |
| Database integration | ☐ | ☐ |
| Authentication flow | ☐ | ☐ |
| REST API | ☐ | ☐ |
| Production smoke test | ☐ | ☐ |

## 12. Rollback Criteria

หยุด rollout และ rollback เมื่อ:

- error rate เพิ่มขึ้นผิดปกติ
- authentication/session ทำงานไม่ถูกต้อง
- database transaction หรือ data integrity มีปัญหา
- extension สำคัญโหลดไม่ได้
- latency หรือ memory usage เกินเกณฑ์
- test หรือ smoke test ที่สำคัญไม่ผ่าน

## References

- https://www.php.net/manual/en/migration84.php
- https://www.php.net/manual/en/migration85.php
- https://getcomposer.org/doc/
- https://phpunit.de/announcements/phpunit-13.html
