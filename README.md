# PHP Modern Learning — PHP 8.4–8.5 Edition

[![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-13-3C9CD7)](https://phpunit.de/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP CI](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml/badge.svg)](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml)

บทเรียนภาษา PHP แบบ **professional, modern และใช้งานได้จริง** สำหรับผู้เริ่มต้น ผู้พัฒนาเว็บ และผู้สอนที่ต้องการเนื้อหาแบบ GitHub-ready พร้อมตัวอย่างโค้ดที่อ่านง่าย มี comment ภาษาไทย และอ้างอิงแนวทางปัจจุบัน

> **Course baseline:** ใช้ PHP 8.5 เป็นเวอร์ชันหลักในการสอน รองรับ PHP 8.4 เป็นขั้นต่ำ และใช้ PHP 8.6 เฉพาะการติดตามรุ่นทดสอบ ไม่ใช้ใน production

---

## Version Policy

| Component | Course baseline | หมายเหตุ |
|---|---:|---|
| PHP | `8.5` | เวอร์ชันหลักของบทเรียน |
| Minimum PHP | `8.4` | เวอร์ชันต่ำสุดที่รองรับ |
| PHP Preview | `8.6 alpha/beta` | ศึกษาแนวโน้มเท่านั้น |
| PHPUnit | `13.x` | ต้องใช้ PHP 8.4 ขึ้นไป |
| Composer | `2.x` | ใช้จัดการ dependencies และ PSR-4 autoload |
| MySQL | `8.0+` | แนะนำให้ใช้รุ่นที่ผู้ให้บริการยังสนับสนุน |

รายละเอียดนโยบายเวอร์ชันอยู่ที่ [`docs/version-policy.md`](docs/version-policy.md)

---

## จุดประสงค์ของ Repository

Repository นี้จัดทำขึ้นเพื่อ:

- ปูพื้นฐาน PHP อย่างถูกต้องและเป็นระบบ
- เชื่อมจาก syntax ไปสู่ OOP, Composer, PDO, API, Security และ Testing
- ส่งเสริม clean code, type safety และการแยกความรับผิดชอบของโค้ด
- ใช้เป็น lesson note สำหรับผู้สอนและผู้เรียน
- ใช้เป็น GitHub portfolio หรือ knowledge base
- รองรับการตรวจสอบตัวอย่างด้วย GitHub Actions

---

## เส้นทางการเรียนรู้หลัก

เริ่มเรียนจากโฟลเดอร์ [`lessons/`](lessons/) ตามลำดับนี้:

| ลำดับ | บทเรียน | เป้าหมาย |
|---:|---|---|
| 00 | [PHP 8.4–8.5 Course Baseline](lessons/00-php-8-4-8-5-course-baseline.md) | เข้าใจเวอร์ชันและ environment |
| 01 | [Modern Project Setup](lessons/01-modern-project-setup.md) | เริ่มโปรเจกต์ด้วย Composer และ PSR-4 |
| 02 | [PHP 8.4 and 8.5 Features](lessons/02-php-8-4-and-8-5-features.md) | ใช้ฟีเจอร์ใหม่อย่างถูกต้อง |
| 03 | [Secure PDO and MySQL](lessons/03-secure-pdo-and-mysql.md) | ต่อฐานข้อมูลแบบปลอดภัย |
| 04 | [REST API and Input Validation](lessons/04-rest-api-and-input-validation.md) | สร้าง JSON API และจัดการ error |
| 05 | [Authentication Security](lessons/05-authentication-security.md) | password hashing, session และ CSRF |
| 06 | [Testing with PHPUnit 13](lessons/06-testing-with-phpunit-13.md) | เขียนและรัน automated tests |

บทเรียนเดิมที่อยู่ใน root repository ยังคงเก็บไว้เพื่อรักษาประวัติและลิงก์เดิม โดยชุดใน `lessons/` ถือเป็น **canonical course edition** สำหรับ PHP 8.4–8.5

---

## หัวข้อเดิมที่มีอยู่ใน Repository

- Object-Oriented Programming in PHP
- Namespaces and Autoloading with Composer
- Forms and Validation in PHP
- PHP with MySQL using PDO
- Error Handling and Exceptions in PHP
- REST API Basics with PHP
- Authentication and Password Hashing in PHP
- PHP Testing with PHPUnit
- Clean Code and Project Structure in PHP

เนื้อหาเดิมเหมาะสำหรับทบทวนแนวคิด ส่วนการเริ่มโปรเจกต์ใหม่ให้ยึด version policy และตัวอย่างใน `lessons/`

---

## Requirements

ตรวจสอบเครื่องมือก่อนเริ่ม:

```bash
php -v
composer --version
```

ค่าที่คาดหวัง:

```text
PHP 8.4.x หรือ PHP 8.5.x
Composer 2.x
```

ติดตั้ง dependencies:

```bash
composer install
```

ตรวจสอบความถูกต้องของ `composer.json`:

```bash
composer validate --strict
```

---

## Project Structure

```text
PHP-Modern-Learning-Ver.1/
├── .github/
│   └── workflows/
│       └── php-ci.yml
├── docs/
│   └── version-policy.md
├── lessons/
│   ├── 00-php-8-4-8-5-course-baseline.md
│   ├── 01-modern-project-setup.md
│   ├── 02-php-8-4-and-8-5-features.md
│   ├── 03-secure-pdo-and-mysql.md
│   ├── 04-rest-api-and-input-validation.md
│   ├── 05-authentication-security.md
│   └── 06-testing-with-phpunit-13.md
├── composer.json
├── CHANGELOG.md
├── LICENSE
└── README.md
```

---

## Coding Standard

ตัวอย่างใหม่ยึดหลักดังนี้:

```php
<?php

declare(strict_types=1);
```

- ใช้ PSR-4 autoloading
- จัดรูปแบบตาม PSR-12
- ระบุ parameter และ return types เมื่อเหมาะสม
- ใช้ prepared statements กับข้อมูลจากผู้ใช้
- escape output ก่อนแสดงใน HTML
- ไม่เก็บ secret หรือ password ใน source code
- ไม่แสดง exception detail ใน production
- เขียน tests สำหรับ business logic ที่สำคัญ

---

## Composer Configuration

Repository กำหนด PHP platform ไว้ที่:

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

ข้อกำหนดนี้ช่วยป้องกันการติดตั้ง dependencies บน PHP ที่เก่ากว่าหลักสูตรหรือบน PHP preview ที่ยังไม่ได้รับรอง

---

## Testing

รัน PHPUnit:

```bash
composer test
```

ตรวจ syntax ของไฟล์ PHP:

```bash
composer lint
```

GitHub Actions จะตรวจบน PHP 8.4 และ PHP 8.5 ทุกครั้งที่ push หรือเปิด pull request

---

## Security Principles

- Validation ต้องทำที่ server เสมอ
- ใช้ `password_hash()` และ `password_verify()`
- ใช้ `session_regenerate_id(true)` หลัง login
- ตั้งค่า session cookie ด้วย `HttpOnly`, `Secure` และ `SameSite`
- ใช้ CSRF token กับคำขอที่เปลี่ยนแปลงข้อมูล
- ใช้ `JSON_THROW_ON_ERROR` เมื่อ decode JSON
- เก็บ secrets ผ่าน environment variables
- ปิด `display_errors` ใน production และเปิด error logging

---

## Migration

ผู้ที่มีโปรเจกต์ PHP รุ่นเก่า ควรอ่าน [`MIGRATION.md`](MIGRATION.md) ก่อนนำตัวอย่างไปใช้ โดยเฉพาะเรื่อง:

- deprecated syntax
- implicitly nullable parameters
- PHPUnit major version
- session security
- JSON error handling
- PDO configuration

---

## References

- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [PHP 8.4 Release](https://www.php.net/releases/8.4/en.php)
- [PHP 8.5 Release](https://www.php.net/releases/8.5/en.php)
- [PHP Manual](https://www.php.net/manual/en/)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHP-FIG Standards](https://www.php-fig.org/psr/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

---

## License

เผยแพร่ภายใต้ [MIT License](LICENSE)

## Author

**Phumin Decoknoi**  
GitHub: [`PhuminDecOKnoi`](https://github.com/PhuminDecOKnoi)

---

> เรียนให้เข้าใจโครงสร้าง เขียนให้ปลอดภัย และทดสอบก่อนนำขึ้น production
