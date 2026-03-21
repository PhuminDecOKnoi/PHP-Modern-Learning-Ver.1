# PHP Syntax and Modern Project Setup

บทเรียนนี้ออกแบบมาเพื่อใช้เป็นเอกสารบน GitHub ได้ทันที เหมาะสำหรับผู้เริ่มต้นที่ต้องการเข้าใจ **PHP syntax พื้นฐาน** และการเริ่มต้นโปรเจกต์แบบ **modern PHP** อย่างเป็นระบบ

---

## บทนำ

หัวข้อนี้ช่วยให้ผู้เรียนเข้าใจทั้ง 2 ส่วนสำคัญในเวลาเดียวกัน คือ

1. รูปแบบการเขียนโค้ด PHP ที่ถูกต้องและอ่านง่าย
2. วิธีตั้งต้นโปรเจกต์ PHP แบบสมัยใหม่ให้พร้อมต่อยอด

แนวคิดหลักของ modern PHP คือการเขียนโค้ดให้เป็นระเบียบ ดูแลง่าย และขยายระบบได้ในอนาคต เช่น

- ใช้ `declare(strict_types=1);`
- ใช้ `namespace`
- ใช้ Composer สำหรับจัดการ dependencies และ autoload
- แยกโครงสร้างไฟล์ให้ชัดเจน เช่น `public/` และ `src/`

---

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจ PHP syntax พื้นฐานที่จำเป็น
- ใช้งานตัวแปร, function และ type hints ได้
- เข้าใจ `strict_types` และประโยชน์ในการลดข้อผิดพลาด
- รู้จักโครงสร้างโปรเจกต์ PHP แบบ modern
- เริ่มใช้ Composer และ PSR-4 autoload ได้
- รันโปรเจกต์แบบ local development ได้

---

## หัวข้อย่อยที่ 1: PHP Syntax พื้นฐานที่ต้องรู้

PHP เริ่มต้นโค้ดด้วย `<?php` ซึ่งเป็นรูปแบบมาตรฐานที่ควรใช้ในทุกโปรเจกต์ ส่วนคำสั่งพื้นฐานอย่าง `echo` ใช้สำหรับแสดงผลข้อความออกหน้าจอ

```php
<?php

// แสดงข้อความพื้นฐาน
 echo "Hello, PHP!";
```

### สิ่งที่ควรเข้าใจ

- คำสั่ง PHP ส่วนใหญ่จบด้วย `;`
- `echo` ใช้แสดงผลข้อความ
- ควรใช้ `<?php` เป็นมาตรฐานเสมอ

### ตัวอย่างเพิ่มเติม

```php
<?php

$name = "Phumin";

// แสดงผลข้อความร่วมกับตัวแปร
 echo "Hello, " . $name . "!";
```

### ผลลัพธ์

```text
Hello, Phumin!
```

---

## หัวข้อย่อยที่ 2: ตัวแปรและฟังก์ชันแบบอ่านง่าย

ใน PHP ตัวแปรจะขึ้นต้นด้วย `$` และควรตั้งชื่อให้สื่อความหมาย ส่วนฟังก์ชันช่วยให้โค้ดแยกหน้าที่ชัดเจนและนำกลับมาใช้ซ้ำได้ง่าย

```php
<?php

$productName = "Notebook";
$price = 59.90;

// ฟังก์ชันสำหรับจัดรูปแบบข้อความสินค้า
function formatProduct(string $name, float $price): string
{
    // number_format ช่วยจัดรูปแบบตัวเลขให้ดูอ่านง่าย
    return $name . " - " . number_format($price, 2) . " THB";
}

 echo formatProduct($productName, $price);
```

### สิ่งที่ควรเข้าใจ

- `string` และ `float` คือ type hints
- `: string` หมายถึงฟังก์ชันต้องคืนค่าเป็น string
- ฟังก์ชันที่ดีควรมีหน้าที่ชัดเจนและเรียกใช้ซ้ำได้

---

## หัวข้อย่อยที่ 3: `strict_types` และการเขียนโค้ดให้ชัดเจน

`strict_types` ช่วยให้ PHP ตรวจสอบชนิดข้อมูลเข้มงวดขึ้นในระดับไฟล์ ทำให้ลดความผิดพลาดจากการส่งค่าผิดชนิดได้ดีขึ้น

```php
<?php

declare(strict_types=1);

function greet(string $name): string
{
    return "Hello, {$name}";
}

 echo greet("Developer");
```

### ตัวอย่างที่ควรระวัง

```php
<?php

declare(strict_types=1);

function add(int $a, int $b): int
{
    return $a + $b;
}

// โค้ดนี้จะเกิด TypeError เพราะส่ง string แทน int
 echo add("10", 20);
```

### สิ่งที่ควรเข้าใจ

- ช่วยจับข้อผิดพลาดได้เร็วขึ้น
- เหมาะกับโปรเจกต์ที่ต้องการคุณภาพโค้ด
- ควรใช้กับไฟล์หลักของแอปพลิเคชัน

---

## หัวข้อย่อยที่ 4: โครงสร้างโปรเจกต์ PHP แบบ modern

โปรเจกต์ PHP สมัยใหม่ควรแยกไฟล์ที่เปิดให้ผู้ใช้เข้าถึงออกจากโค้ดหลักของระบบ เพื่อให้ง่ายต่อการดูแลและเพิ่มความปลอดภัย

```text
php-modern-app/
├── composer.json
├── public/
│   └── index.php
├── src/
│   └── Support/
│       └── Greeter.php
└── vendor/
```

### สิ่งที่ควรเข้าใจ

- `public/` คือจุดเริ่มต้นของแอป
- `src/` ใช้เก็บ class และ logic หลัก
- `vendor/` ถูกสร้างโดย Composer

---

## หัวข้อย่อยที่ 5: Composer และ PSR-4 Autoload

Composer เป็นเครื่องมือสำคัญของ PHP ในการจัดการ package และ autoload ส่วน PSR-4 เป็นมาตรฐานยอดนิยมสำหรับการโหลด class อัตโนมัติ

### ตัวอย่าง `composer.json`

```json
{
  "name": "demo/php-modern-app",
  "description": "Example modern PHP project",
  "type": "project",
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

### สร้าง class ใน `src/Support/Greeter.php`

```php
<?php

declare(strict_types=1);

namespace App\Support;

class Greeter
{
    public function sayHello(string $name): string
    {
        return "Hello, {$name}";
    }
}
```

### เรียกใช้งานจาก `public/index.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Greeter;

$greeter = new Greeter();

 echo $greeter->sayHello("PHP Developer");
```

### สิ่งที่ควรเข้าใจ

- `namespace` ช่วยจัดระเบียบโค้ด
- `use` ช่วยเรียก class มาใช้งานง่ายขึ้น
- Composer จะช่วย autoload class ตามโครงสร้างโฟลเดอร์

---

## หัวข้อย่อยที่ 6: เริ่มโปรเจกต์จริงแบบง่าย

ตัวอย่างขั้นตอนเริ่มต้นโปรเจกต์

```bash
mkdir php-modern-app
cd php-modern-app
composer init
composer dump-autoload
```

จากนั้นสร้างโฟลเดอร์หลัก

```bash
mkdir public src
```

รัน local server

```bash
php -S localhost:8000 -t public
```

### สิ่งที่ควรเข้าใจ

- เหมาะสำหรับใช้พัฒนาและทดลองในเครื่อง
- local server ของ PHP ใช้สะดวกสำหรับเริ่มต้น
- production ควรใช้ web server ที่เหมาะสมกว่า

---

## หัวข้อย่อยที่ 7: ตัวอย่างโปรเจกต์เล็กที่พร้อมรัน

### `src/Support/Greeter.php`

```php
<?php

declare(strict_types=1);

namespace App\Support;

class Greeter
{
    public function welcome(string $name): string
    {
        return "Welcome, {$name}!";
    }
}
```

### `public/index.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Greeter;

// สร้าง object จาก class ใน src/
$greeter = new Greeter();

// แสดงผลลัพธ์ออกหน้าจอ
 echo $greeter->welcome("Modern PHP");
```

### ผลลัพธ์

```text
Welcome, Modern PHP!
```

---

## หัวข้อย่อยที่ 8: แนวคิดที่ควรติดตัวตั้งแต่วันแรก

การเริ่มต้นโปรเจกต์ให้ถูกทางตั้งแต่แรกจะช่วยให้ดูแลระบบง่ายขึ้นในระยะยาว โดยเฉพาะเมื่อโปรเจกต์เริ่มใหญ่ขึ้นหรือมีหลายคนร่วมพัฒนา

### Checklist ที่ควรใช้

- ใช้ `<?php` เป็นมาตรฐาน
- ใช้ `declare(strict_types=1);`
- แยก `public/` ออกจาก `src/`
- ใช้ Composer autoload
- ตั้งชื่อ class และ namespace ให้ชัดเจน
- เขียน function ให้สั้น กระชับ และรับผิดชอบงานชัดเจน

---

## สรุป

หัวข้อ **PHP Syntax and Modern Project Setup** เป็นจุดเริ่มต้นที่ดีสำหรับคนที่อยากเรียน PHP แบบใช้งานได้จริง เพราะครอบคลุมทั้ง syntax พื้นฐานและแนวทางตั้งต้นโปรเจกต์อย่างเป็นระบบ

เมื่อเข้าใจเนื้อหาชุดนี้แล้ว คุณจะพร้อมต่อยอดไปยังหัวข้อถัดไป เช่น

- PHP Variables and Data Types
- PHP Functions and Reusable Code
- PHP OOP Basics
- PHP + MySQL with PDO
- Error Handling and Validation

---

## Suggested File Name

- `README.md`
- `lesson-php-syntax-and-modern-project-setup.md`

