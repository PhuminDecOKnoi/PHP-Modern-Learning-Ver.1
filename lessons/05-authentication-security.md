# 05 — Authentication Security

## บทนำ

บทนี้อธิบาย authentication flow ที่ปลอดภัยสำหรับ PHP 8.4–8.5 ตั้งแต่ password hashing, login verification, session hardening, CSRF protection และ rate limiting

## เป้าหมายการเรียนรู้

- เก็บ password ด้วย `password_hash()`
- ตรวจ password ด้วย `password_verify()`
- อัปเกรด hash ด้วย `password_needs_rehash()`
- ตั้งค่า session cookie อย่างปลอดภัย
- ป้องกัน session fixation
- ใช้ CSRF token
- ออกแบบข้อความ error ที่ไม่เปิดเผยข้อมูลผู้ใช้

## 1. Password Hashing

```php
<?php

declare(strict_types=1);

$password = 'A-strong-user-password';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

if ($passwordHash === false) {
    throw new RuntimeException('Unable to hash password.');
}
```

ข้อควรจำ:

- ห้ามเก็บ plain-text password
- ห้ามใช้ `md5()` หรือ `sha1()` สำหรับ password
- ไม่ต้องสร้าง salt เองเมื่อใช้ password API ของ PHP
- column ควรรองรับ hash ที่ยาวขึ้นในอนาคต เช่น `VARCHAR(255)`

## 2. Registration Flow

```php
<?php

declare(strict_types=1);

function validateRegistration(string $email, string $password): array
{
    $errors = [];
    $cleanEmail = strtolower(trim($email));

    if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'][] = 'Email format is invalid.';
    }

    if (mb_strlen($password) < 12) {
        $errors['password'][] = 'Password must contain at least 12 characters.';
    }

    return $errors;
}
```

นโยบาย password ควรเน้นความยาวและป้องกัน password ที่รั่วไหลหรือเดาง่าย มากกว่าบังคับรูปแบบซับซ้อนจนผู้ใช้จำไม่ได้

## 3. Login Verification

```php
<?php

declare(strict_types=1);

function verifyLogin(string $password, array $user): bool
{
    $hash = (string) ($user['password_hash'] ?? '');

    if ($hash === '') {
        return false;
    }

    return password_verify($password, $hash);
}
```

ข้อความตอบกลับกรณี email ไม่พบและ password ผิดควรเหมือนกัน:

```text
Invalid email or password.
```

ไม่ควรบอกว่า account ใดมีอยู่ในระบบ เพราะเพิ่มความเสี่ยงของ account enumeration

## 4. Rehash หลัง Login สำเร็จ

```php
<?php

declare(strict_types=1);

if (password_verify($inputPassword, $user['password_hash'])) {
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);

        if ($newHash === false) {
            throw new RuntimeException('Unable to refresh password hash.');
        }

        // ใช้ prepared statement อัปเดต hash ในฐานข้อมูล
    }
}
```

วิธีนี้ช่วยให้ระบบอัปเกรด algorithm หรือ cost factor แบบค่อยเป็นค่อยไป

## 5. Secure Session Start

```php
<?php

declare(strict_types=1);

session_start([
    'use_strict_mode' => true,
    'use_only_cookies' => true,
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
]);
```

### ความหมาย

| Setting | ประโยชน์ |
|---|---|
| `use_strict_mode` | ปฏิเสธ session ID ที่ระบบไม่ได้สร้าง |
| `use_only_cookies` | ไม่รับ session ID ผ่าน URL |
| `cookie_httponly` | JavaScript อ่าน cookie ไม่ได้ |
| `cookie_secure` | ส่ง cookie ผ่าน HTTPS เท่านั้น |
| `cookie_samesite` | ลดความเสี่ยง CSRF บางส่วน |

`cookie_secure=true` ต้องใช้ HTTPS ใน environment จริง

## 6. ป้องกัน Session Fixation

หลัง login สำเร็จ:

```php
<?php

declare(strict_types=1);

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['authenticated_at'] = time();
```

เก็บเฉพาะข้อมูลจำเป็น ไม่ควรเก็บ password, access token ที่ไม่จำเป็น หรือข้อมูลส่วนบุคคลจำนวนมากใน session

## 7. Authorization

Authentication ตอบคำถามว่า “ผู้ใช้คือใคร” ส่วน authorization ตอบว่า “ผู้ใช้นั้นทำอะไรได้”

```php
<?php

declare(strict_types=1);

function requireRole(string $requiredRole): void
{
    $currentRole = $_SESSION['role'] ?? null;

    if ($currentRole !== $requiredRole) {
        http_response_code(403);
        exit('Forbidden');
    }
}
```

ระบบจริงอาจใช้ permissions หรือ policy objects แทนการเทียบ role เพียงค่าเดียว

## 8. CSRF Token

สร้าง token:

```php
<?php

declare(strict_types=1);

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}
```

แสดงใน form:

```php
<input
    type="hidden"
    name="csrf_token"
    value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>"
>
```

ตรวจ token:

```php
<?php

declare(strict_types=1);

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    http_response_code(419);
    exit('Invalid CSRF token.');
}
```

SameSite cookie ช่วยลดความเสี่ยง แต่ไม่ควรใช้แทน CSRF token สำหรับคำขอที่เปลี่ยนแปลงข้อมูล

## 9. Logout

```php
<?php

declare(strict_types=1);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parameters = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $parameters['path'],
        'domain' => $parameters['domain'],
        'secure' => $parameters['secure'],
        'httponly' => $parameters['httponly'],
        'samesite' => $parameters['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
```

## 10. Rate Limiting และ Lockout

Login endpoint ควรจำกัดจำนวนความพยายามตาม IP, account identifier และช่วงเวลา แต่ต้องระวังไม่ออกแบบ lockout ที่ attacker ใช้ปิดกั้น account ของผู้อื่นได้ง่าย

แนวทาง:

- เพิ่ม delay หรือ progressive backoff
- จำกัดต่อ IP และ account แบบสมดุล
- log เหตุการณ์ผิดปกติ
- ใช้ CAPTCHA หรือ step-up verification เมื่อความเสี่ยงสูง
- ส่ง `429 Too Many Requests` เมื่อเกิน limit

## 11. Session Timeout

```php
<?php

declare(strict_types=1);

$maxIdleSeconds = 1800;
$lastActivity = (int) ($_SESSION['last_activity'] ?? time());

if (time() - $lastActivity > $maxIdleSeconds) {
    $_SESSION = [];
    session_destroy();

    http_response_code(401);
    exit('Session expired.');
}

$_SESSION['last_activity'] = time();
```

ระบบความเสี่ยงสูงอาจต้องมีทั้ง idle timeout และ absolute timeout

## 12. Security Checklist

- [ ] ใช้ HTTPS
- [ ] ใช้ `password_hash()` และ `password_verify()`
- [ ] ใช้ generic login error
- [ ] regenerate session ID หลัง login
- [ ] session cookie เป็น Secure, HttpOnly และ SameSite
- [ ] มี CSRF token
- [ ] มี rate limiting
- [ ] ใช้ prepared statements
- [ ] ไม่ log password หรือ session ID
- [ ] authorization ตรวจทุก protected action
- [ ] reset password token มีอายุและใช้ได้ครั้งเดียว

## แบบฝึกหัด

1. สร้าง registration service ที่ตรวจ duplicate email
2. เพิ่ม rehash หลัง login
3. สร้าง middleware ตรวจ session timeout
4. เขียน test ให้ `validateRegistration()`
5. อธิบายความต่างระหว่าง authentication, authorization และ CSRF

## References

- https://www.php.net/manual/en/ref.password.php
- https://www.php.net/manual/en/book.session.php
- https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html
- https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
- https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
