# 01 — Modern PHP Project Setup

## บทนำ

บทนี้สอนการเริ่มโปรเจกต์ PHP แบบ modern โดยใช้ Composer, PSR-4 autoloading, `src/`, `tests/` และ public entry point แทนการวางทุกอย่างไว้ในไฟล์เดียว

## เป้าหมายการเรียนรู้

- สร้าง `composer.json` ได้
- เข้าใจ `require` และ `require-dev`
- ใช้ PSR-4 autoloading
- แยก source code, tests และ public files
- เก็บค่าลับนอก source code
- รันคำสั่งตรวจสอบพื้นฐานได้

## 1. โครงสร้างที่แนะนำ

```text
my-php-app/
├── public/
│   └── index.php
├── src/
│   └── Service/
│       └── GreetingService.php
├── tests/
│   └── Service/
│       └── GreetingServiceTest.php
├── .env.example
├── .gitignore
├── composer.json
└── phpunit.xml
```

### หน้าที่ของแต่ละส่วน

| Path | หน้าที่ |
|---|---|
| `public/` | ไฟล์ที่ web server เข้าถึงได้โดยตรง |
| `src/` | source code หลักของ application |
| `tests/` | automated tests |
| `vendor/` | dependencies ที่ Composer ติดตั้ง |
| `.env` | environment-specific secrets; ไม่ commit |

## 2. สร้าง Composer Project

```bash
composer init
```

ตัวอย่าง `composer.json`:

```json
{
  "name": "demo/my-php-app",
  "type": "project",
  "require": {
    "php": ">=8.4 <8.6"
  },
  "require-dev": {
    "phpunit/phpunit": "^13.2"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Tests\\": "tests/"
    }
  }
}
```

ติดตั้ง dependencies:

```bash
composer install
```

สร้าง autoloader ใหม่หลังแก้ namespace mapping:

```bash
composer dump-autoload
```

## 3. สร้าง Class แรก

ไฟล์ `src/Service/GreetingService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Service;

final class GreetingService
{
    public function greet(string $name): string
    {
        $cleanName = trim($name);

        if ($cleanName === '') {
            throw new \InvalidArgumentException('Name is required.');
        }

        return "Hello, {$cleanName}";
    }
}
```

หลักสำคัญ:

- namespace ต้องสัมพันธ์กับ PSR-4 path
- class มีหน้าที่หลักชัดเจน
- ใช้ type declarations
- ใช้ exception กับ input ที่ผิดเงื่อนไข

## 4. Public Entry Point

ไฟล์ `public/index.php`:

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Service\GreetingService;

$service = new GreetingService();
$message = $service->greet('PHP Learner');

header('Content-Type: text/plain; charset=utf-8');
echo $message;
```

รัน development server:

```bash
php -S 127.0.0.1:8000 -t public
```

> PHP built-in server เหมาะกับ development และการทดลอง ไม่ใช่ production server

## 5. Environment Variables

ตัวอย่าง `.env.example`:

```dotenv
APP_ENV=local
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=app_db
DB_USER=app_user
DB_PASSWORD=change-me
```

ข้อควรจำ:

- commit เฉพาะ `.env.example`
- ไม่ commit `.env` จริง
- ไม่เขียน password, token หรือ API key ลง README และ source code
- production ควรใช้ secret manager หรือ environment configuration ของ platform

## 6. `.gitignore`

```gitignore
/vendor/
/.env
/.phpunit.cache/
/.idea/
/.vscode/
.DS_Store
```

โดยทั่วไปควร commit:

```text
composer.json
composer.lock
```

`composer.lock` ช่วยให้ทีมและ CI ติดตั้ง dependency versions ชุดเดียวกัน

## 7. Coding Style

ตัวอย่างใหม่ควรยึด PSR-12 และจัดลำดับไฟล์ดังนี้:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;

final class ExampleService
{
    // class body
}
```

## 8. คำสั่งตรวจสอบ

```bash
composer validate --strict
composer check-platform-reqs
composer lint
composer test
```

## 9. Anti-patterns ที่ควรหลีกเลี่ยง

```php
<?php

// ไม่แนะนำ: hard-code secret และรวมหลายหน้าที่ไว้ในไฟล์เดียว
$password = 'real-production-password';
$pdo = new PDO('mysql:host=localhost;dbname=app', 'root', $password);

// รับ input, query, business logic และ HTML อยู่ด้วยกันทั้งหมด
```

ควรแยกเป็น configuration, database factory, repository/service และ view/response ตามขนาดของระบบ

## แบบฝึกหัด

1. สร้าง class `PriceCalculator` ใน `src/Service/`
2. เพิ่ม method คำนวณราคารวมจากราคาและจำนวน
3. กำหนด type ให้ครบ
4. โยน exception เมื่อราคาเป็นค่าติดลบ
5. สร้าง test ใน `tests/Service/`

## สรุป

Modern project setup ทำให้โปรเจกต์คาดการณ์ได้ง่าย ลดการ `require` แบบกระจัดกระจาย แยก public surface ออกจาก source code และรองรับการทำงานร่วมกันผ่าน Composer และ CI

## References

- https://getcomposer.org/doc/
- https://www.php-fig.org/psr/psr-4/
- https://www.php-fig.org/psr/psr-12/
