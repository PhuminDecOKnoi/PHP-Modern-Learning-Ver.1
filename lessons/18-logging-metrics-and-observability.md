# 18 — Logging, Metrics and Observability

## บทนำ

ระบบที่ “รันได้” ยังไม่ถือว่า production-ready หากทีมไม่สามารถตอบได้ว่าเกิดอะไรขึ้น ผู้ใช้รายใดได้รับผลกระทบ ปัญหาเริ่มเมื่อใด และควรแก้ที่จุดไหน Observability เกิดจากการออกแบบ logs, metrics และ traces ให้เชื่อมโยงกัน ไม่ใช่การเพิ่ม `error_log()` หลังระบบมีปัญหา

บทนี้เน้น structured logging, PSR-3, correlation ID, metrics design, tracing concepts, data minimization และ incident-oriented diagnostics

---

## ผลลัพธ์การเรียนรู้

- ใช้ PSR-3 logger ผ่าน dependency injection
- ออกแบบ structured logs ที่ค้นหาได้
- ใช้ correlation/trace IDs
- แยก logs, metrics และ traces
- เลือก metric type และ labels อย่างถูกต้อง
- ป้องกัน secret/PII leakage
- สร้าง SLI/SLO และ alert ที่มีความหมาย
- ออกแบบ audit log แยกจาก application log

---

## 1. Logs, Metrics และ Traces

| Signal | ตอบคำถาม |
|---|---|
| Logs | เหตุการณ์รายละเอียดเกิดอะไรขึ้น |
| Metrics | ระบบมีแนวโน้มและระดับสุขภาพอย่างไร |
| Traces | Request เดินทางผ่าน components ใดและช้าที่ไหน |

ทั้งสาม signal ควรเชื่อมผ่าน identifiers และ timestamps ที่สอดคล้องกัน

---

## 2. PSR-3 Logger

```php
<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;

final readonly class EmployeeImporter
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function import(string $importId, string $path): void
    {
        $startedAt = hrtime(true);

        $this->logger->info('Employee import started.', [
            'import_id' => $importId,
            'file_path_hash' => hash('sha256', $path),
        ]);

        try {
            // Process import

            $this->logger->info('Employee import completed.', [
                'import_id' => $importId,
                'duration_ms' => (hrtime(true) - $startedAt) / 1_000_000,
            ]);
        } catch (Throwable $error) {
            $this->logger->error('Employee import failed.', [
                'import_id' => $importId,
                'exception' => $error,
            ]);

            throw $error;
        }
    }
}
```

PSR-3 กำหนดระดับ log และการส่ง context ผ่าน interface กลาง ทำให้ library ไม่ผูกกับ logger implementation

---

## 3. Log Levels

| Level | ตัวอย่าง |
|---|---|
| debug | รายละเอียดสำหรับ troubleshooting |
| info | Business/operational milestone ปกติ |
| notice | เหตุการณ์ผิดปกติเล็กน้อยที่ควรทราบ |
| warning | Degraded behavior แต่ระบบยังทำงาน |
| error | Operation ล้มเหลว |
| critical | Component สำคัญใช้งานไม่ได้ |
| alert | ต้องดำเนินการทันที |
| emergency | ระบบโดยรวมใช้งานไม่ได้ |

อย่าใช้ `error` กับทุก validation failure เพราะจะสร้าง alert noise

---

## 4. Structured Logging

แทนข้อความที่ประกอบ string:

```php
$logger->info('User {user_id} logged in from {ip}.', [
    'user_id' => $userId,
    'ip' => $clientIp,
]);
```

ควรเก็บ fields แยก:

```json
{
  "timestamp": "2026-08-01T01:15:00.123Z",
  "level": "info",
  "message": "User logged in.",
  "service": "hr-api",
  "environment": "production",
  "correlation_id": "req-123",
  "user_id": 1001
}
```

### Field naming

- ใช้ naming convention เดียวกัน
- Data types คงที่
- Timestamp เป็น UTC
- มี service/version/environment
- หลีกเลี่ยง dynamic field names

---

## 5. Correlation ID

```php
$correlationId = $_SERVER['HTTP_X_CORRELATION_ID'] ?? null;

if (!is_string($correlationId) || !preg_match('/^[A-Za-z0-9_-]{8,64}$/', $correlationId)) {
    $correlationId = bin2hex(random_bytes(16));
}

header('X-Correlation-ID: ' . $correlationId);
```

ส่ง correlation ID ต่อไปยัง:

- Downstream HTTP calls
- Queue messages
- Database audit events
- Logs
- Error responses ที่ปลอดภัย

อย่าเชื่อ inbound ID โดยไม่ validate

---

## 6. Exception Logging

Log exception ที่ boundary ที่รับผิดชอบ ไม่ log ซ้ำทุก layer จนเกิด duplicate noise

```php
try {
    $application->run();
} catch (DomainException $error) {
    // Map เป็น expected business response ไม่จำเป็นต้อง error log ทุกกรณี
} catch (Throwable $error) {
    $logger->critical('Unhandled application failure.', [
        'exception' => $error,
        'correlation_id' => $correlationId,
    ]);

    http_response_code(500);
}
```

ห้ามส่ง stack trace ให้ end user ใน production

---

## 7. Secret and PII Redaction

ข้อมูลที่ไม่ควร log:

- Password
- Access/refresh token
- Session ID
- API key
- Database connection string
- Full national ID
- Medical/financial details
- Raw uploaded document

สร้าง redaction processor:

```php
function redactContext(array $context): array
{
    $sensitiveKeys = [
        'password',
        'token',
        'authorization',
        'session_id',
        'api_key',
    ];

    foreach ($context as $key => $value) {
        if (in_array(strtolower((string) $key), $sensitiveKeys, true)) {
            $context[$key] = '[REDACTED]';
        }
    }

    return $context;
}
```

Redaction ต้องรองรับ nested structures และ header variants ใน implementation จริง

---

## 8. Metrics Types

| Type | ใช้กับ |
|---|---|
| Counter | Request count, error count |
| Gauge | Queue depth, active workers |
| Histogram | Request latency, file size |
| Summary | Client-side quantile บางระบบ |

ตัวอย่าง names:

```text
http_requests_total
http_request_duration_seconds
queue_oldest_message_age_seconds
employee_import_rows_total
employee_import_failures_total
```

---

## 9. Label Cardinality

ห้ามใช้ labels ที่มีค่าจำนวนมาก เช่น:

- user_id
- request_id
- email
- full URL with IDs
- exception message

ใช้ labels แบบ bounded:

```text
method=GET
route=/employees/{id}
status_class=2xx
service=directory
```

High-cardinality data ควรอยู่ใน logs/traces ไม่ใช่ metric labels

---

## 10. SLI, SLO และ Error Budget

ตัวอย่าง SLI:

- Availability
- Successful request ratio
- p95 latency
- Import completion within 10 minutes
- Queue age under threshold

ตัวอย่าง SLO:

```text
99.9% ของ GET /employees ต้องสำเร็จภายใน 500 ms ต่อเดือน
```

Error budget ช่วยตัดสินสมดุลระหว่าง reliability กับการเปลี่ยนแปลงระบบ

---

## 11. Alert Design

Alert ที่ดีต้อง:

- Actionable
- มี owner
- มี severity
- มี runbook
- อิง user impact หรือ leading indicator ที่สำคัญ
- ไม่เกิดจาก spike สั้นที่ไม่มีผลกระทบ

ตัวอย่าง:

- Error rate สูงต่อเนื่อง
- Queue oldest age เกิน SLO
- Database connection exhaustion
- Disk space ต่ำ
- Certificate ใกล้หมดอายุ

---

## 12. Distributed Tracing Concept

Trace ประกอบด้วย spans:

```text
HTTP Request
├── Authentication
├── Database Query
├── Redis Cache
└── External API
```

Span attributes ควรมี operation name, status, duration และ safe metadata โดยไม่ใส่ PII เกินจำเป็น

---

## 13. Audit Log vs Application Log

Audit log ตอบว่า:

- ใครทำอะไร
- กับข้อมูลใด
- เมื่อใด
- ผ่านช่องทางใด
- ผลลัพธ์อะไร

Application log ตอบเรื่อง technical operation

Audit log ต้องมี:

- Immutable/tamper-evident controls ตามความเสี่ยง
- Retention policy
- Access control
- Time synchronization
- Before/after representation ที่ไม่เปิดเผย secret

อย่าใช้ application log ทดแทน legal/compliance audit trail โดยอัตโนมัติ

---

## 14. Retention and Cost

กำหนด:

- Hot retention
- Archive retention
- Legal hold
- Deletion schedule
- Sampling
- Log level per environment
- Storage budget

การเก็บทุกอย่างตลอดไปเพิ่มทั้งค่าใช้จ่ายและ privacy risk

---

## 15. Health Checks

แยก:

- Liveness — process ยังทำงาน
- Readiness — พร้อมรับ traffic
- Dependency health — database/cache/downstream

Readiness ไม่ควรล้มเพียงเพราะ optional dependency ใช้งานไม่ได้ หากระบบยังให้บริการหลักได้

---

## แบบฝึกปฏิบัติ

1. เปลี่ยน string logs เป็น structured logs
2. เพิ่ม correlation ID ผ่าน HTTP และ queue
3. ออกแบบ redaction tests
4. สร้าง metrics สำหรับ CSV import
5. กำหนด SLO และ alert สำหรับ API
6. แยก audit log จาก application log

---

## Production Checklist

- [ ] ใช้ PSR-3 interface
- [ ] Logs เป็น structured data
- [ ] มี correlation ID
- [ ] Secrets/PII ถูก redact
- [ ] Metrics labels มี cardinality จำกัด
- [ ] มี SLI/SLO และ actionable alerts
- [ ] Exceptions log ที่ boundary
- [ ] Audit log แยกตาม requirement
- [ ] Retention และ access control ชัดเจน
- [ ] Clock/timezone synchronized

---

## References

- PSR-3 Logger Interface — https://www.php-fig.org/psr/psr-3/
- PHP Error Handling — https://www.php.net/manual/en/book.errorfunc.php
- OpenTelemetry PHP — https://opentelemetry.io/docs/languages/php/
