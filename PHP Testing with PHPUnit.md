File Name: PHP Testing with PHPUnit.md

# PHP Testing with PHPUnit

## บทนำ

การทดสอบด้วย **PHPUnit** คือพื้นฐานสำคัญของการพัฒนา PHP แบบ professional เพราะช่วยให้เราตรวจสอบได้ว่าโค้ดทำงานถูกต้อง, ลด regression, และทำให้ refactor ได้อย่างมั่นใจมากขึ้น โดยในงานจริง PHPUnit มักถูกใช้ร่วมกับ Composer, โครงสร้าง `src/` และ `tests/`, และรันผ่าน CLI หรือ CI pipeline

บทเรียนนี้เหมาะสำหรับผู้ที่เริ่มเขียน test ใน PHP และต้องการแนวทางแบบ modern ที่อ่านง่าย ใช้งานได้จริง และพร้อมนำไปใช้ต่อบน GitHub หรือในโปรเจกต์จริง

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจว่าทำไมการเขียน test จึงสำคัญ
- ติดตั้ง PHPUnit ผ่าน Composer ได้
- สร้าง test class แรกและรัน test ได้
- ใช้ assertions ที่สำคัญได้อย่างถูกต้อง
- ทดสอบ exception และใช้ `setUp()` ได้
- ใช้ Data Provider เพื่อลดโค้ดซ้ำ
- ตั้งค่า `phpunit.xml` เบื้องต้นได้
- เข้าใจ code coverage และ best practices สำหรับงานจริง

---

## หัวข้อย่อยที่ 1: ทำไมต้องเขียน Test

การเขียน test ไม่ได้มีเป้าหมายแค่ “เช็กว่าโค้ดรันได้” แต่ช่วยยืนยันว่า logic สำคัญยังคงถูกต้องเมื่อมีการแก้ไขในอนาคต โดยเฉพาะในโปรเจกต์ที่มีหลายไฟล์ หลายคน หรือมีการ deploy บ่อย ๆ

แนวคิดพื้นฐานที่ควรจำคือ test ที่ดีควรอ่านง่าย, มีขอบเขตชัดเจน, และตรวจสอบพฤติกรรมเพียงเรื่องเดียวในแต่ละ test method

```php
<?php

declare(strict_types=1);

final class Calculator
{
    public function add(int $a, int $b): int
    {
        // คืนค่าผลบวกของตัวเลข 2 ค่า
        return $a + $b;
    }
}
```

สิ่งที่ควรเข้าใจ:
- production code คือโค้ดที่ระบบใช้งานจริง
- test code คือโค้ดที่ใช้ตรวจสอบ production code
- ยิ่ง logic สำคัญมาก ยิ่งควรมี test รองรับ

---

## หัวข้อย่อยที่ 2: ติดตั้ง PHPUnit ด้วย Composer

ในโปรเจกต์ PHP สมัยใหม่ นิยมติดตั้ง PHPUnit เป็น **development dependency** ผ่าน Composer เพื่อให้เวอร์ชันของเครื่องมือถูกควบคุมไว้ในโปรเจกต์ และรันได้เหมือนกันทั้งทีม

```bash
composer require --dev phpunit/phpunit
```

หลังติดตั้งแล้ว โดยทั่วไปจะมีโครงสร้างประมาณนี้

```text
project/
├── composer.json
├── composer.lock
├── src/
├── tests/
└── vendor/
```

ตรวจสอบการติดตั้งได้ด้วยคำสั่ง:

```bash
./vendor/bin/phpunit --version
```

สิ่งที่ควรเข้าใจ:
- `--dev` หมายถึง dependency สำหรับการพัฒนา
- ควร commit ทั้ง `composer.json` และ `composer.lock`
- ควรใช้ PHPUnit แบบ local ในโปรเจกต์ แทนการพึ่ง global install

---

## หัวข้อย่อยที่ 3: โครงสร้างไฟล์สำหรับเริ่มต้น

แนวทางที่อ่านง่ายและใช้งานจริง คือแยก source code ไว้ใน `src/` และแยก test ไว้ใน `tests/` ให้ชัดเจน

```text
project/
├── src/
│   └── Calculator.php
├── tests/
│   └── CalculatorTest.php
└── vendor/
```

ตัวอย่างไฟล์ `src/Calculator.php`

```php
<?php

declare(strict_types=1);

namespace App;

final class Calculator
{
    public function add(int $a, int $b): int
    {
        // คืนค่าผลรวม
        return $a + $b;
    }
}
```

สิ่งที่ควรเข้าใจ:
- แยก test ออกจาก production code ให้ชัด
- ชื่อไฟล์ test มักลงท้ายด้วย `Test.php`
- โครงสร้างที่ดีช่วยให้ PHPUnit ค้นหาและจัดการ test ได้ง่ายขึ้น

---

## หัวข้อย่อยที่ 4: เขียน Test แรกด้วย `TestCase`

ใน PHPUnit test class จะสืบทอดจาก `PHPUnit\Framework\TestCase` และ test method สามารถตั้งชื่อขึ้นต้นด้วย `test` หรือใช้ attribute `#[Test]` ก็ได้

ตัวอย่างไฟล์ `tests/CalculatorTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CalculatorTest extends TestCase
{
    #[Test]
    public function it_adds_two_numbers(): void
    {
        $calculator = new Calculator();

        // เรียกใช้งาน method ที่ต้องการทดสอบ
        $result = $calculator->add(2, 3);

        // ตรวจสอบว่าผลลัพธ์ตรงตามที่คาดหวัง
        $this->assertSame(5, $result);
    }
}
```

สิ่งที่ควรเข้าใจ:
- `TestCase` คือฐานของ test class
- `assertSame()` เหมาะกับการตรวจทั้งค่าและชนิดข้อมูล
- ชื่อ test ควรสื่อให้เข้าใจว่า “กำลังทดสอบพฤติกรรมอะไร”

---

## หัวข้อย่อยที่ 5: Assertions ที่ใช้บ่อย

Assertion คือหัวใจของ test เพราะเป็นจุดที่เราบอกว่า “ผลลัพธ์ที่ถูกต้องควรเป็นอย่างไร” ในงานจริงควรเลือก assertion ให้ตรงกับสิ่งที่ต้องการตรวจสอบ

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AssertionExampleTest extends TestCase
{
    public function test_common_assertions(): void
    {
        $name = 'PHPUnit';
        $items = ['php', 'testing'];

        // ตรวจค่าและชนิดข้อมูล
        $this->assertSame('PHPUnit', $name);

        // ตรวจว่าค่าเป็นจริง
        $this->assertTrue(in_array('php', $items, true));

        // ตรวจจำนวนสมาชิก
        $this->assertCount(2, $items);

        // ตรวจว่ามี key ที่ต้องการ
        $this->assertArrayHasKey(0, $items);
    }
}
```

สิ่งที่ควรเข้าใจ:
- ใช้ `assertSame()` เมื่อต้องการความชัดเจนเรื่อง type
- ใช้ `assertTrue()` หรือ `assertFalse()` เมื่อผลลัพธ์เป็น boolean
- อย่าใช้ assertion กว้างเกินไปถ้ามีตัวเลือกที่ชัดเจนกว่า

---

## หัวข้อย่อยที่ 6: ทดสอบ Exception

หลายกรณีในงานจริง เราไม่ได้ทดสอบแค่ “ค่าที่คืนกลับมา” แต่ต้องทดสอบด้วยว่าโค้ดโยน exception ถูกต้องเมื่อรับข้อมูลไม่ถูกต้อง

ตัวอย่าง production code:

```php
<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

final class Email
{
    public static function fromString(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // โยน exception เมื่อรูปแบบอีเมลไม่ถูกต้อง
            throw new InvalidArgumentException('Invalid email');
        }

        return new self();
    }
}
```

ตัวอย่าง test:

```php
<?php

declare(strict_types=1);

namespace Tests;

use App\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_it_throws_exception_for_invalid_email(): void
    {
        // คาดหวังว่าจะเกิด exception ชนิดนี้
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('not-an-email');
    }
}
```

สิ่งที่ควรเข้าใจ:
- test ที่ดีควรครอบคลุมทั้ง success case และ failure case
- `expectException()` ช่วยให้ตั้งความคาดหวังไว้ชัดเจน
- validation logic สำคัญมักควรมี test รองรับเสมอ

---

## หัวข้อย่อยที่ 7: ใช้ `setUp()` เมื่อมีการเตรียมข้อมูลซ้ำ

ถ้าหลาย test ใช้วัตถุหรือข้อมูลตั้งต้นแบบเดียวกัน เราสามารถใช้ `setUp()` เพื่อลดโค้ดซ้ำได้ โดย PHPUnit จะเรียก `setUp()` ก่อนแต่ละ test method

```php
<?php

declare(strict_types=1);

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\TestCase;

final class CalculatorWithSetupTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        // สร้าง object ใหม่ก่อนรันแต่ละ test
        $this->calculator = new Calculator();
    }

    public function test_addition(): void
    {
        $this->assertSame(7, $this->calculator->add(3, 4));
    }

    public function test_addition_with_zero(): void
    {
        $this->assertSame(9, $this->calculator->add(9, 0));
    }
}
```

สิ่งที่ควรเข้าใจ:
- `setUp()` ช่วยลดการสร้าง object ซ้ำในทุก test
- แต่ไม่ควรใช้จน test ซ่อนรายละเอียดมากเกินไป
- test ที่อ่านแล้วเข้าใจทันที ยังสำคัญกว่าการลดบรรทัดโค้ด

---

## หัวข้อย่อยที่ 8: ใช้ Data Provider เพื่อลดโค้ดซ้ำ

เมื่อเราต้องทดสอบ logic เดียวกันด้วยหลายชุดข้อมูล การใช้ Data Provider จะช่วยให้ test กระชับขึ้นและดูแลรักษาง่ายขึ้น

```php
<?php

declare(strict_types=1);

namespace Tests;

use App\Calculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalculatorDataProviderTest extends TestCase
{
    #[DataProvider('additionProvider')]
    public function test_add(int $expected, int $a, int $b): void
    {
        $calculator = new Calculator();

        // ใช้ข้อมูลหลายชุดทดสอบ method เดียวกัน
        $this->assertSame($expected, $calculator->add($a, $b));
    }

    public static function additionProvider(): array
    {
        return [
            'positive numbers' => [5, 2, 3],
            'with zero' => [4, 4, 0],
            'negative and positive' => [1, -1, 2],
        ];
    }
}
```

สิ่งที่ควรเข้าใจ:
- Data Provider เหมาะกับกรณี test หลาย input ในพฤติกรรมเดียวกัน
- ช่วยให้เพิ่ม test cases ใหม่ได้ง่าย
- ตั้งชื่อ data set ให้สื่อความหมาย จะช่วยให้อ่านผลลัพธ์ง่ายขึ้น

---

## หัวข้อย่อยที่ 9: รัน Test จาก Command Line

การรัน test แบบง่ายที่สุดคือเรียก PHPUnit จากในโปรเจกต์โดยตรง

```bash
./vendor/bin/phpunit
```

หากต้องการรันเฉพาะไฟล์เดียว:

```bash
./vendor/bin/phpunit tests/CalculatorTest.php
```

หากต้องการเลือก test ตามชื่อ:

```bash
./vendor/bin/phpunit --filter test_add
```

สิ่งที่ควรเข้าใจ:
- เริ่มจากการรันทั้งชุดก่อน แล้วค่อยเจาะจงบาง test เมื่อ debug
- `--filter` ช่วยให้ทำงานเร็วขึ้นตอนแก้ test หรือแก้ bug
- การรัน test บ่อย ๆ ระหว่างพัฒนาเป็นนิสัยที่ดีมาก

---

## หัวข้อย่อยที่ 10: ตั้งค่า `phpunit.xml` เบื้องต้น

ในโปรเจกต์จริง นิยมใช้ `phpunit.xml` เพื่อกำหนด bootstrap, test suite และ source code ที่เกี่ยวข้อง ทำให้การรัน test ง่ายขึ้น เพราะสั่ง `phpunit` ได้ตรง ๆ

ตัวอย่างไฟล์ `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

สิ่งที่ควรเข้าใจ:
- `bootstrap="vendor/autoload.php"` ช่วยให้โหลด autoloader อัตโนมัติ
- `testsuites` ใช้กำหนดว่าจะให้รัน test จากโฟลเดอร์ใด
- `source` ช่วยระบุโค้ดของเราเองสำหรับการวิเคราะห์และ code coverage

---

## หัวข้อย่อยที่ 11: Code Coverage เบื้องต้น

Code coverage ช่วยให้เห็นว่ามีส่วนใดของ source code ถูกเรียกใช้งานระหว่างการทดสอบบ้าง แต่ coverage สูงไม่ได้แปลว่า test ดีเสมอไป สิ่งสำคัญคือ test ต้องมีคุณภาพและตรวจพฤติกรรมที่สำคัญจริง

ตัวอย่างคำสั่งสร้างรายงาน HTML:

```bash
./vendor/bin/phpunit --coverage-html coverage
```

หมายเหตุ:
- หากต้องการเก็บ coverage ต้องมี driver เช่น **PCOV** หรือ **Xdebug**
- ถ้าใช้ Xdebug ต้องเปิด coverage mode ให้ถูกต้อง

สิ่งที่ควรเข้าใจ:
- coverage ใช้เป็นตัวช่วย ไม่ใช่เป้าหมายเดียว
- อย่าไล่ตัวเลข coverage โดยไม่สนคุณภาพของ test
- ให้โฟกัสที่ business logic สำคัญก่อนเสมอ

---

## หัวข้อย่อยที่ 12: Best Practices สำหรับการเขียน Test

แนวทางที่ควรใช้ในงานจริง:

- เขียน test ให้เล็กและอ่านง่าย
- หนึ่ง test ควรตรวจสอบหนึ่งพฤติกรรมหลัก
- ตั้งชื่อ test ให้สื่อว่า “ระบบควรทำอะไร”
- ใช้ `assertSame()` เมื่อต้องการตรวจทั้งค่าและ type
- ใช้ Data Provider แทนการคัดลอก test ซ้ำหลายชุด
- ใช้ `setUp()` เท่าที่จำเป็น
- รัน test ทุกครั้งก่อน commit หรือ push
- ใส่ test ใน CI ภายหลังเมื่อโปรเจกต์เริ่มโต

ตัวอย่างชื่อ test ที่ดี:

```php
public function test_it_returns_total_price_with_vat(): void
{
    // อ่านชื่อก็เข้าใจว่ากำลังตรวจอะไร
}
```

---

## สรุป

**PHP Testing with PHPUnit** เป็นทักษะสำคัญที่ช่วยยกระดับโปรเจกต์ PHP จากโค้ดที่ “ใช้งานได้” ไปสู่โค้ดที่ “เชื่อถือได้” เมื่อคุณเข้าใจการติดตั้ง PHPUnit, การสร้าง test class, การใช้ assertions, การทดสอบ exception, การใช้ `setUp()`, Data Provider และ `phpunit.xml` แล้ว คุณจะพร้อมต่อยอดไปสู่ unit testing, integration testing, mocking และ CI/CD ได้อย่างมั่นใจ

การเริ่มต้นที่ดีที่สุดคือเลือก class เล็ก ๆ สักหนึ่งตัวในโปรเจกต์ของคุณ แล้วเขียน test ให้ครอบคลุม behavior สำคัญก่อน จากนั้นค่อยขยายไปยังส่วนอื่นอย่างเป็นระบบ
