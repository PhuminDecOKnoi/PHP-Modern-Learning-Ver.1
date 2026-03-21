# Functions and Reusable Code in PHP

## บทนำ

หัวข้อนี้เป็นพื้นฐานสำคัญของการเขียน PHP แบบ professional เพราะเมื่อโปรเจกต์เริ่มโตขึ้น เราไม่ควรเขียนโค้ดทุกอย่างไว้ในไฟล์เดียวหรือทำงานซ้ำ ๆ หลายครั้ง แต่ควรแยก logic ออกเป็น **functions** ที่อ่านง่าย ทดสอบได้ และนำกลับมาใช้ซ้ำได้

บทเรียนนี้เหมาะสำหรับผู้เริ่มต้นและผู้ที่ต้องการวางพื้นฐานให้ถูกต้องตามแนวทาง PHP ยุคปัจจุบัน โดยจะค่อย ๆ พาเรียนตั้งแต่การประกาศฟังก์ชันพื้นฐาน ไปจนถึงการจัดโค้ด reusable ด้วยการแยกไฟล์, ใช้ `require_once`, และเลือกใช้ anonymous functions หรือ arrow functions ให้เหมาะกับงาน

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจโครงสร้างของฟังก์ชันใน PHP
- ใช้ parameters, return types และ `strict_types` ได้ถูกต้อง
- เขียนฟังก์ชันให้สั้น อ่านง่าย และนำกลับมาใช้ซ้ำได้
- รู้จัก default parameters และ variadic functions
- เข้าใจ anonymous functions และ arrow functions
- แยก reusable code ออกเป็นไฟล์อย่างเป็นระบบ

---

## หัวข้อย่อยที่ 1: ฟังก์ชันคืออะไร และทำไมจึงสำคัญ

ฟังก์ชันคือชุดคำสั่งที่เราตั้งชื่อไว้ เพื่อเรียกใช้งานซ้ำเมื่อจำเป็น แทนที่จะเขียน logic เดิมซ้ำหลายครั้ง การใช้ฟังก์ชันช่วยให้โค้ดสั้นลง อ่านง่ายขึ้น และดูแลรักษาได้ง่ายกว่าเดิม

แนวคิดสำคัญคือ ฟังก์ชันที่ดีควรทำหน้าที่ชัดเจนเพียงอย่างเดียว เช่น แปลงข้อความ, คำนวณราคา, ตรวจสอบข้อมูล หรือสร้างข้อความตอบกลับ ไม่ควรทำหลายหน้าที่ปนกันจนอ่านยาก

```php
<?php

// ฟังก์ชันพื้นฐานสำหรับทักทายผู้ใช้
function greetUser(string $name): string
{
    return "Hello, {$name}";
}

echo greetUser("Phumin");
```

สิ่งที่ควรเข้าใจ:
- `function` ใช้สำหรับประกาศฟังก์ชัน
- `string $name` คือ parameter พร้อม type hint
- `: string` หมายถึงฟังก์ชันนี้ต้องคืนค่าเป็น string

---

## หัวข้อย่อยที่ 2: Parameters และ Return Types

ฟังก์ชันใน PHP สามารถรับข้อมูลเข้าไปทำงานผ่าน parameters และส่งผลลัพธ์กลับมาผ่าน return value ได้ การกำหนดชนิดข้อมูลให้ชัดเจนช่วยให้โค้ดอ่านง่ายและลดความผิดพลาด

```php
<?php

declare(strict_types=1);

// ฟังก์ชันคำนวณราคารวม
function calculateTotal(float $price, int $quantity): float
{
    return $price * $quantity;
}

echo calculateTotal(99.50, 3);
```

สิ่งที่ควรเข้าใจ:
- `float $price` บอกว่าค่าราคาควรเป็นเลขทศนิยม
- `int $quantity` ใช้กับจำนวนเต็ม
- การกำหนด return type ช่วยให้รู้ล่วงหน้าว่าฟังก์ชันจะคืนค่าอะไร

---

## หัวข้อย่อยที่ 3: `strict_types` เพื่อให้โค้ดคาดเดาได้ง่ายขึ้น

ใน PHP เราสามารถใช้ `declare(strict_types=1);` เพื่อให้การทำงานกับ scalar types เข้มงวดขึ้นในระดับไฟล์ ซึ่งเหมาะกับงาน modern PHP เพราะช่วยจับข้อผิดพลาดได้เร็วและลดปัญหาจากการแปลงชนิดข้อมูลแบบไม่ตั้งใจ

```php
<?php

declare(strict_types=1);

function add(int $a, int $b): int
{
    return $a + $b;
}

echo add(10, 20);
```

ตัวอย่างที่ควรระวัง:

```php
<?php

declare(strict_types=1);

function add(int $a, int $b): int
{
    return $a + $b;
}

// โค้ดนี้ไม่ควรทำ เพราะส่ง string แทน int
// echo add("10", 20);
```

สิ่งที่ควรเข้าใจ:
- `strict_types` ทำให้โค้ดชัดเจนขึ้น
- เหมาะมากเมื่อทำงานร่วมกับทีม หรือโปรเจกต์ที่ต้องการลด bug
- ควรใช้ร่วมกับ type hints ให้เป็นนิสัย

---

## หัวข้อย่อยที่ 4: Default Parameters และฟังก์ชันที่ยืดหยุ่น

บางครั้งเราอยากให้ฟังก์ชันรับค่าแบบมีค่าเริ่มต้นได้ เพื่อให้เรียกใช้งานสะดวกขึ้น เช่น ถ้าไม่ส่งค่าเข้ามา ก็ใช้ค่ามาตรฐานแทน

```php
<?php

declare(strict_types=1);

// หากไม่ส่ง currency เข้ามา จะใช้ "THB" เป็นค่าเริ่มต้น
function formatPrice(float $amount, string $currency = "THB"): string
{
    return number_format($amount, 2) . " " . $currency;
}

echo formatPrice(1500);
echo PHP_EOL;
echo formatPrice(1500, "USD");
```

สิ่งที่ควรเข้าใจ:
- default parameter ช่วยให้ฟังก์ชันใช้งานง่ายขึ้น
- เหมาะกับค่าที่มีมาตรฐานชัดเจน เช่น language, currency, status
- ไม่ควรใส่ default มากเกินไปจนฟังก์ชันเริ่มสับสน

---

## หัวข้อย่อยที่ 5: Variadic Functions เมื่อต้องรับหลายค่า

ถ้าจำนวน arguments ไม่แน่นอน เราสามารถใช้ variadic syntax `...` เพื่อรับหลายค่าเข้ามาในรูปแบบ array ได้ เหมาะกับฟังก์ชันแนวรวมผลลัพธ์หรือสรุปข้อมูล

```php
<?php

declare(strict_types=1);

// รับตัวเลขได้หลายค่า
function sumNumbers(int ...$numbers): int
{
    return array_sum($numbers);
}

echo sumNumbers(10, 20, 30, 40);
```

สิ่งที่ควรเข้าใจ:
- `int ...$numbers` หมายถึงรับค่า int ได้หลายตัว
- ภายในฟังก์ชัน ตัวแปร `$numbers` จะเป็น array
- เหมาะกับงานที่จำนวน input เปลี่ยนไปตามสถานการณ์

---

## หัวข้อย่อยที่ 6: เขียนฟังก์ชันให้ Reusable และอ่านง่าย

ฟังก์ชันที่ reusable ควรมีหน้าที่เดียวและไม่ผูกกับรายละเอียดที่ไม่จำเป็น เช่น ถ้าฟังก์ชันมีหน้าที่แค่แปลงชื่อผู้ใช้ ก็ไม่ควรมี logic เกี่ยวกับ database หรือ HTML ปนอยู่ในฟังก์ชันเดียวกัน

ลองดูตัวอย่างเปรียบเทียบ:

### ตัวอย่างที่ไม่ค่อยดี

```php
<?php

function processUserData(string $name, float $salary): string
{
    // ฟังก์ชันนี้ทำหลายอย่างเกินไป ทั้ง trim, format, สร้างข้อความ
    $name = trim($name);
    $salary = number_format($salary, 2);

    return "Employee: {$name}, Salary: {$salary} THB";
}
```

### ตัวอย่างที่อ่านง่ายกว่า

```php
<?php

declare(strict_types=1);

function normalizeName(string $name): string
{
    return trim($name);
}

function formatSalary(float $salary): string
{
    return number_format($salary, 2) . " THB";
}

function buildEmployeeLabel(string $name, float $salary): string
{
    return "Employee: " . normalizeName($name) . ", Salary: " . formatSalary($salary);
}

echo buildEmployeeLabel("  Phumin  ", 35000);
```

สิ่งที่ควรเข้าใจ:
- แยกหน้าที่ของแต่ละฟังก์ชันให้ชัด
- ฟังก์ชันเล็ก ๆ มักทดสอบง่ายกว่า
- reusable code เกิดจากการแยก logic ให้เป็นส่วน ๆ

---

## หัวข้อย่อยที่ 7: Variable Scope ภายในฟังก์ชัน

ตัวแปรที่สร้างภายในฟังก์ชันจะอยู่ใน scope ของฟังก์ชันนั้น จึงไม่ควรคาดหวังว่าจะใช้งานจากภายนอกได้โดยตรง การเข้าใจ scope ช่วยลดความสับสนและทำให้ฟังก์ชันมีพฤติกรรมที่คาดเดาได้ง่าย

```php
<?php

declare(strict_types=1);

$message = "Global message";

function showMessage(): void
{
    // ตัวแปรนี้อยู่เฉพาะในฟังก์ชัน
    $localMessage = "Local message";
    echo $localMessage;
}

showMessage();
```

สิ่งที่ควรเข้าใจ:
- ตัวแปรภายในฟังก์ชันมีขอบเขตของตัวเอง
- ยิ่งลดการพึ่งพา global state ได้มาก โค้ดยิ่งดูแลง่าย
- ควรส่งข้อมูลผ่าน parameter และคืนค่าผ่าน return เป็นหลัก

---

## หัวข้อย่อยที่ 8: Anonymous Functions และ Closures

anonymous functions คือฟังก์ชันที่ไม่มีชื่อ มักใช้เมื่อเราต้องส่งฟังก์ชันเข้าไปเป็น callback เช่น ใช้กับ `array_map()`, `array_filter()` หรือกรณีที่ต้องการ logic สั้น ๆ ในจุดเดียว

```php
<?php

declare(strict_types=1);

$numbers = [1, 2, 3, 4, 5];

// ใช้ anonymous function เพื่อคูณค่าทุกตัวด้วย 2
$doubled = array_map(function (int $number): int {
    return $number * 2;
}, $numbers);

print_r($doubled);
```

สิ่งที่ควรเข้าใจ:
- anonymous function เหมาะกับ callback
- ใช้ได้ดีเมื่อ logic ไม่ยาวมาก
- หากฟังก์ชันเริ่มยาว ควรแยกเป็น named function หรือ class method

---

## หัวข้อย่อยที่ 9: Arrow Functions สำหรับงานสั้นและกระชับ

arrow functions เหมาะกับกรณีที่ logic สั้นมากและต้องการเขียนให้กระชับขึ้น โดยรูปแบบจะสั้นกว่า anonymous function และอ่านง่ายในงานที่ไม่ซับซ้อน

```php
<?php

declare(strict_types=1);

$prices = [100, 250, 500];

// เพิ่ม VAT 7% ให้กับราคาทุกตัว
$pricesWithVat = array_map(
    fn (float $price): float => $price * 1.07,
    $prices
);

print_r($pricesWithVat);
```

สิ่งที่ควรเข้าใจ:
- arrow functions เหมาะกับ expression สั้น ๆ
- ช่วยลด boilerplate เมื่อเทียบกับ closure แบบปกติ
- ใช้เมื่อโค้ดต้องการความกระชับและยังอ่านเข้าใจง่าย

---

## หัวข้อย่อยที่ 10: แยก Reusable Code ออกเป็นไฟล์

เมื่อโปรเจกต์เริ่มมีหลายฟังก์ชัน เราควรแยก utility functions ออกเป็นไฟล์เฉพาะ แล้วเรียกใช้งานผ่าน `require_once` เพื่อป้องกันการ include ซ้ำโดยไม่จำเป็น

### ตัวอย่างโครงสร้างไฟล์

```text
project/
├── public/
│   └── index.php
└── src/
    └── helpers.php
```

### `src/helpers.php`

```php
<?php

declare(strict_types=1);

function formatUsername(string $name): string
{
    return strtolower(trim($name));
}

function isAdult(int $age): bool
{
    return $age >= 18;
}
```

### `public/index.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$username = formatUsername('  PHUMIN  ');
$status = isAdult(20) ? 'Adult' : 'Minor';

echo $username . PHP_EOL;
echo $status;
```

สิ่งที่ควรเข้าใจ:
- แยกไฟล์ช่วยให้โค้ดเป็นระเบียบ
- `require_once` เหมาะเมื่อไฟล์นั้นจำเป็นต้องมีแน่นอน
- utility functions ควรถูกจัดกลุ่มตามหน้าที่ ไม่ควรกองรวมทุกอย่างในไฟล์เดียวเมื่อโปรเจกต์ใหญ่ขึ้น

---

## หัวข้อย่อยที่ 11: ตัวอย่างใช้งานจริงแบบย่อ

ตัวอย่างนี้แสดงแนวคิด reusable code แบบใกล้เคียงงานจริง โดยแยกฟังก์ชันตรวจสอบข้อมูลและสร้างข้อความตอบกลับออกจากกัน

```php
<?php

declare(strict_types=1);

function normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function isCompanyEmail(string $email): bool
{
    return str_ends_with($email, '@company.com');
}

function buildAccessMessage(string $email): string
{
    $email = normalizeEmail($email);

    if (isCompanyEmail($email)) {
        return "Access granted for {$email}";
    }

    return "Access denied for {$email}";
}

echo buildAccessMessage('  USER@company.com  ');
```

สิ่งที่ควรเข้าใจ:
- แยก validation ออกจาก formatting
- ฟังก์ชันย่อยช่วยให้ logic หลักอ่านง่ายขึ้น
- โค้ดลักษณะนี้ต่อยอดไป testing ได้ง่าย

---

## หัวข้อย่อยที่ 12: Best Practices สำหรับ Functions ใน PHP

การเขียนฟังก์ชันให้ดีไม่ใช่แค่ทำให้โค้ดรันได้ แต่ต้องทำให้คนอื่นอ่านและดูแลต่อได้ด้วย โดยเฉพาะในโปรเจกต์จริงที่มีหลายคนร่วมพัฒนา

แนวทางที่ควรยึด:
- ตั้งชื่อฟังก์ชันให้สื่อความหมาย เช่น `calculateTotal()` ดีกว่า `doWork()`
- ให้หนึ่งฟังก์ชันทำหน้าที่หลักเพียงเรื่องเดียว
- ใช้ type hints และ return types ให้ชัดเจน
- ใช้ `strict_types` เมื่อเหมาะสม
- หากฟังก์ชันยาวเกินไป ควรแยกย่อยออกเป็นหลายฟังก์ชัน
- หาก logic เริ่มซับซ้อนมาก อาจพิจารณาย้ายไปใช้ class แทน

---

## สรุป

หัวข้อ **Functions and Reusable Code in PHP** คือจุดเปลี่ยนสำคัญจากการเขียนโค้ดแบบเริ่มต้น ไปสู่การเขียนโค้ดแบบมืออาชีพ เพราะฟังก์ชันช่วยให้เราแยก logic ออกเป็นส่วนที่อ่านง่าย ใช้งานซ้ำได้ และดูแลได้ในระยะยาว

เมื่อคุณเข้าใจการใช้ parameters, return types, `strict_types`, closures, arrow functions และการแยกไฟล์ reusable code แล้ว คุณจะพร้อมต่อยอดไปยังหัวข้อที่ใหญ่ขึ้น เช่น OOP, services, dependency injection, และ testing ได้อย่างมั่นคง
