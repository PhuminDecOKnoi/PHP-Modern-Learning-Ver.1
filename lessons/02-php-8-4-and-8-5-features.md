# 02 — PHP 8.4 and PHP 8.5 Modern Features

## บทนำ

บทนี้สรุปฟีเจอร์สำคัญของ PHP 8.4 และ PHP 8.5 ที่เหมาะกับการเรียนและการนำไปใช้จริง พร้อมแยกให้ชัดว่าโค้ดใดใช้ได้ตั้งแต่ PHP 8.4 และโค้ดใดต้องใช้ PHP 8.5

## เป้าหมายการเรียนรู้

- ใช้ Property Hooks และ Asymmetric Visibility ใน PHP 8.4
- ใช้ `#[\Deprecated]` เพื่อสื่อสาร API lifecycle
- ใช้ array helper functions ของ PHP 8.4
- ใช้ Pipe Operator, URI API และ Clone With ใน PHP 8.5
- เข้าใจความเสี่ยงด้าน compatibility
- แยก shared code ออกจาก version-specific examples

---

# Part A — PHP 8.4

## 1. Property Hooks

Property Hooks ช่วยกำหนด logic ตอนอ่านหรือเขียน property โดยไม่ต้องสร้าง getter/setter แบบซ้ำจำนวนมาก

```php
<?php

declare(strict_types=1);

final class Employee
{
    public string $name {
        set {
            $cleanName = trim($value);

            if ($cleanName === '') {
                throw new InvalidArgumentException('Name is required.');
            }

            $this->name = $cleanName;
        }
    }
}

$employee = new Employee();
$employee->name = '  Phumin  ';

echo $employee->name; // Phumin
```

### เหมาะกับ

- normalization
- validation ที่ผูกกับ property
- computed properties

### ระวัง

- อย่าใส่ business workflow ซับซ้อนใน hook
- side effect ที่มองไม่เห็นอาจทำให้ debug ยาก
- ใช้ method ปกติเมื่อ operation มีความหมายมากกว่าการอ่าน/เขียน property

## 2. Asymmetric Visibility

กำหนดสิทธิ์อ่านและเขียนต่างกันได้ เช่น public read แต่ private set

```php
<?php

declare(strict_types=1);

final class AuditRecord
{
    public private(set) DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }
}

$record = new AuditRecord();
echo $record->createdAt->format(DATE_ATOM);

// $record->createdAt = new DateTimeImmutable();
// Error: แก้จากภายนอกไม่ได้
```

แนวคิดนี้ช่วยสร้าง object ที่อ่านสถานะได้ แต่จำกัดจุดที่แก้ไขข้อมูล

## 3. `#[\Deprecated]`

ใช้แจ้งผู้เรียกว่าฟังก์ชันหรือ method ไม่ควรถูกใช้ในโค้ดใหม่

```php
<?php

declare(strict_types=1);

final class ReportService
{
    #[\Deprecated(
        message: 'Use generatePdf() instead',
        since: '2.0'
    )]
    public function exportPdf(): string
    {
        return $this->generatePdf();
    }

    public function generatePdf(): string
    {
        return 'PDF generated';
    }
}
```

Deprecated ไม่ได้แปลว่าถูกลบทันที แต่เป็นสัญญาณให้เตรียม migration

## 4. Array Helper Functions

PHP 8.4 เพิ่มฟังก์ชันที่ช่วยค้นหาและตรวจเงื่อนไขใน array

```php
<?php

declare(strict_types=1);

$employees = [
    ['id' => 1, 'active' => false],
    ['id' => 2, 'active' => true],
    ['id' => 3, 'active' => true],
];

$firstActive = array_find(
    $employees,
    static fn(array $employee): bool => $employee['active']
);

$firstActiveKey = array_find_key(
    $employees,
    static fn(array $employee): bool => $employee['active']
);

$hasActiveEmployee = array_any(
    $employees,
    static fn(array $employee): bool => $employee['active']
);

$allActive = array_all(
    $employees,
    static fn(array $employee): bool => $employee['active']
);
```

ฟังก์ชันเหล่านี้ลด loop แบบ manual และช่วยให้ intent ชัดขึ้น

## 5. Implicitly Nullable Parameters

หลีกเลี่ยงรูปแบบเก่า:

```php
function findUser(string $email = null): void
{
}
```

ใช้ nullable type ให้ชัดเจน:

```php
function findUser(?string $email = null): void
{
}
```

การประกาศ type ที่ชัดช่วยลด deprecation และทำให้ static analysis เข้าใจโค้ดได้ดีขึ้น

---

# Part B — PHP 8.5

> ตัวอย่างในส่วนนี้ต้องใช้ PHP 8.5

## 6. Pipe Operator `|>`

Pipe Operator ส่งค่าจากซ้ายไปขวาผ่าน callable หลายขั้น ทำให้ data transformation อ่านตามลำดับได้

```php
<?php

declare(strict_types=1);

$title = '  Modern PHP 8.5 Course  ';

$slug = $title
    |> trim(...)
    |> strtolower(...)
    |> (static fn(string $value): string => str_replace(' ', '-', $value));

echo $slug;
```

ผลลัพธ์:

```text
modern-php-8.5-course
```

### แนวทางใช้

- เหมาะกับ transformation pipeline
- แต่ละ callable ควรรับค่าจากขั้นก่อนเป็น argument แรก
- อย่าใช้เพื่อทำให้ flow ธรรมดาดูซับซ้อนขึ้น

## 7. URI Extension

PHP 8.5 เพิ่ม URI API ที่รองรับมาตรฐาน URI/URL ชัดเจนกว่าการจัดการ string เอง

```php
<?php

declare(strict_types=1);

use Uri\Rfc3986\Uri;

$uri = new Uri('https://example.com/hr/audit?year=2026');

echo $uri->getHost(); // example.com
```

ประโยชน์:

- parsing ที่มีโครงสร้าง
- ลดการต่อ URL แบบ string ที่ผิดพลาดง่าย
- เหมาะกับ validation และ URL manipulation

## 8. Clone With

ใช้สร้าง object ใหม่จาก object เดิม พร้อมเปลี่ยนบาง property

```php
<?php

declare(strict_types=1);

readonly class CourseVersion
{
    public function __construct(
        public string $name,
        public string $status,
    ) {
    }

    public function publish(): self
    {
        return clone($this, [
            'status' => 'published',
        ]);
    }
}

$draft = new CourseVersion('PHP 8.5', 'draft');
$published = $draft->publish();
```

เหมาะกับ immutable objects และ with-er pattern

## 9. `#[\NoDiscard]`

ใช้เตือนเมื่อ return value สำคัญแต่ caller ไม่ได้นำไปใช้

```php
<?php

declare(strict_types=1);

#[\NoDiscard('The normalized value must be used')]
function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

$email = normalizeEmail(' USER@EXAMPLE.COM ');
```

กรณีตั้งใจไม่ใช้ค่า ให้แสดงเจตนาด้วย `(void)` ตามบริบทที่รองรับ

## 10. `array_first()` และ `array_last()`

```php
<?php

declare(strict_types=1);

$versions = ['8.4', '8.5'];

$first = array_first($versions);
$last = array_last($versions);
```

ถ้า array ว่าง จะได้ `null`

---

# Part C — Compatibility Strategy

## 11. อย่าใช้ PHP 8.5 Syntax ในไฟล์ที่ CI รันบน PHP 8.4

ตัวอย่าง Pipe Operator จะ parse ไม่ผ่านบน PHP 8.4 ดังนั้นควร:

- เก็บไว้ใน Markdown
- หรือแยก executable examples ตาม version
- หรือกำหนด CI job เฉพาะ PHP 8.5

ตัวอย่างโครงสร้าง:

```text
examples/
├── php84/
│   └── property-hooks.php
└── php85/
    └── pipe-operator.php
```

## 12. Feature Detection

ตรวจ extension หรือ function เมื่อ environment อาจต่างกัน:

```php
<?php

declare(strict_types=1);

if (!extension_loaded('pdo_mysql')) {
    throw new RuntimeException('pdo_mysql extension is required.');
}
```

อย่างไรก็ตาม syntax ของภาษาไม่สามารถป้องกันด้วย `function_exists()` ได้ หาก parser ของเวอร์ชันเก่าอ่าน syntax นั้นไม่เข้าใจ

## 13. Migration Checklist

- [ ] รัน tests บน PHP 8.4 และ 8.5
- [ ] ตรวจ deprecations ด้วย `E_ALL`
- [ ] แก้ nullable parameters ให้ชัด
- [ ] ตรวจ extensions และ library compatibility
- [ ] อ่าน migration guide ทางการ
- [ ] ไม่ deploy preview release
- [ ] อัปเดต patch release ก่อน production

## แบบฝึกหัด

1. เขียน class ที่ใช้ asymmetric visibility
2. สร้าง property hook สำหรับ normalize ชื่อพนักงาน
3. ใช้ `array_find()` ค้นหาพนักงานที่ active คนแรก
4. เขียน data pipeline ด้วย `|>` บน PHP 8.5
5. อธิบายว่าทำไมไฟล์ที่มี `|>` จึงไม่ควรถูก lint ด้วย PHP 8.4

## สรุป

PHP 8.4 เน้นการออกแบบ object และ property ที่ชัดเจนขึ้น ส่วน PHP 8.5 เพิ่มเครื่องมือสำหรับ data pipeline, URI, immutable object และ API safety การใช้ฟีเจอร์ใหม่ต้องมาคู่กับ version constraint, CI matrix และ migration plan

## References

- https://www.php.net/releases/8.4/en.php
- https://www.php.net/releases/8.5/en.php
- https://www.php.net/manual/en/migration84.php
- https://www.php.net/manual/en/migration85.php
