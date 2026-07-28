# 00 — PHP 8.4–8.5 Course Baseline

## บทนำ

บทนี้กำหนด environment กลางของหลักสูตร เพื่อให้ตัวอย่างโค้ด การติดตั้ง Composer และการทดสอบทำงานสอดคล้องกันทั้งเครื่องผู้เรียนและ GitHub Actions

หลักสูตรรุ่นนี้ใช้:

```text
Primary: PHP 8.5
Minimum: PHP 8.4
Testing: PHPUnit 13
Dependency manager: Composer 2
Database examples: PDO + MySQL 8.0+
```

## เป้าหมายการเรียนรู้

เมื่อจบบทนี้ ผู้เรียนควรสามารถ:

- ตรวจสอบ PHP และ Composer ในเครื่องได้
- เข้าใจความต่างระหว่าง major, minor และ patch version
- เลือก stable release แทน preview release สำหรับ production
- เข้าใจเหตุผลของ Composer version constraint
- เตรียม extension ที่จำเป็นสำหรับบทเรียนถัดไป

## 1. ตรวจสอบ PHP

```bash
php -v
```

ตัวอย่างผลลัพธ์:

```text
PHP 8.5.x (cli)
```

ตรวจ extension:

```bash
php -m
```

extension ที่ใช้บ่อยในหลักสูตร:

```text
json
mbstring
openssl
pdo
pdo_mysql
```

> ชื่อ extension และวิธีติดตั้งอาจต่างกันตามระบบปฏิบัติการและผู้ให้บริการ hosting

## 2. ตรวจสอบ Composer

```bash
composer --version
```

หลักสูตรใช้ Composer 2 และกำหนด dependency แบบ project-local เพื่อให้สมาชิกทีมติดตั้งเวอร์ชันเดียวกันจาก `composer.json`

## 3. Semantic Versioning แบบเข้าใจง่าย

ตัวอย่าง `8.5.8`:

| ส่วน | ความหมาย |
|---|---|
| `8` | Major version |
| `5` | Minor version |
| `8` | Patch release |

การเรียนสามารถอ้างอิง PHP 8.5 แต่ production ควรใช้ patch release ล่าสุดของ branch 8.5 เสมอ

## 4. Stable กับ Preview

รุ่น alpha, beta และ RC มีไว้ทดสอบ compatibility และแจ้ง bug ไม่ควรใช้กับระบบ production

```text
เหมาะสำหรับ production: PHP 8.4.x, PHP 8.5.x รุ่น stable
ใช้ทดลองเท่านั้น: PHP 8.6.0 Alpha/Beta/RC
```

## 5. Composer Constraint

ไฟล์ `composer.json` ของ repository กำหนด:

```json
{
  "require": {
    "php": ">=8.4 <8.6"
  }
}
```

ความหมาย:

- `>=8.4` ต้องใช้ PHP 8.4 ขึ้นไป
- `<8.6` ยังไม่รับรอง PHP 8.6 จนกว่าจะ stable และผ่านการทดสอบหลักสูตร

ตรวจ compatibility:

```bash
composer check-platform-reqs
```

## 6. Strict Types

ตัวอย่าง canonical เริ่มด้วย:

```php
<?php

declare(strict_types=1);

function calculateTotal(float $price, int $quantity): float
{
    return $price * $quantity;
}
```

`strict_types` ช่วยให้การส่ง scalar type ที่ไม่ตรงกันถูกตรวจอย่างเข้มขึ้นในไฟล์ที่ประกาศใช้งาน ลดพฤติกรรมแปลงชนิดแบบคาดเดายาก

## 7. Development กับ Production

### Development

```ini
display_errors=On
error_reporting=E_ALL
log_errors=On
```

### Production

```ini
display_errors=Off
error_reporting=E_ALL
log_errors=On
```

Production ต้อง log รายละเอียดภายใน แต่ไม่ควรแสดง stack trace, database credentials หรือ path ภายในระบบต่อผู้ใช้

## 8. Checklist ก่อนเริ่ม

- [ ] `php -v` แสดง PHP 8.4 หรือ 8.5
- [ ] `composer --version` แสดง Composer 2
- [ ] มี `json`, `pdo` และ `pdo_mysql` สำหรับบทฐานข้อมูล
- [ ] รัน `composer install` สำเร็จ
- [ ] รัน `composer validate --strict` สำเร็จ
- [ ] เข้าใจว่า PHP 8.6 preview ไม่ใช่ production baseline

## สรุป

Version baseline ไม่ใช่เพียงข้อมูลประกอบ แต่เป็นส่วนหนึ่งของ software governance เพราะช่วยควบคุม compatibility, security updates, dependency resolution และความสม่ำเสมอของทีมพัฒนา

## References

- https://www.php.net/
- https://www.php.net/supported-versions.php
- https://getcomposer.org/doc/
- https://phpunit.de/supported-versions.html
