# 04 — REST API and Input Validation

## บทนำ

บทนี้สอนการสร้าง REST API ด้วย PHP แบบไม่พึ่ง framework เพื่อให้เข้าใจ request, routing, JSON, status codes, validation และ error boundaries ก่อนต่อยอดไปใช้ Laravel, Symfony หรือ Slim

## เป้าหมายการเรียนรู้

- อ่าน HTTP method และ path
- รับ JSON body อย่างปลอดภัย
- ใช้ `JSON_THROW_ON_ERROR`
- แยก validation ออกจาก response logic
- ส่ง status code ที่เหมาะสม
- ออกแบบ error response ที่ frontend ใช้งานต่อได้

## 1. Response Helper

```php
<?php

declare(strict_types=1);

function jsonResponse(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    echo json_encode(
        $payload,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}
```

`never` สื่อว่าฟังก์ชันไม่คืน control กลับไปยัง caller เพราะจบด้วย `exit`

## 2. อ่าน Method และ Path

```php
<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($method === 'GET' && $path === '/api/health') {
    jsonResponse([
        'status' => 'ok',
        'php' => PHP_VERSION,
    ]);
}

jsonResponse([
    'error' => [
        'code' => 'ROUTE_NOT_FOUND',
        'message' => 'Route not found.',
    ],
], 404);
```

## 3. Decode JSON อย่างปลอดภัย

```php
<?php

declare(strict_types=1);

function readJsonBody(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (!str_starts_with(strtolower($contentType), 'application/json')) {
        jsonResponse([
            'error' => [
                'code' => 'UNSUPPORTED_MEDIA_TYPE',
                'message' => 'Content-Type must be application/json.',
            ],
        ], 415);
    }

    $rawBody = file_get_contents('php://input');

    if ($rawBody === false || trim($rawBody) === '') {
        jsonResponse([
            'error' => [
                'code' => 'EMPTY_BODY',
                'message' => 'JSON body is required.',
            ],
        ], 400);
    }

    try {
        $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        jsonResponse([
            'error' => [
                'code' => 'INVALID_JSON',
                'message' => 'Request body contains invalid JSON.',
            ],
        ], 400);
    }

    if (!is_array($decoded)) {
        jsonResponse([
            'error' => [
                'code' => 'INVALID_JSON_SHAPE',
                'message' => 'JSON object is required.',
            ],
        ], 400);
    }

    return $decoded;
}
```

### ทำไมใช้ `JSON_THROW_ON_ERROR`

- ไม่ต้องตรวจ `json_last_error()` แยก
- error flow ชัดเจนด้วย exception
- ลดกรณี decode ล้มเหลวแต่โค้ดยังทำงานต่อ

## 4. Validation

```php
<?php

declare(strict_types=1);

function validateCreateUser(array $input): array
{
    $errors = [];

    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));

    if ($name === '') {
        $errors['name'][] = 'Name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'][] = 'Name must not exceed 100 characters.';
    }

    if ($email === '') {
        $errors['email'][] = 'Email is required.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'][] = 'Email format is invalid.';
    }

    return $errors;
}
```

Validation ตรวจความถูกต้องของข้อมูล ส่วน authorization ตรวจว่าผู้ใช้มีสิทธิ์ทำ operation หรือไม่ ทั้งสองเรื่องต้องแยกจากกัน

## 5. POST Endpoint

```php
<?php

declare(strict_types=1);

if ($method === 'POST' && $path === '/api/users') {
    $input = readJsonBody();
    $errors = validateCreateUser($input);

    if ($errors !== []) {
        jsonResponse([
            'error' => [
                'code' => 'VALIDATION_FAILED',
                'message' => 'The submitted data is invalid.',
                'fields' => $errors,
            ],
        ], 422);
    }

    $user = [
        'id' => 1,
        'name' => trim((string) $input['name']),
        'email' => strtolower(trim((string) $input['email'])),
    ];

    header('Location: /api/users/1');

    jsonResponse([
        'data' => $user,
    ], 201);
}
```

## 6. Status Codes ที่ใช้บ่อย

| Code | ความหมาย |
|---:|---|
| 200 | request สำเร็จ |
| 201 | สร้าง resource สำเร็จ |
| 204 | สำเร็จและไม่มี response body |
| 400 | request ผิดรูปแบบ |
| 401 | ยังไม่ยืนยันตัวตน |
| 403 | ยืนยันตัวตนแล้วแต่ไม่มีสิทธิ์ |
| 404 | ไม่พบ resource/route |
| 409 | conflict เช่นข้อมูลซ้ำ |
| 415 | media type ไม่รองรับ |
| 422 | validation ไม่ผ่าน |
| 429 | request มากเกินกำหนด |
| 500 | server error |

## 7. Global Error Boundary

```php
<?php

declare(strict_types=1);

try {
    // dispatch route
} catch (Throwable $exception) {
    error_log(sprintf(
        '%s: %s in %s:%d',
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));

    jsonResponse([
        'error' => [
            'code' => 'INTERNAL_SERVER_ERROR',
            'message' => 'An unexpected error occurred.',
        ],
    ], 500);
}
```

อย่าส่ง stack trace หรือ exception message ภายในออกสู่ public API ใน production

## 8. CORS

CORS ไม่ใช่ authentication และไม่ใช่ access control ของ server

ตัวอย่างแบบจำกัด origin:

```php
<?php

declare(strict_types=1);

$allowedOrigins = [
    'https://app.example.com',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
}
```

ไม่ควรสะท้อน origin ใด ๆ กลับโดยไม่มี allowlist โดยเฉพาะ API ที่ใช้ credentials

## 9. Security Checklist

- [ ] บังคับ HTTPS ใน production
- [ ] จำกัด request body size ที่ web server/reverse proxy
- [ ] ตรวจ `Content-Type`
- [ ] ใช้ `JSON_THROW_ON_ERROR`
- [ ] validate ทุก field ฝั่ง server
- [ ] ใช้ authentication และ authorization แยกกัน
- [ ] ใช้ rate limiting กับ endpoint สำคัญ
- [ ] log error โดยไม่ log password/token
- [ ] ใช้ prepared statements เมื่อเชื่อม database
- [ ] มี request ID/correlation ID สำหรับ tracing เมื่อระบบใหญ่ขึ้น

## 10. แบบฝึกหัด

1. เพิ่ม `GET /api/users/{id}`
2. เพิ่ม validation สำหรับ `role` ด้วย allowlist
3. ส่ง `409 Conflict` เมื่อ email ซ้ำ
4. เพิ่ม error code แบบคงที่ให้ทุก response
5. เขียน test ให้ `validateCreateUser()`

## References

- https://www.php.net/manual/en/function.json-decode.php
- https://www.php.net/manual/en/json.constants.php
- https://www.rfc-editor.org/rfc/rfc9110
- https://cheatsheetseries.owasp.org/
