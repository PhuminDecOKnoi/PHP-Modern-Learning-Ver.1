# 17 — Queue, Workers and Background Jobs

## บทนำ

งานบางประเภทไม่ควรทำภายใน HTTP request เช่นส่งอีเมล สร้างรายงานขนาดใหญ่ import ไฟล์ sync API และประมวลผลเอกสาร การย้ายงานไป queue ช่วยลด response time และแยกภาระงาน แต่เพิ่มความซับซ้อนด้าน delivery guarantee, retry, duplicate processing, ordering, visibility timeout และ operational monitoring

เป้าหมายของบทนี้คือให้ออกแบบ background processing ที่ **ทำซ้ำได้อย่างปลอดภัย ตรวจสอบย้อนหลังได้ และหยุด/กู้คืนได้** ไม่ใช่เพียงสร้าง infinite loop

---

## ผลลัพธ์การเรียนรู้

- แยก synchronous และ asynchronous workloads
- ออกแบบ job message และ version
- เข้าใจ at-most-once, at-least-once และ effectively-once
- สร้าง idempotent job handler
- ใช้ retry, backoff, jitter และ dead-letter queue
- จัดการ worker lifecycle และ graceful shutdown
- ออกแบบ scheduling, priority และ backpressure
- วัด queue latency และ failure rate

---

## 1. เมื่อใดควรใช้ Queue

เหมาะกับ:

- Email/notification
- Report generation
- CSV/XML import
- Image/document processing
- External API synchronization
- Audit event enrichment
- Batch recalculation

ไม่ควรย้ายทุกอย่างไป queue หาก caller ต้องการผลทันทีหรือ transaction ต้องยืนยันก่อนตอบ

---

## 2. Job Envelope

```json
{
  "messageId": "01J4...",
  "type": "employee.import.requested",
  "version": 1,
  "occurredAt": "2026-08-01T01:00:00Z",
  "correlationId": "request-123",
  "tenantId": "org-001",
  "payload": {
    "importId": 456,
    "storageKey": "imports/456.csv"
  }
}
```

ควรมี:

- Unique message ID
- Message type
- Schema version
- Correlation/causation ID
- Minimal payload
- Reference ไป source data แทน payload ใหญ่

อย่าใส่ secret หรือข้อมูลส่วนบุคคลเกินจำเป็น

---

## 3. Delivery Semantics

| Semantics | ความหมาย |
|---|---|
| At-most-once | อาจหาย แต่ไม่ประมวลผลซ้ำ |
| At-least-once | ไม่ควรหาย แต่มีโอกาสซ้ำ |
| Exactly-once | มักเป็นคำอธิบายที่ต้องตรวจข้อจำกัดอย่างละเอียด |

ระบบ queue ส่วนใหญ่ต้องออกแบบ handler ให้รับ duplicate ได้ เพราะ at-least-once delivery เป็นรูปแบบที่พบบ่อย

---

## 4. Idempotent Handler

```php
final readonly class ImportEmployeeFileHandler
{
    public function __construct(
        private ProcessedMessageRepository $processed,
        private ImportService $imports,
    ) {
    }

    public function __invoke(ImportEmployeeFile $job): void
    {
        if ($this->processed->exists($job->messageId)) {
            return;
        }

        $this->imports->process($job->importId, $job->storageKey);
        $this->processed->markProcessed($job->messageId);
    }
}
```

ตัวอย่างนี้ยังมีช่องว่างระหว่าง process กับ mark processed จึงควรใช้ transaction/outbox/inbox pattern ตาม datastore และผลกระทบของงาน

---

## 5. Inbox Pattern

บันทึก message ID ในฐานข้อมูลเดียวกับ business update:

```sql
BEGIN;

INSERT INTO consumed_messages (message_id, consumed_at)
VALUES (:message_id, CURRENT_TIMESTAMP)
ON CONFLICT DO NOTHING;

-- ตรวจว่ามี insert จริงก่อนทำ business update

UPDATE imports
SET status = 'completed'
WHERE id = :import_id;

COMMIT;
```

ช่วยให้ duplicate ถูกปฏิเสธภายใน transaction boundary เดียวกัน

---

## 6. Transactional Outbox

ปัญหา dual write:

1. Update database สำเร็จ
2. Publish event ล้มเหลว

Outbox pattern:

```sql
BEGIN;

UPDATE employees
SET status = 'inactive'
WHERE id = :employee_id;

INSERT INTO outbox_messages (
    message_id,
    message_type,
    payload,
    created_at
) VALUES (
    :message_id,
    'employee.deactivated',
    :payload,
    CURRENT_TIMESTAMP
);

COMMIT;
```

Outbox publisher อ่าน row ที่ยังไม่ publish แล้วส่งเข้า broker ภายหลัง

---

## 7. Retry Classification

Retryable:

- Temporary network failure
- 429/503 ตาม policy
- Lock/deadlock บางชนิด
- Temporary storage unavailable

Non-retryable:

- Invalid schema
- Missing required business entity แบบถาวร
- Unsupported message version
- Permission denied ที่ต้องแก้ configuration

```php
interface RetryableJobException
{
}
```

ควรแยก exception categories แทน retry ทุก `Throwable`

---

## 8. Backoff and Jitter

```php
function retryDelaySeconds(int $attempt): int
{
    $base = min(300, 2 ** min($attempt, 8));
    return $base + random_int(0, max(1, intdiv($base, 4)));
}
```

ต้องมี:

- Maximum attempts
- Maximum age
- Total deadline
- Retry metadata
- Alert เมื่อเกิน threshold

---

## 9. Dead-Letter Queue

เมื่อ job ล้มเหลวถาวรให้ย้ายไป DLQ พร้อม:

- Original message
- Failure category
- Error summary
- Attempt count
- First/last failed time
- Worker version

DLQ ต้องมี owner, review cadence และ replay procedure ไม่ใช่สุสานข้อความ

---

## 10. Worker Loop

Pseudo-code:

```php
while (!$shutdownRequested) {
    $message = $queue->reserve(timeoutSeconds: 5);

    if ($message === null) {
        continue;
    }

    try {
        $dispatcher->handle($message);
        $queue->acknowledge($message);
    } catch (RetryableException $error) {
        $queue->retry($message, retryDelaySeconds($message->attempt));
    } catch (Throwable $error) {
        $queue->deadLetter($message, $error);
    }
}
```

Production worker ต้องจัดการ memory leak, connection refresh และ max jobs per process

---

## 11. Graceful Shutdown

เมื่อได้รับ SIGTERM:

1. หยุด reserve job ใหม่
2. ทำ current job ให้จบภายใน deadline
3. Ack/release อย่างถูกต้อง
4. Flush logs/metrics
5. ปิด connections
6. Exit ด้วย code ที่เหมาะสม

ใน PHP CLI สามารถใช้ `pcntl` เมื่อ environment รองรับ

---

## 12. Visibility Timeout

Queue บางระบบซ่อน message ระหว่าง worker ประมวลผล หาก worker ไม่ ack ภายใน timeout message จะปรากฏอีกครั้ง

ต้องกำหนด timeout มากกว่างานปกติ และมี heartbeat/lease extension สำหรับงานยาว พร้อม idempotency เสมอ

---

## 13. Ordering

Global ordering ลด scalability มาก ควรถามว่าต้องเรียงระดับใด:

- ต่อ employee
- ต่อ tenant
- ต่อ aggregate
- ต่อ partition

ใช้ partition key เพื่อรักษาลำดับเฉพาะขอบเขตที่จำเป็น

---

## 14. Backpressure

เมื่อ producer เร็วกว่า consumer:

- จำกัด enqueue rate
- Scale workers
- แยก priority queues
- Reject/defer non-critical jobs
- Batch operations
- แจ้ง queue age threshold

Queue ไม่ได้ทำให้ capacity problem หายไป เพียงช่วย buffer ชั่วคราว

---

## 15. Scheduled Jobs

Scheduler ควร enqueue job ไม่ควรทำงานหนักเอง

ต้องจัดการ:

- Timezone
- Missed schedule
- Duplicate scheduler instances
- Leader election/lock
- Catch-up policy
- Manual rerun

ใช้ UTC สำหรับ schedule engine และแปลง business timezone อย่างชัดเจน

---

## 16. Observability

Metrics:

- Queue depth
- Oldest message age
- Enqueue rate
- Completion rate
- Retry rate
- DLQ count
- Processing duration
- Worker utilization

Log:

- Message ID
- Job type/version
- Correlation ID
- Attempt
- Outcome
- Duration

---

## แบบฝึกปฏิบัติ

1. ออกแบบ job envelope สำหรับ CSV import
2. สร้าง idempotent email job
3. ใช้ outbox pattern กับ employee status update
4. แยก retryable/non-retryable errors
5. ออกแบบ DLQ replay procedure
6. สร้าง graceful shutdown checklist

---

## Production Checklist

- [ ] Job มี ID/type/version
- [ ] Handler idempotent
- [ ] Dual write ใช้ outbox หรือกลไกเทียบเท่า
- [ ] Retry แยกตาม error category
- [ ] มี backoff/jitter/max attempts
- [ ] DLQ มี owner และ replay process
- [ ] Worker graceful shutdown ได้
- [ ] มี visibility timeout/heartbeat policy
- [ ] Monitor queue age ไม่ใช่แค่ queue depth
- [ ] Payload ไม่เก็บ secret/PII เกินจำเป็น

---

## References

- PHP CLI — https://www.php.net/manual/en/features.commandline.php
- PHP PCNTL — https://www.php.net/manual/en/book.pcntl.php
- Enterprise Integration Patterns — https://www.enterpriseintegrationpatterns.com/
