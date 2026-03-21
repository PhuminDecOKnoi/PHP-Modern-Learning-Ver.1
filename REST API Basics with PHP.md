File Name: REST API Basics with PHP.md

# REST API Basics with PHP

## บทนำ

REST API คือรูปแบบการสื่อสารระหว่างระบบผ่าน HTTP โดยมักใช้ข้อมูลแบบ JSON เป็นหลัก สำหรับ PHP ยุคปัจจุบัน การเริ่มต้นทำ REST API ควรเข้าใจ 4 เรื่องสำคัญก่อนเสมอ คือ **routing แบบพื้นฐาน**, **HTTP methods**, **JSON request/response**, และ **validation + error handling** ฝั่ง server

บทเรียนนี้เหมาะสำหรับผู้เริ่มต้นที่อยากสร้าง API แบบง่ายด้วย PHP ล้วน ๆ ก่อนต่อยอดไปยัง framework เช่น Laravel หรือ Slim โดยจะเน้นตัวอย่างที่อ่านง่าย รันได้จริง และสอดคล้องกับแนวทาง modern PHP

## สิ่งที่ผู้เรียนจะได้เรียน

- เข้าใจโครงสร้างพื้นฐานของ REST API
- รู้จัก HTTP methods เช่น `GET`, `POST`, `PUT`, `DELETE`
- รับ request body แบบ JSON ด้วย `php://input`
- ส่ง response เป็น JSON ด้วย `json_encode()`
- ตั้งค่า HTTP status code และ headers ให้ถูกต้อง
- ทำ validation และจัดการ error แบบเป็นระบบ
- ทดลองรัน API ด้วย built-in server ของ PHP

---

## หัวข้อย่อยที่ 1: REST API คืออะไร

REST API คือการออกแบบ endpoint ให้สื่อความหมายตาม resource เช่น `/users`, `/products`, `/orders` และใช้ HTTP method เพื่อบอกเจตนาของการทำงาน เช่นอ่านข้อมูล สร้างข้อมูล แก้ไข หรือ ลบข้อมูล

แนวคิดสำคัญคือ API ควรส่งข้อมูลกลับในรูปแบบที่เครื่องอ่านง่าย เช่น JSON และควรแยกความหมายของ request/response ให้ชัดเจน

```php
<?php

// ตัวอย่างแนวคิด endpoint
// GET    /users      => อ่านรายการผู้ใช้
// GET    /users/1    => อ่านผู้ใช้ 1 คน
// POST   /users      => สร้างผู้ใช้ใหม่
// PUT    /users/1    => แก้ไขผู้ใช้
// DELETE /users/1    => ลบผู้ใช้
```

สิ่งที่ควรเข้าใจ:
- REST เน้น resource และ HTTP semantics
- URL ควรสื่อว่าเข้าถึง “อะไร”
- Method ควรสื่อว่า “ทำอะไร”

---

## หัวข้อย่อยที่ 2: อ่าน HTTP Method และ Route แบบง่าย

ใน PHP เราสามารถอ่าน method ของ request ได้จาก `$_SERVER['REQUEST_METHOD']` และอ่าน path จาก `$_SERVER['REQUEST_URI']` จากนั้นใช้ `match` หรือ `if` เพื่อ route ไปยัง logic ที่ต้องการ

```php
<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($method === 'GET' && $path === '/api/health') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Route not found']);
```

สิ่งที่ควรเข้าใจ:
- `$_SERVER` ใช้อ่านข้อมูลของ request
- `parse_url()` ช่วยแยก path ออกจาก query string
- ควรมี default response สำหรับ route ที่ไม่พบเสมอ

---

## หัวข้อย่อยที่ 3: ส่ง JSON Response ให้ถูกต้อง

REST API ควรส่ง response ที่มี `Content-Type: application/json` และควรตั้ง status code ให้ตรงกับผลลัพธ์ เช่น `200`, `201`, `400`, `404`, `500`

```php
<?php

declare(strict_types=1);

function jsonResponse(array $data, int $statusCode = 200): void
{
    // ตั้งค่า status code ของ HTTP response
    http_response_code($statusCode);

    // ระบุว่าข้อมูลที่ส่งกลับเป็น JSON
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

jsonResponse([
    'message' => 'API is working',
    'success' => true,
]);
```

สิ่งที่ควรเข้าใจ:
- ใช้ `header()` ก่อนมี output อื่น
- ใช้ `http_response_code()` เพื่อสื่อผลลัพธ์ระดับ HTTP
- สร้าง helper function จะช่วยให้โค้ด reuse ได้ง่ายขึ้น

---

## หัวข้อย่อยที่ 4: รับ JSON Request Body ด้วย `php://input`

เมื่อ client ส่งข้อมูลแบบ `application/json` ค่าเหล่านั้นจะไม่ถูกใส่ไว้ใน `$_POST` โดยตรง ดังนั้นฝั่ง API ควรอ่าน raw body ผ่าน `php://input` แล้ว decode ด้วย `json_decode()`

```php
<?php

declare(strict_types=1);

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!is_array($data)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

// ตัวอย่างการอ่านข้อมูลจาก JSON body
$name = $data['name'] ?? null;
$email = $data['email'] ?? null;

echo json_encode([
    'name' => $name,
    'email' => $email,
]);
```

สิ่งที่ควรเข้าใจ:
- `php://input` ใช้อ่าน raw request body
- `json_decode(..., true)` แปลง JSON object เป็น associative array
- ควรตรวจเสมอว่า decode แล้วได้ข้อมูลที่ใช้งานได้จริง

---

## หัวข้อย่อยที่ 5: Validation ฝั่ง Server

API ที่ดีต้องไม่เชื่อข้อมูลจาก client ทันที ควรตรวจสอบข้อมูลทุกครั้ง เช่น required fields, email format, ความยาวข้อความ หรือชนิดข้อมูล

```php
<?php

declare(strict_types=1);

function validateUserInput(array $data): array
{
    $errors = [];

    // ตรวจว่าชื่อถูกส่งมาหรือไม่
    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    }

    // ตรวจรูปแบบอีเมล
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Invalid email format';
    }

    return $errors;
}

$input = [
    'name' => 'Anna',
    'email' => 'anna@example.com',
];

$errors = validateUserInput($input);

if ($errors !== []) {
    jsonResponse([
        'message' => 'Validation failed',
        'errors' => $errors,
    ], 422);
}
```

สิ่งที่ควรเข้าใจ:
- Validation ต้องทำที่ server เสมอ
- `422 Unprocessable Entity` เหมาะกับข้อมูลที่รูปแบบไม่ผ่าน validation
- ควรส่งรายละเอียด error กลับในรูปแบบที่ frontend อ่านต่อได้ง่าย

---

## หัวข้อย่อยที่ 6: ตัวอย่าง API แบบครบวงจร

ตัวอย่างนี้เป็น mini endpoint สำหรับสร้าง user แบบง่าย โดยรวมการอ่าน method, อ่าน JSON body, validation และการส่ง response ไว้ในไฟล์เดียว

```php
<?php

declare(strict_types=1);

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function validateUserInput(array $data): array
{
    $errors = [];

    if (empty($data['name'])) {
        $errors['name'] = 'Name is required';
    }

    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Invalid email format';
    }

    return $errors;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($method === 'POST' && $path === '/api/users') {
    $rawBody = file_get_contents('php://input');
    $data = json_decode($rawBody, true);

    if (!is_array($data)) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }

    $errors = validateUserInput($data);

    if ($errors !== []) {
        jsonResponse([
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    // จำลองการบันทึกข้อมูล
    $user = [
        'id' => 1,
        'name' => $data['name'],
        'email' => $data['email'],
    ];

    jsonResponse([
        'message' => 'User created successfully',
        'data' => $user,
    ], 201);
}

jsonResponse(['error' => 'Route not found'], 404);
```

สิ่งที่ควรเข้าใจ:
- `POST /api/users` ใช้สร้าง resource ใหม่
- `201 Created` เหมาะกับการสร้างข้อมูลสำเร็จ
- ถึงยังไม่เชื่อมฐานข้อมูล ก็สามารถออกแบบ API flow ให้ถูกต้องได้ก่อน

---

## หัวข้อย่อยที่ 7: ลองรัน API ด้วย Built-in Server

สำหรับ development สามารถใช้ built-in server ของ PHP เพื่อทดสอบ API ได้ง่าย โดยเหมาะกับการพัฒนาและเดโมในสภาพแวดล้อมควบคุม ไม่ควรใช้เป็น production server

```bash
php -S localhost:8000
```

หากไฟล์ API ชื่อ `index.php` ให้เรียกผ่าน browser หรือ client เช่น:

```bash
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Anna","email":"anna@example.com"}'
```

ผลลัพธ์ตัวอย่าง:

```json
{
  "message": "User created successfully",
  "data": {
    "id": 1,
    "name": "Anna",
    "email": "anna@example.com"
  }
}
```

สิ่งที่ควรเข้าใจ:
- `curl` ใช้ทดสอบ API ได้สะดวกมาก
- ต้องส่ง `Content-Type: application/json` เมื่อส่ง JSON body
- built-in server เหมาะกับ dev/test เท่านั้น

---

## หัวข้อย่อยที่ 8: Best Practices สำหรับ REST API เบื้องต้น

เมื่อเริ่มทำ API จริง ควรยึดแนวทางเหล่านี้ตั้งแต่แรก

- ใช้ `declare(strict_types=1);` ในไฟล์หลัก
- แยก helper เช่น `jsonResponse()` และ validation function ออกจาก route logic
- ส่ง status code ให้ตรงความหมาย
- ใช้ JSON เป็น format มาตรฐานของ request/response
- อย่าเชื่อ input จาก client โดยไม่ validate
- อย่าเปิดเผย stack trace หรือ error ภายในระบบใน production
- เมื่อเริ่มโตขึ้น ควรแยก routing, controller, service, และ database layer ออกจากกัน

---

## สรุป

หัวข้อ **REST API Basics with PHP** คือพื้นฐานสำคัญก่อนต่อยอดไปสู่ API ที่เชื่อมฐานข้อมูล, authentication, middleware หรือ framework ระดับ production หากคุณเข้าใจการอ่าน request, การส่ง JSON response, การตั้ง status code, และการทำ validation ฝั่ง server คุณจะสามารถสร้าง API ที่มีโครงสร้างถูกต้องและพร้อมขยายต่อได้ง่ายขึ้น

## References

- PHP Manual: `header()`
- PHP Manual: `http_response_code()`
- PHP Manual: `php://input`
- PHP Manual: `json_encode()` / `json_decode()`
- PHP Manual: `$_SERVER`
- PHP Manual: Built-in web server
