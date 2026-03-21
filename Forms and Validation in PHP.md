File Name: Forms and Validation in PHP.md

# Forms and Validation in PHP

## บทนำ

หัวข้อนี้คือพื้นฐานสำคัญของการพัฒนาเว็บด้วย PHP เพราะเกือบทุกระบบต้องมีการรับข้อมูลจากผู้ใช้ผ่าน **form** ไม่ว่าจะเป็นการสมัครสมาชิก, login, ติดต่อสอบถาม, ค้นหา, หรืออัปโหลดไฟล์ หากรับข้อมูลโดยไม่ตรวจสอบให้ดี อาจเกิดข้อมูลผิดรูปแบบ, bug ในระบบ, หรือช่องโหว่ด้านความปลอดภัยได้

บทเรียนนี้จึงเน้นแนวทางแบบ **modern PHP** คือแยกขั้นตอนให้ชัดเจนระหว่าง **รับข้อมูล**, **ตรวจสอบความถูกต้อง (validation)**, **แสดงข้อความผิดพลาด**, และ **ประมวลผลข้อมูลอย่างปลอดภัย** พร้อมตัวอย่างที่อ่านง่ายและนำไปใช้บน GitHub ได้ทันที

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจการทำงานของ HTML form ร่วมกับ PHP
- รู้จักความต่างของ `GET` และ `POST`
- รับค่าจากฟอร์มอย่างเป็นระบบ
- ตรวจสอบข้อมูลด้วย validation ฝั่ง server
- แสดง error messages แบบอ่านง่าย
- ใช้ `filter_input()` และ `filter_var()` ได้อย่างเหมาะสม
- ป้องกันปัญหาพื้นฐาน เช่น XSS, CSRF และการเก็บรหัสผ่านแบบไม่ปลอดภัย
- เห็นแนวทางจัดโค้ด form handling ให้ reusable มากขึ้น

---

## หัวข้อย่อยที่ 1: ฟอร์มใน PHP ทำงานอย่างไร

เมื่อผู้ใช้กรอกข้อมูลใน form แล้วกด submit ข้อมูลจะถูกส่งมายัง PHP script ตาม `action` และ `method` ที่กำหนดไว้ โดยงานของฝั่ง PHP คือรับข้อมูล, ตรวจสอบความถูกต้อง, และตัดสินใจว่าจะบันทึกหรือส่งกลับ error

โดยทั่วไป หากเป็นข้อมูลที่เปลี่ยนแปลงสถานะของระบบ เช่น สมัครสมาชิก, login, create/update/delete ควรใช้ `POST` ส่วน `GET` เหมาะกับงานที่ใช้ query เช่น search หรือ filter

```php
<?php

declare(strict_types=1);
?>

<form action="" method="post">
    <label for="name">Name</label>
    <input type="text" id="name" name="name">

    <button type="submit">Submit</button>
</form>
```

สิ่งที่ควรเข้าใจ:
- `method="post"` เหมาะกับข้อมูลที่ไม่ควรแสดงใน URL
- `action=""` หมายถึง submit กลับมาที่หน้าเดิม
- form เป็นเพียงจุดรับข้อมูล แต่ความปลอดภัยต้องตรวจฝั่ง server เสมอ

---

## หัวข้อย่อยที่ 2: รับค่าจาก `$_POST` แบบพื้นฐาน

PHP สามารถอ่านค่าจากฟอร์มที่ส่งด้วย `POST` ผ่าน `$_POST` ได้โดยตรง ซึ่งเหมาะสำหรับการเริ่มต้นเรียนรู้ แต่ในการใช้งานจริงควรตรวจสอบว่ามี key นั้นอยู่ก่อน และไม่ควรเชื่อข้อมูลจากผู้ใช้ทันที

```php
<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อ่านค่าจากฟอร์มแบบพื้นฐาน
    $name = $_POST['name'] ?? '';

    echo "Hello, " . $name;
}
```

สิ่งที่ควรเข้าใจ:
- `$_SERVER['REQUEST_METHOD']` ใช้ตรวจว่าหน้านี้ถูกเรียกด้วย `POST` หรือไม่
- `?? ''` ใช้กำหนดค่า default หากไม่มีข้อมูลส่งมา
- ขั้นตอนนี้ยังเป็นเพียงการ “รับค่า” ยังไม่ใช่ validation ที่สมบูรณ์

---

## หัวข้อย่อยที่ 3: ใช้ `filter_input()` เพื่ออ่าน input ให้ชัดเจนขึ้น

ใน PHP มี `filter_input()` สำหรับดึงค่าจาก external input เช่น `INPUT_GET` และ `INPUT_POST` พร้อม filter ได้ในขั้นตอนเดียว ทำให้โค้ดชัดเจนขึ้นเมื่อเทียบกับการอ่าน `$_POST` ตรง ๆ

```php
<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // อ่าน email จาก POST พร้อม trim ภายหลัง
    $email = filter_input(INPUT_POST, 'email', FILTER_DEFAULT);
    $email = is_string($email) ? trim($email) : '';

    echo $email;
}
```

สิ่งที่ควรเข้าใจ:
- `filter_input()` เหมาะกับการดึงค่าจาก input โดยตรง
- ควร trim ข้อมูล string เพิ่ม เพื่อเอาช่องว่างหัวท้ายออก
- การอ่านค่ากับการ validate ควรคิดเป็นคนละขั้นตอน

---

## หัวข้อย่อยที่ 4: Validation ฝั่ง Server คือสิ่งที่ขาดไม่ได้

แม้จะมี validation ฝั่ง browser เช่น `required`, `type="email"`, `minlength` แต่ทั้งหมดนี้ช่วยแค่เรื่อง UX เท่านั้น ผู้ใช้หรือ attacker ยังสามารถส่งข้อมูลข้ามขั้นตอน client-side ได้ จึงต้องมี **server-side validation** เสมอ

แนวคิดสำคัญคือให้ตรวจข้อมูล **เร็วที่สุดหลังรับเข้ามา** และตรวจตามกติกาทางธุรกิจจริง เช่น email ต้องถูก format, password ต้องยาวพอ, อายุต้องเป็นเลขและอยู่ในช่วงที่กำหนด

```php
<?php

declare(strict_types=1);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $age = trim((string) ($_POST['age'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'กรุณากรอกชื่อ';
    }

    if ($age === '') {
        $errors['age'] = 'กรุณากรอกอายุ';
    } elseif (!ctype_digit($age)) {
        $errors['age'] = 'อายุต้องเป็นตัวเลขเท่านั้น';
    } elseif ((int) $age < 18) {
        $errors['age'] = 'อายุต้องไม่น้อยกว่า 18 ปี';
    }
}
```

สิ่งที่ควรเข้าใจ:
- อย่าเชื่อข้อมูลจาก form โดยตรง
- Validation ควรตอบคำถามว่า “ข้อมูลนี้ใช้ได้จริงหรือไม่”
- เก็บ error เป็น array จะทำให้จัดการหลาย field ได้ง่ายขึ้น

---

## หัวข้อย่อยที่ 5: ตรวจสอบ email และค่าที่มีรูปแบบเฉพาะ

ข้อมูลบางประเภทมีรูปแบบมาตรฐาน เช่น email, URL, integer หรือ boolean ซึ่ง PHP มี filter สำเร็จรูปที่ช่วยตรวจสอบเบื้องต้นได้ เช่น `FILTER_VALIDATE_EMAIL`

```php
<?php

declare(strict_types=1);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($email === '') {
        $errors['email'] = 'กรุณากรอกอีเมล';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
}
```

สิ่งที่ควรเข้าใจ:
- `filter_var()` ใช้ตรวจรูปแบบข้อมูลได้สะดวก
- format ถูกต้อง ไม่ได้แปลว่าธุรกิจยอมรับเสมอ เช่น อาจต้องตรวจ domain เพิ่ม
- validation ควรมีทั้งระดับ syntax และ business rules

---

## หัวข้อย่อยที่ 6: แสดง error และเก็บค่าเดิมกลับเข้า form

UX ที่ดีคือเมื่อ validation ไม่ผ่าน ควรแสดง error ใกล้ field ที่ผิด และคืนค่าที่ผู้ใช้เคยกรอกไว้กลับเข้า form เพื่อไม่ให้ต้องกรอกใหม่ทั้งหมด

เวลาแสดงค่ากลับใน HTML ต้องระวังเรื่อง XSS จึงควร escape output ก่อนเสมอด้วย `htmlspecialchars()`

```php
<?php

declare(strict_types=1);

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'กรุณากรอกชื่อ';
    }

    if ($email === '') {
        $errors['email'] = 'กรุณากรอกอีเมล';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
}
?>

<form action="" method="post">
    <label for="name">Name</label>
    <input
        type="text"
        id="name"
        name="name"
        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
    >
    <small><?= $errors['name'] ?? '' ?></small>

    <label for="email">Email</label>
    <input
        type="email"
        id="email"
        name="email"
        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
    >
    <small><?= $errors['email'] ?? '' ?></small>

    <button type="submit">Register</button>
</form>
```

สิ่งที่ควรเข้าใจ:
- คืนค่าเดิมช่วยให้ผู้ใช้ทำงานต่อได้ง่าย
- `htmlspecialchars()` ช่วยลดความเสี่ยง XSS ตอนแสดงผล
- แยก `$errors` เป็นราย field ทำให้หน้า form ดูแลง่ายขึ้น

---

## หัวข้อย่อยที่ 7: แยก validation เป็นฟังก์ชันเพื่อทำ Reusable Code

เมื่อฟอร์มเริ่มซับซ้อนขึ้น ไม่ควรเขียน validation ทุกอย่างกองไว้ในไฟล์เดียว ควรแยกเป็นฟังก์ชัน เช่น `validateRegistrationData()` เพื่อให้อ่านง่าย ทดสอบง่าย และนำกลับมาใช้ซ้ำได้

```php
<?php

declare(strict_types=1);

/**
 * ตรวจสอบข้อมูลสมัครสมาชิกและคืนค่า error list
 *
 * @param array<string, string> $data
 * @return array<string, string>
 */
function validateRegistrationData(array $data): array
{
    $errors = [];

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($name === '') {
        $errors['name'] = 'กรุณากรอกชื่อ';
    }

    if ($email === '') {
        $errors['email'] = 'กรุณากรอกอีเมล';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    return $errors;
}

$data = [
    'name' => (string) ($_POST['name'] ?? ''),
    'email' => (string) ($_POST['email'] ?? ''),
    'password' => (string) ($_POST['password'] ?? ''),
];

$errors = validateRegistrationData($data);
```

สิ่งที่ควรเข้าใจ:
- validation function ช่วยให้โค้ดสะอาดขึ้น
- แยกหน้าที่รับข้อมูลกับตรวจข้อมูลออกจากกัน
- โครงสร้างนี้พร้อมต่อยอดไปสู่ OOP หรือ service class ได้ง่าย

---

## หัวข้อย่อยที่ 8: Password Validation และการเก็บรหัสผ่านอย่างปลอดภัย

ถ้าฟอร์มเกี่ยวข้องกับการสมัครสมาชิกหรือ login ห้ามเก็บรหัสผ่านแบบ plain text เด็ดขาด ควรตรวจขั้นต่ำ เช่น ความยาว และเมื่อผ่านแล้วให้เก็บด้วย `password_hash()` จากนั้นตรวจตอน login ด้วย `password_verify()`

```php
<?php

declare(strict_types=1);

$password = (string) ($_POST['password'] ?? '');
$errors = [];

if (strlen($password) < 8) {
    $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
}

if ($errors === []) {
    // hash รหัสผ่านก่อนบันทึกลงฐานข้อมูล
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    echo $passwordHash;
}
```

ตัวอย่างตรวจรหัสผ่านตอน login:

```php
<?php

declare(strict_types=1);

$plainPassword = 'secret1234';
$storedHash = password_hash('secret1234', PASSWORD_DEFAULT);

if (password_verify($plainPassword, $storedHash)) {
    echo 'Login success';
} else {
    echo 'Invalid credentials';
}
```

สิ่งที่ควรเข้าใจ:
- ใช้ `password_hash()` สำหรับเก็บรหัสผ่าน
- ใช้ `password_verify()` สำหรับตรวจรหัสผ่าน
- อย่าใช้ `md5()` หรือ `sha1()` สำหรับ password storage

---

## หัวข้อย่อยที่ 9: ป้องกัน XSS ตอนแสดงผล

แม้ข้อมูลจะผ่าน validation แล้ว ก็ยังไม่ควรแสดงกลับออกไปแบบดิบ หากข้อมูลนั้นมาจากผู้ใช้ เพราะอาจมี script หรือ HTML แฝงมาได้ หลักง่ายที่สุดคือ **escape ตอน output**

```php
<?php

declare(strict_types=1);

$comment = (string) ($_POST['comment'] ?? '');
?>

<h2>User Comment</h2>
<p><?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?></p>
```

สิ่งที่ควรเข้าใจ:
- validation ไม่แทน output escaping
- ข้อมูลจากผู้ใช้ควรถูกมองว่า untrusted เสมอ
- ใช้ `htmlspecialchars()` เมื่อจะแสดงข้อความลง HTML

---

## หัวข้อย่อยที่ 10: CSRF Basics สำหรับฟอร์มที่เปลี่ยนข้อมูลระบบ

ฟอร์มที่สร้าง แก้ไข ลบ หรือเปลี่ยนข้อมูลสำคัญ ควรมี **CSRF token** เพื่อยืนยันว่าคำขอมาจาก form ที่ระบบออกให้จริง ไม่ใช่คำขอปลอมจากเว็บไซต์อื่น

ตัวอย่างแนวคิดพื้นฐาน:

```php
<?php

declare(strict_types=1);

session_start();

// สร้าง token ครั้งแรก
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors['csrf'] = 'Invalid CSRF token';
    }
}
?>

<form action="" method="post">
    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
    >

    <label for="title">Title</label>
    <input type="text" id="title" name="title">

    <button type="submit">Save</button>
</form>
```

สิ่งที่ควรเข้าใจ:
- token ควรถูกสร้างฝั่ง server
- ตรวจ token ทุกครั้งก่อนประมวลผลคำขอสำคัญ
- ฟอร์มที่มีผลต่อข้อมูลระบบไม่ควรพึ่งแค่ `POST` อย่างเดียว

---

## หัวข้อย่อยที่ 11: File Upload Validation เบื้องต้น

ถ้าฟอร์มมีการอัปโหลดไฟล์ ต้องระวังมากกว่าฟิลด์ข้อความทั่วไป เพราะ attacker อาจอัปโหลดไฟล์อันตรายได้ ควรตรวจอย่างน้อยเรื่อง `POST`, error code, ชนิดไฟล์, นามสกุล, ขนาดไฟล์ และเปลี่ยนชื่อไฟล์ใหม่ฝั่งระบบ

```php
<?php

declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        exit('Upload failed');
    }

    // จำกัดขนาดไฟล์ไม่เกิน 2 MB
    if ($file['size'] > 2 * 1024 * 1024) {
        exit('File too large');
    }

    $allowedMimeTypes = ['image/jpeg', 'image/png'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        exit('Invalid file type');
    }

    $newFileName = bin2hex(random_bytes(16)) . '.jpg';

    // ตัวอย่างแนวคิดการย้ายไฟล์
    // move_uploaded_file($file['tmp_name'], __DIR__ . '/uploads/' . $newFileName);

    echo 'Upload success';
}
```

สิ่งที่ควรเข้าใจ:
- อย่าเชื่อ `Content-Type` จาก browser เพียงอย่างเดียว
- ควรตรวจ MIME type และขนาดไฟล์ฝั่ง server
- ควรเปลี่ยนชื่อไฟล์ใหม่ก่อนเก็บจริง

---

## หัวข้อย่อยที่ 12: ตัวอย่างฟอร์มสมัครสมาชิกแบบครบวงจร

ตัวอย่างนี้รวมแนวคิดหลักเข้าด้วยกัน ได้แก่ รับข้อมูล, validate, เก็บ error, escape ค่าเดิม และ hash password

```php
<?php

declare(strict_types=1);

$errors = [];
$data = [
    'name' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name'] = trim((string) ($_POST['name'] ?? ''));
    $data['email'] = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($data['name'] === '') {
        $errors['name'] = 'กรุณากรอกชื่อ';
    }

    if ($data['email'] === '') {
        $errors['email'] = 'กรุณากรอกอีเมล';
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (strlen($password) < 8) {
        $errors['password'] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    if ($errors === []) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // ในงานจริงให้นำ $data และ $passwordHash ไปบันทึกฐานข้อมูลด้วย prepared statements
        echo 'Register success';
    }
}
?>

<form action="" method="post" novalidate>
    <label for="name">Name</label>
    <input
        type="text"
        id="name"
        name="name"
        value="<?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?>"
    >
    <small><?= $errors['name'] ?? '' ?></small>

    <label for="email">Email</label>
    <input
        type="email"
        id="email"
        name="email"
        value="<?= htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8') ?>"
    >
    <small><?= $errors['email'] ?? '' ?></small>

    <label for="password">Password</label>
    <input type="password" id="password" name="password">
    <small><?= $errors['password'] ?? '' ?></small>

    <button type="submit">Register</button>
</form>
```

สิ่งที่ควรเข้าใจ:
- โค้ดตัวอย่างนี้ครอบคลุม flow พื้นฐานของฟอร์มจริง
- เหมาะใช้เป็นต้นแบบก่อนแยกไปเป็น controller/service ในโปรเจกต์ใหญ่
- ขั้นตอนบันทึกฐานข้อมูลควรใช้ prepared statements เพื่อป้องกัน SQL injection

---

## แนวทางเขียน Forms และ Validation แบบ professional

แนวทางที่ควรยึดตั้งแต่ต้น:

- ใช้ `POST` สำหรับคำขอที่เปลี่ยนแปลงข้อมูลระบบ
- ตรวจ `REQUEST_METHOD` ก่อนประมวลผลทุกครั้ง
- อย่าเชื่อ client-side validation เพียงอย่างเดียว
- แยก validation ออกจาก view logic เท่าที่ทำได้
- เก็บ error messages เป็นโครงสร้างที่อ่านง่าย
- escape output ทุกครั้งเมื่อแสดงข้อมูลจากผู้ใช้
- ใช้ CSRF token กับฟอร์มสำคัญ
- ใช้ `password_hash()` และ `password_verify()` กับรหัสผ่าน
- หากบันทึกข้อมูลลงฐานข้อมูล ให้ใช้ prepared statements
- สำหรับ file uploads ให้ตรวจชนิดไฟล์ ขนาดไฟล์ และเปลี่ยนชื่อไฟล์ใหม่เสมอ

---

## สรุป

หัวข้อ **Forms and Validation in PHP** เป็นพื้นฐานที่สำคัญมากของงานพัฒนาเว็บ เพราะเป็นจุดที่ระบบต้องติดต่อกับข้อมูลจากผู้ใช้โดยตรง หากวางโครงสร้างการรับข้อมูลและ validation ดีตั้งแต่ต้น โค้ดจะอ่านง่าย ปลอดภัยขึ้น และต่อยอดไปสู่ระบบที่ใหญ่ขึ้นได้ง่ายกว่าเดิม

เมื่อเข้าใจเรื่อง form handling, server-side validation, error handling, output escaping และ security basics แล้ว คุณจะพร้อมต่อยอดไปสู่หัวข้อที่ซับซ้อนขึ้น เช่น authentication, session handling, file uploads, database access และ MVC architecture

---

## References

- PHP Manual: Dealing with Forms
- PHP Manual: `filter_input()`
- PHP Manual: `filter_var()`
- PHP Manual: `htmlspecialchars()`
- PHP Manual: `password_hash()` / `password_verify()`
- OWASP Cheat Sheet: Input Validation
- OWASP Cheat Sheet: Cross-Site Scripting Prevention
- OWASP Cheat Sheet: CSRF Prevention
- OWASP Cheat Sheet: File Upload Security
