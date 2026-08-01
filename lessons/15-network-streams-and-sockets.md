# 15 — Network Streams and Socket Programming

## บทนำ

HTTP client ครอบคลุมงาน integration ส่วนใหญ่ แต่บางระบบต้องสื่อสารผ่าน TCP, UDP, Unix domain socket หรือ protocol ภายในองค์กร การเขียน socket code ต้องเข้าใจ framing, timeout, partial read/write, blocking mode, connection lifecycle และ TLS หากมอง socket เหมือน file ธรรมดาโดยไม่เข้าใจ network behavior จะเกิด bug ที่ตรวจยากและระบบค้างได้

บทนี้มุ่งให้ผู้เรียนเข้าใจ low-level network programming เพื่ออ่านระบบเดิม ออกแบบ internal protocol ขนาดเล็ก และตัดสินใจได้ว่าเมื่อใดควรใช้ library/protocol มาตรฐานแทนการสร้างเอง

---

## ผลลัพธ์การเรียนรู้

- เข้าใจ TCP, UDP และ Unix domain sockets
- ใช้ `stream_socket_client()` และ `stream_socket_server()`
- กำหนด timeout และ blocking mode
- จัดการ partial read/write และ message framing
- ใช้ stream context สำหรับ TLS
- ออกแบบ protocol ที่มี version, size limit และ error response
- เข้าใจ non-blocking I/O และ `stream_select()`
- ประเมินความเหมาะสมของ WebSocket, message broker และ HTTP

---

## 1. TCP vs UDP

| ประเด็น | TCP | UDP |
|---|---|---|
| Connection | มี connection | ไม่มี connection |
| Delivery | เรียงลำดับและส่งซ้ำ | ไม่รับประกัน |
| Use case | API protocol, database, file transfer | telemetry, discovery, realtime บางประเภท |
| Complexity | ง่ายกว่าสำหรับ reliable stream | application ต้องจัดการ loss/order เอง |

TCP เป็น byte stream ไม่มีขอบเขตข้อความโดยอัตโนมัติ Application ต้องออกแบบ framing เอง

---

## 2. TCP Client ด้วย Stream API

```php
<?php

declare(strict_types=1);

$errorCode = 0;
$errorMessage = '';

$connection = stream_socket_client(
    'tcp://127.0.0.1:9000',
    $errorCode,
    $errorMessage,
    2.0,
    STREAM_CLIENT_CONNECT,
);

if ($connection === false) {
    throw new RuntimeException(
        "Connection failed ({$errorCode}): {$errorMessage}",
    );
}

try {
    stream_set_timeout($connection, 3);
    fwrite($connection, "PING\n");

    $response = fgets($connection, 1024);

    if ($response === false) {
        throw new RuntimeException('Unable to read response.');
    }
} finally {
    fclose($connection);
}
```

---

## 3. Partial Write

`fwrite()` อาจเขียนไม่ครบ ต้อง loop จนจบ:

```php
function writeAll($stream, string $payload): void
{
    $offset = 0;
    $length = strlen($payload);

    while ($offset < $length) {
        $written = fwrite($stream, substr($payload, $offset));

        if ($written === false || $written === 0) {
            throw new RuntimeException('Socket write failed.');
        }

        $offset += $written;
    }
}
```

ใน production ควรหลีกเลี่ยง `substr()` ซ้ำกับ payload ใหญ่มากและติดตาม timeout/deadline ด้วย

---

## 4. Message Framing

แนวทางหลัก:

- Delimiter เช่น newline
- Fixed-length message
- Length-prefix
- Self-describing format

Length-prefix example:

```php
function encodeFrame(string $payload): string
{
    return pack('N', strlen($payload)) . $payload;
}
```

อ่านจำนวน bytes ที่แน่นอน:

```php
function readExact($stream, int $length): string
{
    $buffer = '';

    while (strlen($buffer) < $length) {
        $chunk = fread($stream, $length - strlen($buffer));

        if ($chunk === false || $chunk === '') {
            throw new RuntimeException('Unexpected end of stream.');
        }

        $buffer .= $chunk;
    }

    return $buffer;
}
```

ต้องตรวจ maximum frame size ก่อน allocate memory

---

## 5. Server แบบพื้นฐาน

```php
$server = stream_socket_server(
    'tcp://127.0.0.1:9000',
    $errorCode,
    $errorMessage,
);

if ($server === false) {
    throw new RuntimeException($errorMessage, $errorCode);
}

while ($client = stream_socket_accept($server, 5)) {
    try {
        stream_set_timeout($client, 3);
        $request = fgets($client, 1024);

        if ($request === "PING\n") {
            fwrite($client, "PONG\n");
        } else {
            fwrite($client, "ERROR unsupported-command\n");
        }
    } finally {
        fclose($client);
    }
}
```

ตัวอย่างนี้เหมาะกับการเรียนรู้ ไม่ใช่ production server เพราะรับ client ทีละ connection

---

## 6. Non-Blocking I/O

```php
stream_set_blocking($connection, false);

$read = [$connection];
$write = [];
$except = [];

$ready = stream_select($read, $write, $except, 1, 0);

if ($ready === false) {
    throw new RuntimeException('stream_select failed.');
}
```

Non-blocking I/O ต้องมี state machine ต่อ connection เช่น:

- Connecting
- Reading header
- Reading body
- Writing response
- Closing

หากระบบต้องจัดการ connections จำนวนมาก ควรใช้ event-loop library หรือ server/runtime ที่ออกแบบมาโดยเฉพาะ แทนการสร้าง loop เองทั้งหมด

---

## 7. TLS Context

```php
$context = stream_context_create([
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
        'allow_self_signed' => false,
        'peer_name' => 'service.example.com',
    ],
]);

$connection = stream_socket_client(
    'tls://service.example.com:9443',
    $errorCode,
    $errorMessage,
    3.0,
    STREAM_CLIENT_CONNECT,
    $context,
);
```

อย่าปิด certificate verification เพื่อแก้ปัญหา development แล้วนำค่าไป production

---

## 8. Unix Domain Socket

เหมาะกับ process บนเครื่องเดียวกัน:

```php
$connection = stream_socket_client(
    'unix:///var/run/example.sock',
    $errorCode,
    $errorMessage,
    1.0,
);
```

ข้อดี:

- ไม่เปิด TCP port
- ใช้ filesystem permissions
- latency ต่ำ

ต้องจัดการ permission, ownership และ stale socket file

---

## 9. UDP Example

```php
$socket = stream_socket_client(
    'udp://127.0.0.1:9999',
    $errorCode,
    $errorMessage,
    1.0,
);

if ($socket === false) {
    throw new RuntimeException($errorMessage, $errorCode);
}

fwrite($socket, json_encode([
    'type' => 'heartbeat',
    'timestamp' => time(),
], JSON_THROW_ON_ERROR));
```

UDP payload ต้องมี size limit และ application ต้องยอมรับ packet loss/duplication/order change

---

## 10. Protocol Design Checklist

ทุก protocol ควรกำหนด:

- Protocol version
- Encoding
- Frame format
- Maximum message size
- Authentication
- Integrity/confidentiality
- Timeout
- Error codes
- Idempotency
- Heartbeat/keepalive
- Compatibility policy

ไม่ควรสร้าง custom cryptography หรือ custom authentication protocol หากมีมาตรฐานที่ผ่านการตรวจสอบแล้ว

---

## 11. WebSocket และ Alternatives

ใช้ WebSocket เมื่อจำเป็นต้องมี bidirectional long-lived connection เช่น dashboard realtime หรือ notifications

พิจารณาทางเลือก:

- Server-Sent Events สำหรับ server-to-client stream
- HTTP polling สำหรับความถี่ต่ำ
- Message broker สำหรับ asynchronous integration
- gRPC/standard RPC framework สำหรับ typed service communication

PHP-FPM แบบ request/response ไม่ได้เหมาะกับ long-lived connections ทุก deployment จึงต้องเลือก runtime/architecture ให้ตรงงาน

---

## 12. Security Threats

- Unauthenticated client
- Oversized frame
- Slowloris/slow client
- Resource exhaustion
- Protocol downgrade
- Replay
- Injection ใน message fields
- Deserialization of untrusted objects
- Missing TLS verification

Controls:

- Authentication ก่อน expensive work
- Per-connection deadline
- Frame size limit
- Connection limit
- Rate limit
- Strict parser
- Structured data allowlist
- Network segmentation

---

## 13. Observability

วัด:

- Active connections
- Connection failures
- Read/write timeout
- Bytes in/out
- Frame parse errors
- Protocol versions
- Queue/backpressure
- Connection duration

ใช้ connection ID/correlation ID แต่ไม่ log raw sensitive payload

---

## แบบฝึกปฏิบัติ

1. สร้าง newline-delimited TCP echo protocol
2. เปลี่ยนเป็น length-prefixed JSON protocol
3. เพิ่ม maximum frame size
4. ทดสอบ partial reads/writes
5. เพิ่ม TLS context และตรวจ certificate
6. เปรียบเทียบ TCP socket กับ HTTP API สำหรับ use case เดียวกัน

---

## Production Checklist

- [ ] มี connect/read/write deadline
- [ ] จัดการ partial read/write
- [ ] Protocol มี framing และ size limit
- [ ] TLS verification ถูกต้อง
- [ ] Authentication และ rate limit
- [ ] Non-blocking code มี state machine
- [ ] มี backpressure
- [ ] มี metrics ต่อ connection/protocol
- [ ] ใช้มาตรฐานแทน custom protocol เมื่อทำได้

---

## References

- PHP Network Streams — https://www.php.net/manual/en/book.stream.php
- stream_socket_client — https://www.php.net/manual/en/function.stream-socket-client.php
- stream_socket_server — https://www.php.net/manual/en/function.stream-socket-server.php
- PHP Sockets Extension — https://www.php.net/manual/en/book.sockets.php
