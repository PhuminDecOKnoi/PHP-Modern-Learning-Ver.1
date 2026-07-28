# 06 — Testing with PHPUnit 13

## บทนำ

บทนี้สอน automated testing ด้วย PHPUnit 13 สำหรับ PHP 8.4–8.5 โดยเน้น test ที่อ่านง่าย ตรวจพฤติกรรมสำคัญ และรันได้ทั้งในเครื่องและ GitHub Actions

## Requirements

```text
PHP 8.4 หรือ 8.5
Composer 2
PHPUnit 13.x
```

ติดตั้ง:

```bash
composer require --dev phpunit/phpunit:^13.2
```

ตรวจเวอร์ชัน:

```bash
./vendor/bin/phpunit --version
```

## เป้าหมายการเรียนรู้

- สร้าง test class ด้วย `TestCase`
- ใช้ attributes เช่น `#[Test]` และ `#[DataProvider]`
- เลือก assertion ให้ตรงกับพฤติกรรม
- ทดสอบ exception
- แยก stub กับ mock ตามเจตนา
- รัน tests ผ่าน Composer และ CI

## 1. Production Code

ไฟล์ `src/Support/Email.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $email): self
    {
        $normalized = strtolower(trim($email));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        return new self($normalized);
    }
}
```

## 2. Test แรก

ไฟล์ `tests/Support/EmailTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Email;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function it_normalizes_a_valid_email(): void
    {
        $email = Email::fromString(' USER@EXAMPLE.COM ');

        self::assertSame('user@example.com', $email->value);
    }
}
```

### โครงสร้าง Arrange–Act–Assert

```text
Arrange: เตรียม input หรือ dependency
Act: เรียก code ที่ต้องการทดสอบ
Assert: ตรวจผลลัพธ์
```

ใน test ขนาดเล็กอาจรวม Arrange และ Act ไว้บรรทัดเดียวได้ หากยังอ่านชัดเจน

## 3. ทดสอบ Exception

```php
#[Test]
public function it_rejects_an_invalid_email(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid email address.');

    Email::fromString('not-an-email');
}
```

ควรทดสอบทั้ง success path และ failure path ของ business rule สำคัญ

## 4. Data Provider

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvalidEmailTest extends TestCase
{
    public static function invalidEmails(): iterable
    {
        yield 'empty value' => [''];
        yield 'missing domain' => ['user@'];
        yield 'plain text' => ['not-an-email'];
    }

    #[DataProvider('invalidEmails')]
    public function test_invalid_emails_are_rejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString($input);
    }
}
```

Data Provider เหมาะเมื่อทดสอบพฤติกรรมเดียวกันกับ input หลายชุด

## 5. Assertions ที่ใช้บ่อย

| Assertion | ใช้เมื่อ |
|---|---|
| `assertSame()` | ตรวจค่าและชนิดข้อมูล |
| `assertEquals()` | ต้องการ comparison แบบไม่เข้มเท่า `same` |
| `assertTrue()` / `assertFalse()` | ตรวจ boolean |
| `assertCount()` | ตรวจจำนวนสมาชิก |
| `assertContains()` | ตรวจสมาชิกใน iterable |
| `assertInstanceOf()` | ตรวจชนิด object |
| `assertNull()` / `assertNotNull()` | ตรวจ nullable result |

ให้เลือก assertion ที่สื่อ intent ชัดที่สุด แทนการใช้ `assertTrue()` ครอบ expression ซับซ้อนทุกกรณี

## 6. `setUp()`

```php
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new Calculator();
    }

    public function test_addition(): void
    {
        self::assertSame(5, $this->calculator->add(2, 3));
    }
}
```

ใช้ `setUp()` เมื่อหลาย test ต้องสร้าง fixture แบบเดียวกัน แต่หลีกเลี่ยง setup ขนาดใหญ่ที่ทำให้ test อ่านยาก

## 7. Stub กับ Mock

### Stub

ใช้เมื่อ dependency ต้องคืนค่าควบคุม แต่ไม่สนใจจำนวนครั้งที่ถูกเรียก

```php
$clock = $this->createStub(Clock::class);
$clock->method('now')->willReturn(new DateTimeImmutable('2026-07-28'));
```

### Mock

ใช้เมื่อ interaction เป็นพฤติกรรมที่ต้องตรวจจริง

```php
$mailer = $this->createMock(Mailer::class);
$mailer
    ->expects(self::once())
    ->method('sendWelcomeMessage');
```

อย่าใช้ mock เพียงเพราะ dependency เป็น interface ถ้า test ไม่ได้ตรวจ interaction นั้น ให้ใช้ stub หรือ fake ที่ง่ายกว่า

## 8. `phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit
    bootstrap="vendor/autoload.php"
    cacheDirectory=".phpunit.cache"
    colors="true"
    failOnWarning="true"
    failOnRisky="true"
>
    <testsuites>
        <testsuite name="course-tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## 9. Composer Scripts

```json
{
  "scripts": {
    "test": "phpunit --colors=always",
    "lint": "find . -path './vendor' -prune -o -name '*.php' -print0 | xargs -0 -n1 php -l"
  }
}
```

รัน:

```bash
composer test
composer lint
```

## 10. Test Naming

ชื่อ test ควรบอกพฤติกรรม:

```text
it_normalizes_a_valid_email
it_rejects_an_invalid_email
user_cannot_approve_an_already_rejected_request
```

หลีกเลี่ยงชื่อกว้าง เช่น:

```text
testEmail
testFunction1
worksCorrectly
```

## 11. Test Pyramid แบบใช้งานจริง

- Unit tests: เร็วและแยก dependency
- Integration tests: ตรวจการทำงานร่วมกับ database/API/ filesystem
- End-to-end tests: ตรวจ flow สำคัญผ่านระบบจริง

ไม่จำเป็นต้อง mock ทุกอย่าง Unit tests จำนวนมากที่ไม่สะท้อน business behavior อาจมีต้นทุนดูแลสูงกว่าประโยชน์

## 12. GitHub Actions

Repository นี้ใช้ matrix:

```yaml
matrix:
  php: ['8.4', '8.5']
```

CI ตรวจ:

1. Composer configuration
2. dependency installation
3. PHP syntax
4. PHPUnit tests

## 13. Checklist

- [ ] test ตรวจพฤติกรรมหนึ่งเรื่องเป็นหลัก
- [ ] ชื่อ test สื่อความหมาย
- [ ] ใช้ `assertSame()` เมื่อ type สำคัญ
- [ ] ครอบคลุม failure path
- [ ] ใช้ stub เมื่อไม่ตรวจ interaction
- [ ] ใช้ mock เมื่อ interaction เป็น requirement
- [ ] test ไม่พึ่งเวลา network หรือข้อมูลภายนอกโดยไม่ควบคุม
- [ ] รันบน PHP 8.4 และ 8.5

## แบบฝึกหัด

1. เพิ่ม Data Provider สำหรับ valid emails
2. สร้าง class `Money` และทดสอบจำนวนติดลบ
3. สร้าง stub ของ `Clock`
4. สร้าง mock ของ `Mailer` เฉพาะกรณีที่ต้องส่ง welcome message หนึ่งครั้ง
5. ทำให้ CI รัน test ทุกครั้งที่เปิด pull request

## References

- https://phpunit.de/documentation.html
- https://phpunit.de/supported-versions.html
- https://docs.phpunit.de/en/13.2/
