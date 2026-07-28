# PHP Version Policy

อัปเดตล่าสุด: 28 กรกฎาคม 2026

เอกสารนี้กำหนด baseline ของบทเรียนใน repository เพื่อให้ผู้เรียน ผู้สอน และผู้ร่วมพัฒนาตีความคำว่า **modern PHP** ตรงกัน

## 1. Supported Course Versions

| สถานะ | เวอร์ชัน | การใช้งานในหลักสูตร |
|---|---:|---|
| Primary | PHP 8.5 | ใช้เป็นเวอร์ชันหลักของตัวอย่างใหม่ |
| Minimum | PHP 8.4 | ตัวอย่าง canonical ต้องรันได้ |
| Compatibility only | PHP 8.3 | ใช้กล่าวถึงการย้ายระบบเก่า ไม่รับรองตัวอย่างใหม่ทั้งหมด |
| Security-only legacy | PHP 8.2 | ไม่ใช้เป็นฐานสร้างโปรเจกต์ใหม่ |
| Preview | PHP 8.6 alpha/beta/RC | ใช้ทดลองและศึกษาการเปลี่ยนแปลงเท่านั้น |
| Unsupported | PHP 8.1 หรือต่ำกว่า | ไม่อยู่ในขอบเขตหลักสูตรรุ่นนี้ |

## 2. Support Window

PHP แต่ละ minor branch มีช่วง active support และ security support ตามนโยบายของโครงการ PHP ผู้เรียนควรตรวจหน้าทางการก่อน deploy ทุกครั้ง เพราะวันที่และ patch release เปลี่ยนแปลงได้

แหล่งตรวจสอบหลัก:

- https://www.php.net/supported-versions.php
- https://www.php.net/

## 3. Patch Version Principle

ตัวอย่างในหลักสูตรจะอ้างอิง minor version เช่น `8.4` หรือ `8.5` แต่ผู้ใช้งานจริงต้องติดตั้ง **patch release ล่าสุด** ของ branch นั้นเสมอ

ตัวอย่าง:

```text
ถูกต้อง: ใช้ PHP 8.5.x รุ่นล่าสุดที่ได้รับ security fixes
ไม่แนะนำ: ตรึง production ไว้ที่ PHP 8.5.0 โดยไม่อัปเดต
```

## 4. Composer Constraint

Repository กำหนด:

```json
{
  "require": {
    "php": ">=8.4 <8.6"
  }
}
```

เหตุผล:

- ปฏิเสธ PHP ต่ำกว่า 8.4 ซึ่งไม่ใช่ baseline ของหลักสูตร
- ป้องกันการติดตั้งบน PHP 8.6 preview โดยไม่ตั้งใจ
- ทำให้ dependency resolver เลือก package ที่เข้ากับ environment จริง

เมื่อ PHP 8.6 stable และบทเรียนผ่านการตรวจสอบแล้ว จึงค่อยปรับ constraint ใน major course update ถัดไป

## 5. PHPUnit Policy

| PHPUnit | PHP Requirement | สถานะในหลักสูตร |
|---|---:|---|
| PHPUnit 13 | PHP 8.4+ | ค่าเริ่มต้น |
| PHPUnit 12 | PHP 8.3+ | ใช้สำหรับ migration note |
| PHPUnit 11 หรือต่ำกว่า | แตกต่างตามรุ่น | ไม่ใช้ในตัวอย่าง canonical ใหม่ |

คำสั่งติดตั้งที่ใช้ในหลักสูตร:

```bash
composer require --dev phpunit/phpunit:^13.2
```

## 6. Database Policy

บทเรียนใช้ PDO เป็น database abstraction หลัก และใช้ MySQL เป็นตัวอย่าง

ข้อกำหนดขั้นต่ำเชิงแนวทาง:

- PDO extension
- PDO MySQL driver สำหรับตัวอย่าง MySQL
- MySQL 8.0+ หรือบริการ MySQL-compatible ที่ยังได้รับการสนับสนุน
- character set `utf8mb4`
- prepared statements
- transactions เมื่อมีหลายคำสั่งที่ต้องสำเร็จหรือย้อนกลับร่วมกัน

## 7. Coding Policy

ตัวอย่าง canonical ต้อง:

- เริ่มด้วย `declare(strict_types=1);`
- ใช้ typed parameters และ return types เมื่อเหมาะสม
- ใช้ PSR-4 autoloading
- ไม่ใช้ deprecated syntax โดยไม่มีหมายเหตุ
- ไม่ hard-code credentials หรือ secrets
- ใช้ exceptions และ logging อย่างแยกความรับผิดชอบ
- มี comment ภาษาไทยเฉพาะจุดที่อธิบายเหตุผลหรือพฤติกรรมสำคัญ

## 8. Production Safety

รุ่น alpha, beta และ release candidate ไม่ใช่ production baseline

ก่อน deploy ต้องตรวจ:

1. PHP patch release ล่าสุด
2. dependency security advisories
3. test suite
4. production configuration เช่น `display_errors=Off`
5. backup และ rollback plan
6. compatibility ของ hosting, extension และ database driver

## 9. Review Cadence

ควรทบทวนเอกสารนี้อย่างน้อย:

- เมื่อ PHP minor version ใหม่เป็น stable
- เมื่อ PHPUnit major version ใหม่ออก
- เมื่อ supported-version window เปลี่ยน
- เมื่อ security practice สำคัญเปลี่ยน
- อย่างน้อยปีละ 2 ครั้ง
