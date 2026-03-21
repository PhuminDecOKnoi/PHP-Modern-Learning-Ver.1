File Name: Namespaces and Autoloading with Composer.md

# Namespaces and Autoloading with Composer

## บทนำ

หัวข้อนี้เป็นพื้นฐานสำคัญของ **modern PHP** เพราะเมื่อโปรเจกต์เริ่มมีหลายไฟล์ หลาย class และหลาย module การตั้งชื่อ class แบบไม่มีระบบจะทำให้ชื่อชนกันและดูแลยาก `namespace` จึงเข้ามาช่วยจัดระเบียบโค้ด ส่วน Composer ช่วยจัดการ dependencies และสร้าง autoloader ให้เราไม่ต้อง `require` class ทีละไฟล์

ถ้าคุณอยากเขียน PHP ให้ใกล้เคียงงานจริงมากขึ้น หัวข้อนี้คือจุดเปลี่ยนจาก “เขียนสคริปต์ธรรมดา” ไปสู่ “เขียนโปรเจกต์แบบ professional”

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจว่า `namespace` คืออะไร และช่วยแก้ปัญหาอะไร
- รู้วิธีประกาศ namespace ให้ถูกต้อง
- ใช้ `use` และ alias เพื่อนำ class มาใช้งานอย่างอ่านง่าย
- ตั้งค่า Composer autoload แบบ PSR-4 ได้
- รู้จัก `vendor/autoload.php` และวิธีเรียกใช้งาน
- จัดโครงสร้างโปรเจกต์ให้พร้อมต่อยอดในงานจริง
- เข้าใจแนวทางใช้งาน dev และ production เบื้องต้น

---

## หัวข้อย่อยที่ 1: ทำไมต้องใช้ Namespace

เมื่อโปรเจกต์มีหลาย class เช่น `User`, `Mailer`, `Logger` หรือ `Controller` โอกาสที่ชื่อจะซ้ำกันมีสูงมาก โดยเฉพาะเมื่อใช้ package จากภายนอกด้วย `namespace` ช่วยแยกกลุ่มของ class, interface, function และ constants ออกจากกัน ทำให้ชื่อไม่ชนกัน และช่วยให้โครงสร้างโปรเจกต์ชัดเจนขึ้น

ในงานจริง namespace มักสะท้อนโครงสร้างของระบบ เช่น `App\Controllers`, `App\Services`, `App\Models`

```php
<?php

declare(strict_types=1);

namespace App\Services;

class Mailer
{
    public function send(string $email, string $message): void
    {
        // จำลองการส่งอีเมล
        echo "Send to {$email}: {$message}";
    }
}
```

สิ่งที่ควรเข้าใจ:
- `namespace App\Services;` ใช้ระบุว่าคลาสนี้อยู่ในกลุ่ม `App\Services`
- class ชื่อเดียวกันสามารถมีอยู่คนละ namespace ได้
- namespace ช่วยให้โปรเจกต์ขยายต่อได้ง่ายขึ้น

---

## หัวข้อย่อยที่ 2: การประกาศ Namespace ให้ถูกต้อง

ไฟล์ที่มี namespace ต้องประกาศ `namespace` ไว้ด้านบนของไฟล์ และก่อน code อื่นทั้งหมด ยกเว้น `declare` ซึ่งสามารถมาก่อนได้ ดังนั้นใน PHP สมัยใหม่เรามักเห็นรูปแบบนี้บ่อยมาก: `declare(strict_types=1);` แล้วตามด้วย `namespace ...;`

```php
<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public function __construct(
        public string $name,
        public string $email
    ) {
    }
}
```

สิ่งที่ควรเข้าใจ:
- ลำดับที่แนะนำคือ `<?php` → `declare(...)` → `namespace ...`
- อย่าใส่ HTML หรือ output ก่อน `namespace`
- 1 ไฟล์ควรมีหน้าที่ชัดเจน และโดยทั่วไปควรมี 1 class หลักต่อ 1 ไฟล์

---

## หัวข้อย่อยที่ 3: การเรียกใช้งาน Class จาก Namespace อื่น

เมื่อ class อยู่คนละ namespace เราสามารถเรียกแบบ fully qualified name ได้ หรือใช้ `use` เพื่อ import ชื่อมาใช้งานให้อ่านง่ายขึ้น ซึ่งเป็นแนวทางที่นิยมกว่าในโปรเจกต์จริง

### แบบใช้ชื่อเต็ม

```php
<?php

declare(strict_types=1);

namespace App\Http;

class RegisterController
{
    public function store(): void
    {
        $mailer = new \App\Services\Mailer();

        // เรียกใช้งาน class จาก namespace อื่นด้วยชื่อเต็ม
        $mailer->send('user@example.com', 'Welcome');
    }
}
```

### แบบใช้ `use`

```php
<?php

declare(strict_types=1);

namespace App\Http;

use App\Services\Mailer;

class RegisterController
{
    public function store(): void
    {
        $mailer = new Mailer();

        // อ่านง่ายกว่า และนิยมใช้มากกว่า
        $mailer->send('user@example.com', 'Welcome');
    }
}
```

สิ่งที่ควรเข้าใจ:
- `use` ช่วยลดความยาวของชื่อ class
- โค้ดจะอ่านง่ายขึ้นเมื่อ import class ไว้ด้านบน
- ใช้ fully qualified name ได้ แต่ไม่เหมาะหากต้องใช้ซ้ำหลายครั้ง

---

## หัวข้อย่อยที่ 4: Alias ด้วย `as`

ถ้าชื่อ class ยาวเกินไป หรือมี class ชื่อซ้ำจากคนละ namespace เราสามารถตั้ง alias ได้ด้วย `as`

```php
<?php

declare(strict_types=1);

namespace App\Reports;

use App\Services\Mailer as AppMailer;

class WeeklyReport
{
    public function send(): void
    {
        $mailer = new AppMailer();

        // ใช้ alias เพื่อให้ชื่อสั้นและชัดเจนขึ้น
        $mailer->send('team@example.com', 'Weekly report is ready');
    }
}
```

สิ่งที่ควรเข้าใจ:
- alias เหมาะเมื่อชื่อชนกันหรือชื่อยาวเกินไป
- `as` ช่วยให้โค้ดชัดเจนขึ้นในไฟล์ที่ import หลาย class
- ควรตั้ง alias ให้สื่อความหมาย ไม่สั้นจนเดาไม่ออก

---

## หัวข้อย่อยที่ 5: โครงสร้างโปรเจกต์ที่แนะนำ

เมื่อใช้ Composer และ namespace แบบ PSR-4 โครงสร้างโปรเจกต์ควรอ่านง่ายและสะท้อนบทบาทของแต่ละส่วน เช่นเก็บ source code ใน `src/` และให้ `public/` เป็น entry point ของแอป

```text
php-modern-app/
├── composer.json
├── public/
│   └── index.php
├── src/
│   ├── Http/
│   │   └── RegisterController.php
│   └── Services/
│       └── Mailer.php
└── vendor/
```

สิ่งที่ควรเข้าใจ:
- `src/` ใช้เก็บ business logic และ class หลัก
- `public/` ใช้เก็บไฟล์ที่ web server ควรเข้าถึง
- `vendor/` ถูกสร้างโดย Composer ไม่ควรแก้ไขด้วยมือ

---

## หัวข้อย่อยที่ 6: Composer คืออะไร

Composer คือเครื่องมือสำหรับจัดการ dependencies ของ PHP และยังช่วยสร้าง autoloader ให้กับทั้ง package ภายนอกและ class ของเราเอง ทำให้เราไม่ต้อง `require` ทีละไฟล์เหมือนวิธีเก่า

ตัวอย่างเริ่มต้นโปรเจกต์:

```bash
composer init
```

หลังจากนั้นเราจะได้ไฟล์ `composer.json` ซึ่งเป็นศูนย์กลางของการตั้งค่าโปรเจกต์

ตัวอย่าง:

```json
{
  "name": "demo/php-modern-app",
  "type": "project",
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

สิ่งที่ควรเข้าใจ:
- `composer.json` ใช้กำหนด package และการตั้งค่า autoload
- `App\\` คือ namespace prefix
- `src/` คือโฟลเดอร์ที่ map เข้ากับ prefix นั้น

---

## หัวข้อย่อยที่ 7: PSR-4 Autoload ทำงานอย่างไร

PSR-4 คือมาตรฐานการ map namespace ไปยัง directory เช่น ถ้าเรากำหนดว่า `App\\` ชี้ไปที่ `src/` หมายความว่า class `App\Services\Mailer` จะถูกมองหาที่ไฟล์ `src/Services/Mailer.php`

### ตัวอย่างไฟล์ `src/Services/Mailer.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

class Mailer
{
    public function send(string $email, string $message): void
    {
        // จำลองการส่งอีเมล
        echo "Send to {$email}: {$message}";
    }
}
```

### ตัวอย่างไฟล์ `public/index.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\Mailer;

$mailer = new Mailer();
$mailer->send('user@example.com', 'Hello from Composer autoload');
```

สิ่งที่ควรเข้าใจ:
- `vendor/autoload.php` คือจุดเริ่มต้นของ autoload
- เมื่อ class และไฟล์ตรงตาม PSR-4 Composer จะหาไฟล์ให้เอง
- ไม่ต้อง `require 'src/Services/Mailer.php';` ด้วยมือ

---

## หัวข้อย่อยที่ 8: เมื่อแก้ `composer.json` ต้องทำอะไรต่อ

หลังเพิ่มหรือแก้ส่วน `autoload` ใน `composer.json` เราควรสั่งให้ Composer สร้าง autoload ใหม่ด้วยคำสั่งนี้

```bash
composer dump-autoload
```

หรือบาง environment อาจใช้:

```bash
php composer.phar dump-autoload
```

สิ่งที่ควรเข้าใจ:
- ถ้าเพิ่ม namespace ใหม่แล้ว class ยังไม่ถูกโหลด ให้เช็กว่าได้รัน `dump-autoload` แล้วหรือยัง
- ชื่อ namespace กับ path ต้องตรงกัน
- ตัวพิมพ์เล็ก-ใหญ่ของชื่อไฟล์ควรสอดคล้องกับชื่อ class โดยเฉพาะบน Linux

---

## หัวข้อย่อยที่ 9: Autoload สำหรับ Functions

โดยทั่วไป autoload เหมาะกับ class มากที่สุด แต่ถ้าเรามีไฟล์ functions ที่ต้อง include ทุกครั้ง Composer ก็รองรับ `autoload.files` ได้เช่นกัน

ตัวอย่าง `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    },
    "files": [
      "src/helpers.php"
    ]
  }
}
```

ตัวอย่าง `src/helpers.php`:

```php
<?php

declare(strict_types=1);

function appName(): string
{
    // helper function แบบง่าย
    return 'PHP Modern App';
}
```

ตัวอย่างเรียกใช้งาน:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

echo appName();
```

สิ่งที่ควรเข้าใจ:
- `files` เหมาะกับ helper functions ที่ไม่อยู่ใน class
- ใช้เท่าที่จำเป็น เพราะ class-based code ดูแลง่ายกว่าในระยะยาว
- ถ้าโปรเจกต์ใหญ่ ควรใช้ services หรือ utility classes แทน helper จำนวนมาก

---

## หัวข้อย่อยที่ 10: ตัวอย่างโปรเจกต์เล็กที่พร้อมรัน

### `composer.json`

```json
{
  "name": "demo/php-modern-app",
  "type": "project",
  "require": {},
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

### `src/Services/Greeter.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

class Greeter
{
    public function hello(string $name): string
    {
        return "Hello, {$name}";
    }
}
```

### `public/index.php`

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Services\Greeter;

$greeter = new Greeter();

echo $greeter->hello('Phumin');
```

### คำสั่งสำหรับเริ่มใช้งาน

```bash
composer install
composer dump-autoload
php -S localhost:8000 -t public
```

เมื่อเปิด `http://localhost:8000` คุณควรเห็นผลลัพธ์:

```php
<?php
// Output ที่คาดหวัง
// Hello, Phumin
```

---

## หัวข้อย่อยที่ 11: Best Practices ที่ควรใช้ตั้งแต่ต้น

แนวทางที่ดีสำหรับโปรเจกต์ PHP สมัยใหม่คือ:

- ใช้ `declare(strict_types=1);` ในไฟล์หลักของแอป
- ใช้ namespace ให้สอดคล้องกับโครงสร้างโฟลเดอร์
- ใช้ Composer autoload แทนการ `require` class เอง
- ให้ `public/` เป็น entry point ของระบบ
- ใช้ `autoload.files` เฉพาะกรณีจำเป็นจริง
- ใน production สามารถ optimize autoloader ได้ แต่ไม่ควรเปิด optimization นี้ใน development เพราะจะทำให้เพิ่มหรือลบ class ระหว่างพัฒนาไม่สะดวก

---

## สรุป

**Namespaces and Autoloading with Composer** คือหัวใจของการจัดโครงสร้างโปรเจกต์ PHP แบบ modern เพราะช่วยให้โค้ดเป็นระบบ ลดปัญหาชื่อชนกัน และทำให้โหลด class ได้อัตโนมัติอย่างสะอาดและดูแลง่าย เมื่อคุณเข้าใจ `namespace`, `use`, alias, `composer.json`, PSR-4 และ `vendor/autoload.php` แล้ว คุณจะพร้อมต่อยอดไปยังหัวข้อระดับสูงกว่า เช่น MVC structure, dependency injection, package development และ framework-based architecture

ถัดจากบทนี้ หัวข้อที่เหมาะมากคือ:
- `Error Handling and Exceptions in PHP`
- `PHP Forms and Input Validation`
- `Working with Files and JSON in PHP`
