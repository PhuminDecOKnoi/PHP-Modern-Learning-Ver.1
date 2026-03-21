File Name: Clean Code and Project Structure in PHP.md

# Clean Code and Project Structure in PHP

## บทนำ

หัวข้อนี้เน้นการเขียน PHP ให้ **อ่านง่าย ดูแลง่าย และขยายต่อได้จริง** โดยเชื่อม 2 เรื่องสำคัญเข้าด้วยกัน คือ **Clean Code** และ **Project Structure** เพราะต่อให้โค้ดรันได้ แต่ถ้าโครงสร้างไฟล์สับสน ชื่อคลาสไม่สื่อความหมาย หรือ logic ปนกันหลายหน้าที่ โปรเจกต์ก็จะดูแลยากในระยะยาว

สำหรับ PHP ยุคปัจจุบัน แนวทางที่ควรยึดคือใช้ **Composer**, จัดโครงสร้างคลาสด้วย **PSR-4 autoloading**, เขียนโค้ดให้สอดคล้องกับ **PSR-12 coding style**, แยกความรับผิดชอบของไฟล์และคลาสให้ชัด และทำให้จุดเริ่มต้นของแอปเรียบง่ายที่สุด

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจความหมายของ clean code ในบริบทของ PHP
- จัดโครงสร้างโปรเจกต์ให้เป็นระเบียบตั้งแต่เริ่ม
- ตั้งชื่อไฟล์ คลาส ฟังก์ชัน และตัวแปรให้อ่านง่าย
- แยกหน้าที่ของโค้ดด้วย functions, classes และ folders
- ใช้ Composer และ PSR-4 กับโปรเจกต์แบบ modern
- เห็นตัวอย่าง project structure ที่พร้อมต่อยอด
- รู้แนวปฏิบัติที่ช่วยให้ทีมทำงานร่วมกันง่ายขึ้น

---

## หัวข้อย่อยที่ 1: Clean Code คืออะไรใน PHP

Clean code ไม่ได้หมายถึงโค้ดที่ "สวย" อย่างเดียว แต่หมายถึงโค้ดที่ **อ่านแล้วเข้าใจได้เร็ว**, **เปลี่ยนแปลงได้ง่าย**, และ **ลดความเสี่ยงจากความสับสน** ในงานจริง หลักคิดสำคัญคือให้ชื่อชัด, function สั้น, class มีหน้าที่ชัดเจน, และหลีกเลี่ยง logic ซ้อนกันโดยไม่จำเป็น

ตัวอย่างด้านล่างแสดงความต่างระหว่างโค้ดที่ชื่อไม่สื่อความหมาย กับโค้ดที่ตั้งชื่อชัดเจนและอ่านง่ายกว่า

```php
<?php

declare(strict_types=1);

// ตัวอย่างที่อ่านยาก
function x($a, $b)
{
    return $a - ($a * $b / 100);
}

echo x(1000, 10);
```

ตัวอย่างที่อ่านง่ายกว่า:

```php
<?php

declare(strict_types=1);

// คำนวณราคาหลังหักส่วนลด
function calculateDiscountedPrice(float $originalPrice, float $discountPercent): float
{
    return $originalPrice - ($originalPrice * $discountPercent / 100);
}

echo calculateDiscountedPrice(1000, 10);
```

สิ่งที่ควรเข้าใจ:
- ชื่อที่ดีช่วยลดเวลาตีความโค้ด
- function ควรบอกชัดว่าทำอะไร
- clean code เริ่มจากความชัดเจน ไม่ใช่ความซับซ้อน

---

## หัวข้อย่อยที่ 2: ตั้งชื่อให้สื่อความหมาย

การตั้งชื่อที่ดีเป็นพื้นฐานของ clean code ใน PHP ไม่ว่าจะเป็นตัวแปร, function, class หรือไฟล์ ควรตั้งชื่อให้เห็นหน้าที่ของมันโดยตรง เช่น `UserRepository`, `sendWelcomeEmail()`, `calculateVat()` หรือ `OrderController`

หลีกเลี่ยงชื่อกว้างเกินไป เช่น `data`, `temp`, `doStuff`, `helper1` เพราะชื่อเหล่านี้ไม่ช่วยให้ผู้อ่านเข้าใจว่าโค้ดส่วนนั้นทำอะไร

```php
<?php

declare(strict_types=1);

// ชื่อที่สื่อความหมายชัดเจน
$userEmail = 'user@example.com';
$isEmailVerified = true;

function sendPasswordResetLink(string $email): void
{
    // จำลองการส่งลิงก์รีเซ็ตรหัสผ่าน
    echo "Send reset link to {$email}";
}

if ($isEmailVerified) {
    sendPasswordResetLink($userEmail);
}
```

สิ่งที่ควรเข้าใจ:
- ชื่อที่ดีควรสื่อความหมายได้โดยไม่ต้องอธิบายเพิ่มมาก
- ตัวแปร boolean ควรตั้งชื่อให้อ่านเป็นจริง/เท็จได้
- ชื่อที่สื่อชัดช่วยให้ code review ง่ายขึ้นมาก

---

## หัวข้อย่อยที่ 3: แยกหน้าที่ของโค้ดให้ชัด

ปัญหาที่พบบ่อยในโปรเจกต์ PHP คือเอาทุกอย่างไปรวมไว้ในไฟล์เดียว เช่น รับ request, validate, query database, format output และ render response อยู่ในที่เดียวกัน วิธีที่ดีกว่าคือแยกหน้าที่ออกเป็นส่วนย่อย เช่น controller, service, repository หรือ utility ตามขนาดของโปรเจกต์

หลักง่าย ๆ คือ **หนึ่ง function หรือหนึ่ง class ควรมีหน้าที่หลักที่ชัดเจน** ถ้าฟังก์ชันยาวจนต้องเลื่อนหลายหน้าจอ มักเป็นสัญญาณว่าควรแยก logic

```php
<?php

declare(strict_types=1);

function validateEmail(string $email): bool
{
    // ตรวจสอบรูปแบบอีเมล
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function registerUser(string $email): string
{
    if (!validateEmail($email)) {
        return 'Invalid email';
    }

    // จำลองการบันทึกข้อมูล
    return 'User registered successfully';
}

echo registerUser('user@example.com');
```

สิ่งที่ควรเข้าใจ:
- แยก validation ออกจาก business logic
- function เล็กลงจะทดสอบง่ายขึ้น
- โค้ดจะ reuse ได้มากขึ้นเมื่อแต่ละส่วนรับผิดชอบชัดเจน

---

## หัวข้อย่อยที่ 4: โครงสร้างโปรเจกต์แบบ modern PHP

สำหรับโปรเจกต์ PHP ปัจจุบัน ควรแยกโค้ดออกเป็นโฟลเดอร์ที่มีความหมายชัดเจน เช่น `public/`, `src/`, `config/`, `tests/` และใช้ Composer สำหรับ autoloading แทนการ `require` ไฟล์คลาสทีละไฟล์

ตัวอย่างโครงสร้างพื้นฐาน:

```text
my-php-app/
├── composer.json
├── public/
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   └── Support/
├── config/
├── tests/
└── vendor/
```

คำอธิบาย:
- `public/` เป็น entry point ของแอป
- `src/` เก็บ source code หลัก
- `config/` เก็บค่าตั้งต้นของระบบ
- `tests/` เก็บชุดทดสอบ
- `vendor/` ถูกสร้างโดย Composer

สิ่งที่ควรเข้าใจ:
- โครงสร้างที่ชัดช่วยให้สมาชิกทีมเข้าใจโปรเจกต์เร็วขึ้น
- ควรหลีกเลี่ยงการวางไฟล์ PHP ปะปนกันทุกที่
- เริ่มโครงสร้างดีตั้งแต่แรก จะช่วยลดงาน refactor ภายหลัง

---

## หัวข้อย่อยที่ 5: ใช้ Composer และ PSR-4 Autoload

Composer คือเครื่องมือหลักในการจัดการ dependencies และ autoload ของ PHP สมัยใหม่ โดยแนวทางที่แนะนำคือใช้ **PSR-4** เพื่อ map namespace ไปยังโฟลเดอร์ เช่น `App\` ไปยัง `src/`

ตัวอย่าง `composer.json`:

```json
{
  "name": "demo/my-php-app",
  "type": "project",
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "require-dev": {
    "phpunit/phpunit": "^12.0"
  }
}
```

หลังแก้ `composer.json` ให้สั่ง:

```bash
composer dump-autoload
```

ตัวอย่าง class ใน `src/Service/WelcomeService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

class WelcomeService
{
    public function message(string $name): string
    {
        return "Welcome, {$name}";
    }
}
```

ตัวอย่างเรียกใช้งานใน `public/index.php`:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Service\WelcomeService;

$service = new WelcomeService();

echo $service->message('Phumin');
```

สิ่งที่ควรเข้าใจ:
- Composer ช่วยจัดการ autoload ให้เป็นมาตรฐาน
- PSR-4 ช่วยให้โครงสร้าง namespace และไฟล์สอดคล้องกัน
- ลดการ `require_once` หลายไฟล์ด้วยมือ

---

## หัวข้อย่อยที่ 6: Coding Style ที่ทีมอ่านร่วมกันได้

การเขียนโค้ดให้ format สม่ำเสมอช่วยลดภาระในการอ่าน และทำให้ทีม review โค้ดได้เร็วขึ้น ในโลก PHP นิยมอิงมาตรฐานของ PHP-FIG เช่น **PSR-12** ซึ่งครอบคลุมเรื่องการจัดวางไฟล์, การเว้นวรรค, การจัดบรรทัด, class, methods และ control structures

ตัวอย่างโค้ดที่จัด format แบบอ่านง่าย:

```php
<?php

declare(strict_types=1);

namespace App\Support;

class PriceFormatter
{
    public function format(float $price): string
    {
        return number_format($price, 2) . ' THB';
    }
}
```

สิ่งที่ควรเข้าใจ:
- coding style ที่สม่ำเสมอช่วยลด cognitive load
- ควรใช้ formatter หรือ linter ในโปรเจกต์จริง
- style guide ไม่ได้ทำให้ logic ดีขึ้นเอง แต่ช่วยให้โค้ดสื่อสารได้ดีขึ้น

---

## หัวข้อย่อยที่ 7: แยก Configuration ออกจาก Logic

อีกหนึ่งหลักสำคัญของ project structure ที่ดี คือไม่ hard-code ค่าที่เปลี่ยนตาม environment เช่น host, port, database name หรือ app mode ไว้ใน logic หลักของระบบ แต่ควรแยกไว้ในไฟล์ config หรือ environment variables

ตัวอย่างแบบง่าย:

```php
<?php

declare(strict_types=1);

$config = [
    'app_name' => 'My PHP App',
    'debug' => true,
];

function appName(array $config): string
{
    return $config['app_name'];
}

echo appName($config);
```

สิ่งที่ควรเข้าใจ:
- config ไม่ควรปนกับ business logic
- ช่วยให้เปลี่ยนค่าระหว่าง dev/prod ได้ง่าย
- เมื่อโปรเจกต์โตขึ้น ควรแยก config เป็นไฟล์หรือระบบจัดการ environment

---

## หัวข้อย่อยที่ 8: ลดการซ้ำด้วย Reusable Code

โค้ดที่ดีควรลด duplication โดยไม่ทำ abstraction เร็วเกินไป วิธีที่เหมาะคือเริ่มจากการดึง logic ที่ใช้ซ้ำจริงออกเป็น function, class หรือ service หลังจากเห็น pattern ชัดแล้วค่อยสรุปเป็นโครงสร้างกลาง

```php
<?php

declare(strict_types=1);

function formatCurrency(float $amount): string
{
    // ฟังก์ชันกลางสำหรับแสดงผลจำนวนเงิน
    return number_format($amount, 2) . ' THB';
}

echo formatCurrency(1500);
echo PHP_EOL;
echo formatCurrency(2599.5);
```

สิ่งที่ควรเข้าใจ:
- reusable code ช่วยลดการแก้หลายจุด
- อย่ารีบสร้าง helper ใหญ่เกินไปโดยยังไม่เห็นรูปแบบการใช้งานจริง
- แยกโค้ดเฉพาะส่วนที่มีโอกาสใช้ซ้ำจริง

---

## หัวข้อย่อยที่ 9: ตัวอย่าง Mini Project Structure

ตัวอย่างนี้เป็นโปรเจกต์เล็กที่ออกแบบให้พร้อมต่อยอด

```text
mini-app/
├── composer.json
├── public/
│   └── index.php
├── src/
│   ├── Controller/
│   │   └── HomeController.php
│   └── Service/
│       └── WelcomeService.php
└── tests/
```

ตัวอย่าง `src/Controller/HomeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WelcomeService;

class HomeController
{
    public function __construct(
        private WelcomeService $welcomeService
    ) {
    }

    public function index(): string
    {
        // controller ทำหน้าที่ประสานงาน
        return $this->welcomeService->message('Developer');
    }
}
```

ตัวอย่าง `public/index.php`:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controller\HomeController;
use App\Service\WelcomeService;

$controller = new HomeController(new WelcomeService());

echo $controller->index();
```

สิ่งที่ควรเข้าใจ:
- controller ไม่ควรแบกทุกอย่างเอง
- service ใช้เก็บ business logic ที่นำกลับมาใช้ซ้ำได้
- โครงสร้างนี้ขยายต่อเป็น MVC หรือ layered architecture ได้ง่าย

---

## หัวข้อย่อยที่ 10: Checklist สำหรับโปรเจกต์ PHP ที่ดู professional

ใช้ checklist นี้ตอนเริ่มโปรเจกต์หรือ review โค้ด:

- ตั้งชื่อไฟล์ คลาส ฟังก์ชัน และตัวแปรให้สื่อความหมาย
- ใช้ `declare(strict_types=1);` ในไฟล์ที่เหมาะสม
- แยก `public/` ออกจาก `src/`
- ใช้ Composer และ PSR-4 autoload
- จัด format โค้ดให้สม่ำเสมอตาม style guide
- แยก config ออกจาก business logic
- ลด duplication ด้วย reusable code
- แยก class ตามหน้าที่ ไม่รวมทุกอย่างไว้ที่เดียว
- เตรียม `tests/` ตั้งแต่ต้น แม้ยังเขียน test ไม่ครบ
- หลีกเลี่ยงไฟล์ชื่อกว้าง ๆ เช่น `helper.php`, `test2.php`, `newfile.php`

---

## สรุป

Clean code และ project structure เป็นพื้นฐานสำคัญของ PHP ระดับ professional เพราะช่วยให้โปรเจกต์ **อ่านง่าย ดูแลง่าย ทดสอบง่าย และขยายต่อได้จริง** เมื่อคุณเริ่มต้นด้วยชื่อที่ชัด, โครงสร้างโฟลเดอร์ที่ดี, การแยกหน้าที่ของโค้ด และการใช้ Composer + PSR-4 อย่างถูกต้อง คุณจะทำงานกับโปรเจกต์ขนาดเล็กและขนาดใหญ่ได้มั่นใจขึ้นมาก

หัวข้อนี้ไม่ได้จบแค่ “จัดโฟลเดอร์ให้สวย” แต่คือการวางฐานของระบบให้พร้อมสำหรับ testing, database, API, authentication และการทำงานร่วมกันเป็นทีมในระยะยาว

## References

- PHP-FIG: PSR-12 Extended Coding Style
- PHP-FIG: PSR-4 Autoloader
- Composer Documentation: Basic Usage
- Composer Documentation: Schema / autoload
