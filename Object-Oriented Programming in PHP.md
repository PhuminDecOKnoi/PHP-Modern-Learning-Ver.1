File Name: Object-Oriented Programming in PHP.md

# Object-Oriented Programming in PHP

## บทนำ

OOP หรือ **Object-Oriented Programming** คือแนวทางการเขียนโปรแกรมที่จัดโค้ดเป็น **class** และ **object** เพื่อให้โค้ดเป็นระบบ นำกลับมาใช้ซ้ำได้ และขยายต่อได้ง่าย ใน PHP ส่วนนี้อยู่ในหมวด **Classes and Objects** ของเอกสารทางการ และเป็นพื้นฐานสำคัญของงานพัฒนาเว็บสมัยใหม่ ไม่ว่าจะเป็น Laravel, Symfony หรือโปรเจกต์ PHP แบบ custom ก็ตาม

สำหรับ PHP ยุคปัจจุบัน แนวทางที่ควรใช้คือเขียน class ให้รับผิดชอบหน้าที่ชัดเจน, ใช้ type declarations, ใช้ visibility ให้เหมาะสม, แยกโค้ดด้วย namespace และเลือกใช้ interface หรือ trait ตามวัตถุประสงค์จริงของงาน

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจความหมายของ class และ object
- รู้จัก properties, methods และ visibility
- ใช้ constructor และ property promotion ได้
- เข้าใจ inheritance, abstract class และ interface
- ใช้ trait เพื่อทำ reusable behavior
- จัดโครงสร้างโค้ดด้วย namespace แบบ modern
- เห็นแนวทางเขียน OOP ที่ใช้งานได้จริงในโปรเจกต์ PHP

---

## หัวข้อย่อยที่ 1: Class และ Object คืออะไร

`class` คือแม่แบบของข้อมูลและพฤติกรรม ส่วน `object` คือ instance ที่ถูกสร้างจาก class นั้น โดยใน PHP เราสร้าง object ด้วยคำสั่ง `new`

ตัวอย่างง่ายที่สุดคือการสร้าง class สำหรับเก็บข้อมูลผู้ใช้และเรียก method เพื่อแสดงข้อความ

```php
<?php

declare(strict_types=1);

// สร้าง class สำหรับแทนข้อมูลผู้ใช้
class User
{
    public string $name = 'Guest';

    // method สำหรับแสดงคำทักทาย
    public function greet(): string
    {
        return "Hello, {$this->name}";
    }
}

// สร้าง object จาก class
$user = new User();
$user->name = 'Phumin';

echo $user->greet();
```

สิ่งที่ควรเข้าใจ:
- `class` คือโครงสร้าง
- `object` คือสิ่งที่ถูกสร้างจาก class
- `$this` ใช้อ้างถึง object ปัจจุบันภายใน class

---

## หัวข้อย่อยที่ 2: Properties และ Methods

ใน OOP ข้อมูลที่อยู่ใน class เรียกว่า **properties** และพฤติกรรมเรียกว่า **methods** โดย properties ของ PHP สามารถประกาศ modifier ได้ เช่น visibility, `static` และรองรับ type declaration สำหรับ properties ตั้งแต่ PHP 7.4 เป็นต้นมา

```php
<?php

declare(strict_types=1);

class Product
{
    // property ของสินค้า
    public string $name;
    public float $price;

    // method สำหรับคำนวณราคาพร้อม VAT
    public function getPriceWithVat(): float
    {
        return $this->price * 1.07;
    }
}

$product = new Product();
$product->name = 'Notebook';
$product->price = 100.00;

echo $product->name . PHP_EOL;
echo $product->getPriceWithVat();
```

สิ่งที่ควรเข้าใจ:
- property ใช้เก็บสถานะของ object
- method ใช้กำหนดพฤติกรรม
- type declarations ทำให้โค้ดชัดเจนและลดความผิดพลาด

---

## หัวข้อย่อยที่ 3: Visibility — `public`, `protected`, `private`

PHP รองรับ visibility สำหรับ properties และ methods คือ `public`, `protected` และ `private` โดยถ้าไม่ระบุ visibility สำหรับ method จะถือเป็น `public` และสำหรับ property ต้องกำหนด modifier อย่างน้อยหนึ่งตัว

แนวคิดสำคัญคือ **ซ่อนรายละเอียดภายใน class เท่าที่จำเป็น** แล้วเปิดเฉพาะส่วนที่ควรให้ภายนอกเรียกใช้ได้จริง

```php
<?php

declare(strict_types=1);

class BankAccount
{
    // เปิดให้เรียกอ่านได้จากภายนอก
    public string $owner;

    // ซ่อนยอดเงินไว้ภายใน class
    private float $balance = 0;

    public function __construct(string $owner, float $balance)
    {
        $this->owner = $owner;
        $this->balance = $balance;
    }

    public function deposit(float $amount): void
    {
        $this->balance += $amount;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }
}

$account = new BankAccount('Phumin', 1000);
$account->deposit(500);

echo $account->getBalance();
```

สิ่งที่ควรเข้าใจ:
- `public` ใช้กับสิ่งที่ภายนอกต้องเรียกได้
- `private` ใช้กับข้อมูลหรือ logic ภายใน
- การซ่อนข้อมูลช่วยป้องกันการแก้ไขค่าแบบไม่ตั้งใจ

---

## หัวข้อย่อยที่ 4: Constructor และ Constructor Property Promotion

PHP มี `__construct()` สำหรับกำหนดค่าตอนสร้าง object และในงาน modern PHP มักใช้ **Constructor Property Promotion** เพื่อเขียนโค้ดให้สั้นลง อ่านง่ายขึ้น

```php
<?php

declare(strict_types=1);

class Employee
{
    public function __construct(
        public string $name,
        public string $position,
        private float $salary
    ) {
        // constructor นี้จะกำหนดค่าให้ property โดยอัตโนมัติ
    }

    public function getSalary(): float
    {
        return $this->salary;
    }
}

$employee = new Employee('Anna', 'HRBP', 45000);

echo $employee->name . PHP_EOL;
echo $employee->getSalary();
```

สิ่งที่ควรเข้าใจ:
- constructor ใช้เตรียม object ให้พร้อมใช้งาน
- property promotion ช่วยลดโค้ดซ้ำ
- object ที่ถูกออกแบบดีควรถูกสร้างมาแล้วใช้งานได้ทันที

---

## หัวข้อย่อยที่ 5: `readonly` Property สำหรับข้อมูลที่ไม่ควรถูกแก้ไข

PHP รองรับ `readonly` property ตั้งแต่ PHP 8.1 ซึ่งจะไม่อนุญาตให้แก้ไขค่าหลังจาก initialize แล้ว และ `readonly` ใช้ได้กับ typed properties เท่านั้น

กรณีนี้เหมาะกับข้อมูลที่ควรคงที่ เช่น ID, email หรือ createdAt

```php
<?php

declare(strict_types=1);

class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $email
    ) {
    }
}

$customer = new Customer(1, 'user@example.com');

echo $customer->id . PHP_EOL;
echo $customer->email;

// โค้ดนี้จะ error ถ้าพยายามแก้ไข
// $customer->id = 2;
```

สิ่งที่ควรเข้าใจ:
- `readonly` ช่วยป้องกันการเปลี่ยนค่าที่ไม่ควรเปลี่ยน
- เหมาะกับ object ที่มีลักษณะ immutable บางส่วน
- ใช้แล้วโค้ดอ่านเจตนาได้ชัดขึ้น

---

## หัวข้อย่อยที่ 6: Inheritance และ Abstract Class

PHP รองรับ inheritance เพื่อให้ child class สืบทอด behavior จาก parent class ได้ ส่วน **abstract class** คือ class ที่ไม่สามารถ instantiate ได้โดยตรง และใช้เป็นแม่แบบสำหรับ class ลูกที่ต้อง implement บางส่วนต่อเอง

```php
<?php

declare(strict_types=1);

// abstract class ใช้เป็นโครงพื้นฐาน
abstract class Report
{
    public function export(): string
    {
        return $this->buildHeader() . PHP_EOL . $this->buildBody();
    }

    abstract protected function buildBody(): string;

    protected function buildHeader(): string
    {
        return '=== Report ===';
    }
}

class SalesReport extends Report
{
    protected function buildBody(): string
    {
        return 'Sales data for this month';
    }
}

$report = new SalesReport();
echo $report->export();
```

สิ่งที่ควรเข้าใจ:
- ใช้ inheritance เมื่อ class มีความสัมพันธ์แบบ “เป็นชนิดหนึ่งของ”
- ใช้ abstract class เมื่ออยากมีโครงร่วมและบังคับบาง method
- อย่าใช้ inheritance เพียงเพื่อ reuse code ถ้าความสัมพันธ์ไม่ชัด

---

## หัวข้อย่อยที่ 7: Interface สำหรับกำหนดสัญญาการทำงาน

Interface ใช้กำหนดว่า class ต้องมี method อะไรบ้าง โดยไม่บอกวิธี implement เหมาะกับงานที่ต้องการสลับ implementation ได้ง่าย

```php
<?php

declare(strict_types=1);

interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo "[FILE] {$message}";
    }
}

class DatabaseLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        echo "[DB] {$message}";
    }
}

function writeLog(LoggerInterface $logger, string $message): void
{
    // รับ object ที่ทำตาม contract เดียวกัน
    $logger->log($message);
}

writeLog(new FileLogger(), 'System started');
```

สิ่งที่ควรเข้าใจ:
- interface คือ contract
- ช่วยให้สลับ implementation ได้ง่าย
- เหมาะกับงานที่ต้องรองรับหลายรูปแบบ เช่น payment, logger, notifier

---

## หัวข้อย่อยที่ 8: Trait สำหรับ Reusable Behavior

Trait เป็นกลไกสำหรับ **reuse code** ในภาษาแบบ single inheritance อย่าง PHP และไม่สามารถ instantiate ได้ด้วยตัวเอง จุดเด่นคือใช้แชร์พฤติกรรมข้ามหลาย class ได้โดยไม่ต้องบังคับให้มีความสัมพันธ์แบบ parent-child

```php
<?php

declare(strict_types=1);

trait HasTimestamps
{
    public function currentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}

class OrderService
{
    use HasTimestamps;

    public function createOrder(): void
    {
        echo "Order created at " . $this->currentTimestamp();
    }
}

class InvoiceService
{
    use HasTimestamps;

    public function createInvoice(): void
    {
        echo "Invoice created at " . $this->currentTimestamp();
    }
}

$orderService = new OrderService();
$orderService->createOrder();
```

สิ่งที่ควรเข้าใจ:
- trait เหมาะกับ reusable behavior ขนาดเล็ก
- ใช้เพื่อลดโค้ดซ้ำระหว่างหลาย class
- ไม่ควรใส่ logic ใหญ่เกินไปจน trait กลายเป็น class แฝง

---

## หัวข้อย่อยที่ 9: Namespace และการจัดโครงสร้างโปรเจกต์

Namespace ใช้แยกชื่อของ class, interface, function และ constants เพื่อไม่ให้ชนกันในโปรเจกต์ขนาดใหญ่

ในงานจริง มักแยกโค้ดไว้ในโฟลเดอร์ `src/` และตั้ง namespace ให้สอดคล้องกับโครงสร้างไฟล์ เพื่อให้จัดการง่ายและพร้อมใช้ร่วมกับ Composer autoload

```php
<?php

declare(strict_types=1);

namespace App\Services;

class Mailer
{
    public function send(string $email, string $message): void
    {
        echo "Send mail to {$email}: {$message}";
    }
}
```

ตัวอย่างการเรียกใช้งานจากไฟล์อื่น:

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
        $mailer->send('user@example.com', 'Welcome to our system');
    }
}
```

สิ่งที่ควรเข้าใจ:
- namespace ช่วยจัดระเบียบ class
- `use` ช่วย import class มาใช้งาน
- เป็นพื้นฐานสำคัญของโปรเจกต์ PHP แบบ modern

---

## หัวข้อย่อยที่ 10: ตัวอย่าง OOP แบบใช้งานจริง

ตัวอย่างนี้รวมหลายแนวคิดเข้าด้วยกัน ได้แก่ constructor, encapsulation, interface และ dependency injection แบบง่าย

```php
<?php

declare(strict_types=1);

interface PaymentGatewayInterface
{
    public function charge(float $amount): bool;
}

class MockPaymentGateway implements PaymentGatewayInterface
{
    public function charge(float $amount): bool
    {
        // จำลองการจ่ายเงินสำเร็จ
        return $amount > 0;
    }
}

class OrderProcessor
{
    public function __construct(
        private PaymentGatewayInterface $paymentGateway
    ) {
    }

    public function process(float $amount): string
    {
        if ($this->paymentGateway->charge($amount)) {
            return "Payment successful";
        }

        return "Payment failed";
    }
}

$processor = new OrderProcessor(new MockPaymentGateway());
echo $processor->process(1500);
```

สิ่งที่ควรเข้าใจ:
- class หนึ่งไม่ควรรู้รายละเอียดทุกอย่างเอง
- inject dependency ผ่าน constructor ทำให้ทดสอบง่ายและยืดหยุ่น
- interface ช่วยให้เปลี่ยน implementation ได้โดยไม่กระทบ logic หลัก

---

## แนวทางเขียน OOP แบบ professional

โค้ด OOP ที่ดีไม่ใช่แค่ “ใช้ class ให้เป็น” แต่ต้องออกแบบให้ **อ่านง่าย**, **ทดสอบได้**, และ **เปลี่ยนแปลงได้ง่าย** แนวทางที่ควรเริ่มใช้ตั้งแต่ต้นคือ:
- ให้ 1 class รับผิดชอบงานหลักเพียงเรื่องเดียว
- ซ่อนข้อมูลภายในด้วย `private` หรือ `protected` ตามความเหมาะสม
- ใช้ interface เมื่ออยากกำหนด contract
- ใช้ trait เฉพาะส่วนที่เป็น reusable behavior จริง
- ใช้ namespace และตั้งชื่อ class ให้สื่อความหมาย
- ประกาศ property ให้ชัดเจน และหลีกเลี่ยง dynamic properties

---

## สรุป

OOP ใน PHP คือพื้นฐานสำคัญของการพัฒนาโปรเจกต์สมัยใหม่ เพราะช่วยให้โค้ดเป็นระบบ ขยายง่าย และรองรับการทำงานร่วมกันในทีมได้ดี เมื่อคุณเข้าใจ `class`, `object`, `visibility`, `constructor`, `inheritance`, `interface`, `trait` และ `namespace` แล้ว คุณจะพร้อมต่อยอดไปสู่หัวข้อระดับสูงกว่า เช่น dependency injection, design patterns, testing และ architecture ของระบบจริงได้อย่างมั่นคง
