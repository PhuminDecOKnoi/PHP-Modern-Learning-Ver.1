File Name: Error Handling and Exceptions in PHP.md

# Error Handling and Exceptions in PHP

## บทนำ

การจัดการข้อผิดพลาดเป็นหัวใจสำคัญของงานพัฒนา PHP เพราะโปรแกรมจริงไม่ได้ทำงานในสภาพแวดล้อมที่สมบูรณ์เสมอไป เช่น input ไม่ถูกต้อง, ไฟล์หาย, database ล่ม, API ตอบกลับผิดรูปแบบ หรือ business rule บางอย่างไม่ผ่าน

ใน PHP ยุคปัจจุบัน เราควรแยกให้ออกระหว่าง **error**, **exception** และ **logging** เพื่อให้ระบบอ่านง่าย ดูแลง่าย และปลอดภัยขึ้น โดยเฉพาะใน production ที่ไม่ควรแสดงรายละเอียดภายในระบบออกไปให้ผู้ใช้เห็น

บทเรียนนี้เหมาะสำหรับผู้ที่เริ่มใช้ PHP จริงจัง และอยากเขียนโค้ดให้รับมือข้อผิดพลาดได้แบบ professional

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจความต่างระหว่าง error และ exception ใน PHP
- ใช้ `try`, `catch`, `finally` ได้อย่างถูกต้อง
- รู้จัก `Throwable`, `Exception` และ `Error`
- สร้าง custom exception สำหรับ business rule ได้
- ใช้ `error_reporting()` และตั้งค่าการแสดงผลให้เหมาะกับ development / production
- ใช้ `set_error_handler()` และ `set_exception_handler()` อย่างระมัดระวัง
- เห็นแนวทาง logging และ best practices ที่ใช้ได้จริง

---

## หัวข้อย่อยที่ 1: Error และ Exception ต่างกันอย่างไร

ใน PHP คำว่า “ข้อผิดพลาด” ไม่ได้มีความหมายเหมือนกันทั้งหมด บางกรณีเป็น **diagnostic error** เช่น warning หรือ notice และบางกรณีเป็น **exception** ที่สามารถ `throw` และ `catch` ได้

ในงานจริง ให้จำง่าย ๆ ว่า:
- **Error/Warning/Notice** มักมาจาก runtime หรือการทำงานของ engine
- **Exception** มักใช้แทนเหตุการณ์ผิดปกติที่แอปต้องการจัดการอย่างเป็นระบบ
- ตั้งแต่ PHP 7 เป็นต้นมา ทั้ง `Error` และ `Exception` อยู่ภายใต้ `Throwable`

```php
<?php

declare(strict_types=1);

// ตัวอย่าง exception แบบง่าย
throw new Exception('Something went wrong');
```

สิ่งที่ควรเข้าใจ:
- ไม่ใช่ทุกปัญหาจะถูกจัดการเหมือนกัน
- โค้ดที่ดีควรเลือกใช้ exception กับกรณีที่ “ต้องการจัดการเชิงตรรกะ”
- งาน debug, monitoring และ logging ควรแยกจากการแสดงผลให้ผู้ใช้

---

## หัวข้อย่อยที่ 2: รู้จัก `Throwable`, `Exception` และ `Error`

`Throwable` คือรากของสิ่งที่สามารถถูก `throw` ได้ใน PHP ส่วนที่พบได้บ่อยคือ `Exception` และ `Error`

- `Exception` เหมาะกับกรณีที่แอปพลิเคชันตั้งใจโยนปัญหาออกมา
- `Error` มักเกี่ยวกับ engine-level problem หรือข้อผิดพลาดร้ายแรงทางโปรแกรม
- ถ้าต้องการ catch ได้กว้างขึ้น สามารถ catch เป็น `Throwable` ได้

```php
<?php

declare(strict_types=1);

function runTask(): void
{
    throw new RuntimeException('Task failed');
}

try {
    runTask();
} catch (Throwable $e) {
    // จับได้ทั้ง Exception และ Error
    echo 'Caught: ' . $e->getMessage();
}
```

สิ่งที่ควรเข้าใจ:
- `Throwable` เหมาะกับจุด boundary ของระบบ เช่น front controller หรือ job runner
- ใน logic ภายใน ควร catch ให้แคบที่สุดเท่าที่ทำได้
- อย่าจับ `Throwable` ทุกที่โดยไม่จำเป็น เพราะจะทำให้ debug ยากขึ้น

---

## หัวข้อย่อยที่ 3: การใช้ `try`, `catch`, `finally`

`try` ใช้ครอบโค้ดที่อาจเกิด exception, `catch` ใช้จัดการเมื่อเกิดปัญหา และ `finally` ใช้กับงาน cleanup ที่ต้องทำไม่ว่าจะสำเร็จหรือผิดพลาด

แนวคิดสำคัญคืออย่าใส่ `try` กว้างเกินไป ควรครอบเฉพาะส่วนที่มีความเสี่ยงจริง เพื่อให้อ่านง่ายและรู้ทันทีว่าอะไรคือจุดที่อาจพัง

```php
<?php

declare(strict_types=1);

function divide(int $a, int $b): float
{
    if ($b === 0) {
        throw new InvalidArgumentException('Divider must not be zero');
    }

    return $a / $b;
}

try {
    $result = divide(10, 2);
    echo 'Result: ' . $result;
} catch (InvalidArgumentException $e) {
    echo 'Input error: ' . $e->getMessage();
} finally {
    // ทำงานส่วน cleanup เสมอ
    echo PHP_EOL . 'Done';
}
```

สิ่งที่ควรเข้าใจ:
- `finally` เหมาะกับ cleanup เช่น ปิด resource หรือบันทึกสถานะท้ายงาน
- ควร catch exception ให้ตรงชนิดเมื่อรู้ว่ารับมือกรณีนั้นอย่างไร
- ถ้าไม่รู้จะจัดการอย่างไร อย่ากลืน exception ทิ้งเงียบ ๆ

---

## หัวข้อย่อยที่ 4: การโยน exception ด้วย `throw`

การใช้ `throw` ช่วยให้โค้ดสื่อสารว่ามี “สถานการณ์ผิดปกติ” เกิดขึ้น และบังคับให้ caller ตัดสินใจว่าจะรับมืออย่างไรต่อ

ในงานจริง ควร throw เมื่อ:
- input ไม่ผ่านเงื่อนไขสำคัญ
- resource ที่จำเป็นใช้งานไม่ได้
- business rule บางอย่างไม่เป็นไปตามข้อกำหนด

```php
<?php

declare(strict_types=1);

function registerUser(string $email): void
{
    if ($email === '') {
        // โยน exception เมื่อข้อมูลสำคัญไม่ถูกต้อง
        throw new InvalidArgumentException('Email is required');
    }

    echo 'User registered: ' . $email;
}

try {
    registerUser('');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}
```

สิ่งที่ควรเข้าใจ:
- อย่าใช้ exception แทนทุกเงื่อนไขทั่วไป
- ใช้ exception เมื่อกรณีนั้น “ผิดปกติ” จริงสำหรับ flow ของระบบ
- ข้อความ exception ควรชัด แต่ไม่ควรเผยข้อมูลลับ

---

## หัวข้อย่อยที่ 5: สร้าง Custom Exception สำหรับงานจริง

เมื่อระบบเริ่มใหญ่ขึ้น การใช้ `Exception` หรือ `RuntimeException` แบบกว้าง ๆ อาจไม่พอ เพราะเราอยากแยกชนิดปัญหาให้ชัดเจน เช่น Payment failed, Validation failed, หรือ Domain rule violated

การสร้าง custom exception ช่วยให้โค้ดอ่านง่ายขึ้น และทำให้ `catch` ได้ตรงความหมายของปัญหา

```php
<?php

declare(strict_types=1);

class OrderException extends RuntimeException
{
}

function placeOrder(int $quantity): void
{
    if ($quantity <= 0) {
        throw new OrderException('Quantity must be greater than zero');
    }

    echo 'Order placed';
}

try {
    placeOrder(0);
} catch (OrderException $e) {
    echo 'Order error: ' . $e->getMessage();
}
```

สิ่งที่ควรเข้าใจ:
- custom exception ทำให้ domain logic ชัดเจนขึ้น
- เหมาะกับระบบที่มีหลาย business rule
- ควรตั้งชื่อ class ให้สื่อปัญหา เช่น `PaymentException`, `ValidationException`

---

## หัวข้อย่อยที่ 6: การใช้หลาย `catch` และการ rethrow

บางครั้ง function เดียวอาจเจอได้หลายประเภทของ exception คุณสามารถแยก `catch` ตามชนิดเพื่อให้แต่ละกรณีตอบสนองต่างกันได้

และถ้าจับแล้วพบว่ายังไม่ควรจบแค่นั้น สามารถ **rethrow** เพื่อส่ง exception ต่อขึ้นไปยังชั้นบนได้

```php
<?php

declare(strict_types=1);

try {
    // สมมติว่าจุดนี้มีหลายอย่างที่อาจพัง
    throw new RuntimeException('Database timeout');
} catch (InvalidArgumentException $e) {
    echo 'Bad input: ' . $e->getMessage();
} catch (RuntimeException $e) {
    // log ก่อน แล้วค่อยโยนต่อ
    error_log($e->getMessage());
    throw $e;
}
```

สิ่งที่ควรเข้าใจ:
- แยก `catch` ตาม responsibility ของแต่ละกรณี
- ถ้ายังไม่ใช่จุดที่ควรตัดสินใจสุดท้าย ให้ log แล้ว rethrow ได้
- อย่าจับ exception แล้วไม่ทำอะไรเลย

---

## หัวข้อย่อยที่ 7: `error_reporting()` และสภาพแวดล้อม Development / Production

การจัดการข้อผิดพลาดที่ดีไม่ใช่แค่เขียน `try/catch` แต่รวมถึงการตั้งค่าว่าจะ **รายงาน**, **แสดง**, และ **บันทึก** error อย่างไรด้วย

ใน development ควรเห็น error ให้ชัดเพื่อแก้ปัญหาได้เร็ว ส่วนใน production ควรเน้น log และหลีกเลี่ยงการแสดงรายละเอียดภายในระบบออกหน้าจอ

```php
<?php

declare(strict_types=1);

// ตัวอย่างสำหรับ development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ตัวอย่างสำหรับ production
// error_reporting(E_ALL);
// ini_set('display_errors', '0');
// ini_set('log_errors', '1');
```

สิ่งที่ควรเข้าใจ:
- development ควรมองเห็นปัญหาเร็ว
- production ควร log แทนการโชว์รายละเอียดให้ผู้ใช้
- การตั้งค่าระดับ error เป็นส่วนสำคัญของ quality และ security

---

## หัวข้อย่อยที่ 8: `set_error_handler()` สำหรับแปลง error บางชนิดเป็น exception

PHP เปิดให้กำหนด custom error handler ได้ด้วย `set_error_handler()` ซึ่งมีประโยชน์มากเมื่ออยากเปลี่ยน warning หรือ notice บางชนิดให้กลายเป็น `ErrorException` แล้วคุม flow ผ่าน `try/catch`

อย่างไรก็ตาม ไม่ควรแปลงทุกอย่างแบบเหมารวม เพราะ notice หรือ deprecation บางตัวอาจไม่ควรทำให้โปรแกรมหยุดทันที

```php
<?php

declare(strict_types=1);

set_error_handler(function (
    int $errno,
    string $errstr,
    string $errfile,
    int $errline
): bool {
    // ถ้า error นี้ไม่อยู่ในระดับที่เราสนใจ ก็ปล่อยผ่าน
    if (!(error_reporting() & $errno)) {
        return false;
    }

    // ตัวอย่าง: ไม่เปลี่ยน deprecation เป็น exception
    if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
        return false;
    }

    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    // file() กับไฟล์ที่ไม่มีอยู่ อาจสร้าง warning
    file('missing-file.txt');
} catch (ErrorException $e) {
    echo 'Handled as exception: ' . $e->getMessage();
}
```

สิ่งที่ควรเข้าใจ:
- แนวทางนี้ทำให้ flow การจัดการปัญหาเป็นระบบขึ้น
- อย่าแปลงทุก notice/warning เป็น exception แบบไม่คิด
- custom error handler ไม่สามารถจัดการ error ได้ทุกชนิด

---

## หัวข้อย่อยที่ 9: `set_exception_handler()` สำหรับจุด fallback ของระบบ

ถ้า exception หลุดออกมาจนไม่มี `catch` ใดรับไว้ PHP สามารถเรียก global handler ผ่าน `set_exception_handler()` ได้

แนวคิดนี้เหมาะกับ “จุดขอบ” ของระบบ เช่น entry point ของเว็บ, CLI command หรือ worker process เพื่อให้มี fallback handler กลางสำหรับ log และตอบกลับข้อความแบบปลอดภัย

```php
<?php

declare(strict_types=1);

set_exception_handler(function (Throwable $e): void {
    // บันทึกลง log ก่อน
    error_log($e->getMessage());

    // แสดงข้อความแบบปลอดภัยกับผู้ใช้
    echo 'Unexpected error occurred';
});

throw new RuntimeException('Database connection failed');
```

สิ่งที่ควรเข้าใจ:
- global exception handler ไม่ใช่ข้ออ้างให้ไม่เขียน `try/catch` ในจุดสำคัญ
- มันเหมาะเป็น safety net ชั้นสุดท้าย
- ใน production ควรตอบข้อความทั่วไป และ log รายละเอียดจริงไว้ภายใน

---

## หัวข้อย่อยที่ 10: Logging ให้ถูกที่ ไม่เปิดเผยข้อมูลเกินจำเป็น

การแสดง error ต่อผู้ใช้กับการ log ภายในระบบควรถูกแยกจากกันเสมอ ผู้ใช้ควรเห็นข้อความที่เข้าใจง่าย แต่ทีมพัฒนาควรมีข้อมูลเพียงพอสำหรับ debug เช่น message, code, file, line และ trace

```php
<?php

declare(strict_types=1);

try {
    throw new RuntimeException('API timeout');
} catch (Throwable $e) {
    // เก็บรายละเอียดไว้ใน log
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $e::class,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    // แสดงข้อความทั่วไปกับผู้ใช้
    echo 'Service temporarily unavailable';
}
```

สิ่งที่ควรเข้าใจ:
- อย่าแสดง stack trace หรือ path ภายในให้ผู้ใช้ทั่วไปเห็น
- log ควรมีข้อมูลพอให้ตามรอยปัญหาได้
- ควรกำหนดรูปแบบ log ให้สม่ำเสมอในทั้งโปรเจกต์

---

## หัวข้อย่อยที่ 11: ข้อควรระวังเรื่อง `@` Error Control Operator

PHP มี `@` สำหรับ suppress diagnostic error แต่ในงานจริงไม่ควรใช้พร่ำเพรื่อ เพราะทำให้การ debug ยากขึ้น และถ้ามี custom error handler อยู่ มันยังอาจถูกเรียกอยู่ดี

ทางที่ดีกว่าคือเขียนเงื่อนไขเชิงป้องกัน หรือใช้ `try/catch` / validation / handler ที่ชัดเจน

```php
<?php

declare(strict_types=1);

// ไม่แนะนำ
// $data = @file('missing-file.txt');

// แนะนำกว่า: ตรวจสอบก่อนหรือใช้ handler ที่ชัดเจน
$path = 'missing-file.txt';

if (!is_file($path)) {
    echo 'File not found';
} else {
    $data = file($path);
}
```

สิ่งที่ควรเข้าใจ:
- `@` ไม่ใช่แนวทางหลักของโค้ดที่ดี
- suppress error มักซ่อนปัญหาที่ควรถูกแก้
- ให้ใช้ validation และ explicit handling แทน

---

## หัวข้อย่อยที่ 12: ตัวอย่างโครงสร้างแบบใช้งานจริง

ตัวอย่างนี้แสดงรูปแบบที่นิยมในโปรเจกต์จริง คือโยน exception จาก service, จับที่ controller หรือ entry point, log รายละเอียด แล้วแสดงข้อความที่เหมาะกับผู้ใช้

```php
<?php

declare(strict_types=1);

class PaymentException extends RuntimeException
{
}

class PaymentService
{
    public function charge(float $amount): void
    {
        if ($amount <= 0) {
            throw new PaymentException('Invalid payment amount');
        }

        // สมมติว่าชำระเงินสำเร็จ
    }
}

class CheckoutController
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function checkout(float $amount): void
    {
        try {
            $this->paymentService->charge($amount);
            echo 'Payment successful';
        } catch (PaymentException $e) {
            error_log($e->getMessage());
            echo 'Unable to process payment';
        }
    }
}

$controller = new CheckoutController(new PaymentService());
$controller->checkout(0);
```

สิ่งที่ควรเข้าใจ:
- service โยนปัญหาตาม domain rule
- controller เป็นจุดที่ตัดสินใจว่าจะตอบผู้ใช้อย่างไร
- โครงสร้างนี้อ่านง่าย ทดสอบง่าย และดูแลต่อได้ง่าย

---

## Best Practices ที่ควรใช้ตั้งแต่ต้น

- ใช้ `try/catch` เฉพาะจุดที่มีความเสี่ยงจริง
- catch ให้แคบที่สุดก่อน เช่น `InvalidArgumentException` ก่อน `Throwable`
- ใช้ custom exception กับ business rule สำคัญ
- development ให้เปิดเห็น error ชัดเจน
- production ให้ปิดการแสดง error และใช้ logging แทน
- อย่ากลืน exception ทิ้งโดยไม่ log หรือไม่จัดการ
- หลีกเลี่ยง `@` เพราะทำให้ debug ยาก
- ตั้งจุด fallback กลางด้วย `set_exception_handler()` สำหรับ entry point ของระบบ

---

## สรุป

หัวข้อ **Error Handling and Exceptions in PHP** เป็นพื้นฐานสำคัญของการเขียนโปรแกรมแบบมืออาชีพ เพราะระบบที่ดีไม่ใช่แค่ “ทำงานได้” แต่ต้อง “พังอย่างมีแบบแผน” และ “ตรวจสอบย้อนหลังได้” ด้วย

เมื่อคุณเข้าใจ `Throwable`, `try/catch/finally`, custom exception, `error_reporting()`, `set_error_handler()` และ `set_exception_handler()` แล้ว คุณจะสามารถออกแบบ PHP application ที่อ่านง่าย ปลอดภัย และพร้อมใช้งานจริงมากขึ้น
