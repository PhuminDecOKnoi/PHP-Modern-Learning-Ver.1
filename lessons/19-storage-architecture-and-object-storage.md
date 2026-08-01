# 19 — Storage Architecture and Object Storage

## บทนำ

คำว่า “เก็บไฟล์” มีหลายความหมาย ตั้งแต่ local disk, shared filesystem, database BLOB, object storage ไปจนถึง archive tier การเลือก storage ผิดประเภททำให้เกิดปัญหา scalability, backup, security, retention และ deployment ผู้พัฒนาจึงต้องแยก **file metadata**, **binary content**, **source of truth**, **temporary data** และ **delivery channel** ออกจากกัน

บทนี้สอนการออกแบบ storage abstraction ที่เปลี่ยน backend ได้ ปลอดภัย และมี lifecycle governance เหมาะกับเอกสารพนักงาน รายงาน audit และไฟล์ import/export

---

## ผลลัพธ์การเรียนรู้

- เปรียบเทียบ local, shared, database และ object storage
- ออกแบบ storage key และ metadata schema
- ใช้ presigned URL concept อย่างปลอดภัย
- จัดการ upload/download แบบ streaming
- กำหนด retention, lifecycle และ legal hold
- ออกแบบ integrity verification และ encryption
- ป้องกัน overwrite, path traversal และ confused-deputy problems
- สร้าง storage interface ที่ test ได้

---

## 1. Storage Options

| Storage | เหมาะกับ | ข้อจำกัด |
|---|---|---|
| Local filesystem | Single host, temporary files | ไม่เหมาะกับหลาย instances/failover |
| Shared filesystem | Legacy/shared processing | Locking, latency, mount dependency |
| Database BLOB | Atomic metadata+small binary บางกรณี | DB size, backup และ throughput |
| Object storage | Large files, scale, lifecycle | Eventual behaviors/API dependency |
| Archive storage | Long retention, rare access | Retrieval delay/cost |

ไม่มี storage แบบเดียวที่ดีที่สุดทุกงาน

---

## 2. Metadata แยกจาก Binary

Database table:

```sql
CREATE TABLE stored_documents (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    storage_key VARCHAR(512) NOT NULL UNIQUE,
    original_name VARCHAR(255) NOT NULL,
    content_type VARCHAR(100) NOT NULL,
    size_bytes BIGINT NOT NULL,
    sha256 CHAR(64) NOT NULL,
    status VARCHAR(30) NOT NULL,
    retention_until DATE,
    created_at TIMESTAMP NOT NULL,
    created_by BIGINT NOT NULL
);
```

เก็บ binary ใน object storage และเก็บ metadata/index/authorization ใน database

---

## 3. Storage Key Design

```text
org/{tenant-id}/documents/{yyyy}/{mm}/{uuid}.pdf
imports/{tenant-id}/{import-id}/source.csv
exports/{tenant-id}/{job-id}/result.zip
```

หลักปฏิบัติ:

- ใช้ opaque ID ไม่ใช้ filename เป็น key หลัก
- มี tenant boundary
- ไม่ใส่ PII ใน key
- ไม่พึ่ง directory semantics มากเกินไป
- Key immutable เมื่อทำได้
- เก็บ original filename เป็น metadata

---

## 4. Storage Interface

```php
<?php

declare(strict_types=1);

interface ObjectStorage
{
    /** @param resource $stream */
    public function put(
        string $key,
        $stream,
        string $contentType,
        array $metadata = [],
    ): StoredObject;

    /** @return resource */
    public function readStream(string $key);

    public function delete(string $key): void;

    public function exists(string $key): bool;
}
```

Application layer ไม่ควรรู้ vendor SDK โดยตรง

---

## 5. Streaming Upload

```php
$input = fopen($temporaryPath, 'rb');

if ($input === false) {
    throw new RuntimeException('Unable to open upload stream.');
}

try {
    $stored = $storage->put(
        key: $storageKey,
        stream: $input,
        contentType: $verifiedMime,
        metadata: [
            'document_id' => (string) $documentId,
            'schema_version' => '1',
        ],
    );
} finally {
    fclose($input);
}
```

หลีกเลี่ยงการอ่านไฟล์ทั้งก้อนเข้า string ก่อน upload

---

## 6. Integrity Verification

คำนวณ SHA-256 ระหว่างรับไฟล์หรือก่อนจัดเก็บ:

```php
$hash = hash_file('sha256', $temporaryPath);

if ($hash === false) {
    throw new RuntimeException('Unable to calculate file hash.');
}
```

ใช้ hash เพื่อ:

- ตรวจ corruption
- ยืนยัน download
- Deduplication ตาม policy
- Audit evidence

Hash ไม่แทน digital signature หรือ authenticity proof

---

## 7. Presigned URL Concept

Presigned URL ให้ client upload/download โดยตรงภายในเวลาจำกัด

ต้องกำหนด:

- Short expiration
- Exact object key
- Allowed method
- Content type/size constraints เมื่อ platform รองรับ
- Authorization ก่อนออก URL
- Audit event
- One-time workflow เมื่อจำเป็น

อย่าออก presigned URL จาก key ที่ผู้ใช้กำหนดโดยไม่ตรวจ tenant/ownership

---

## 8. Upload State Machine

```text
initiated
  -> uploaded
  -> scanning
  -> accepted
  -> available
  -> quarantined/rejected
```

อย่าถือว่า upload สำเร็จแล้วไฟล์พร้อมใช้งานทันที หากต้อง malware scan, content validation หรือ metadata extraction

---

## 9. Confused Deputy Protection

เมื่อ backend มีสิทธิ์อ่าน object ทั้ง bucket ผู้ใช้ไม่ควรส่ง key ใดก็ได้ให้ backend download

แนวทาง:

1. รับ document ID
2. Query metadata ตาม tenant/user authorization
3. อ่าน storage key จาก trusted database
4. เรียก storage
5. Audit access

ห้ามใช้ `GET /download?key=...` โดยตรง

---

## 10. Encryption

พิจารณา:

- TLS in transit
- Server-side encryption at rest
- Customer-managed keys ตาม requirement
- Application-level envelope encryption สำหรับข้อมูลอ่อนไหวสูง
- Key rotation
- Separation of duties

Encryption ไม่แทน access control และ retention policy

---

## 11. Versioning และ Immutability

เอกสาร audit/compliance อาจต้อง:

- Object versioning
- Write-once retention
- Legal hold
- Hash chain/digital signature
- Immutable audit metadata

กำหนดชัดว่า delete หมายถึง soft delete, logical hide หรือ physical deletion

---

## 12. Lifecycle และ Retention

ตัวอย่าง policy:

```text
0–30 วัน      hot storage
31–365 วัน    standard/infrequent access
หลัง 1 ปี     archive tier
ครบ retention delete เว้น legal hold
```

ต้องพิจารณา:

- Legal basis
- Business requirement
- Restore time
- Retrieval cost
- Cross-region replication
- Deletion verification

---

## 13. Database BLOB Decision

เก็บ BLOB ใน database เมื่อ:

- ไฟล์เล็ก
- ต้อง atomic transaction กับ record
- ปริมาณไม่สูง
- Backup/restore impact ยอมรับได้

ไม่ควรเมื่อ:

- ไฟล์ใหญ่/จำนวนมาก
- ต้อง CDN/direct delivery
- Database backup โตเร็ว
- Binary throughput รบกวน OLTP workload

---

## 14. CDN และ Content Delivery

สำหรับ public/static content อาจใช้ CDN แต่เอกสารส่วนบุคคลต้อง:

- Private origin
- Signed URL/cookie
- Short cache policy
- Authorization-aware delivery
- Cache purge strategy
- No-store สำหรับข้อมูลอ่อนไหวตาม requirement

---

## 15. Backup and Disaster Recovery

Object storage durability ไม่เท่ากับ backup

ต้องมี:

- Versioning/replication ตาม risk
- Metadata database backup
- Cross-account/region copy เมื่อจำเป็น
- Restore test
- RPO/RTO
- Protection from accidental/malicious deletion

Binary และ metadata ต้อง restore ให้สัมพันธ์กัน

---

## 16. Cost Governance

ต้นทุนประกอบด้วย:

- Storage capacity
- API requests
- Data transfer
- Retrieval
- Early deletion
- Replication
- Archive restore

เก็บ metrics ตาม tenant/file class และกำหนด lifecycle อัตโนมัติ

---

## 17. Testing

- Fake/in-memory storage สำหรับ unit tests
- Integration test กับ storage emulator/service
- Test partial upload
- Test checksum mismatch
- Test unauthorized access
- Test expired presigned URL
- Test retention/legal hold
- Test restore metadata + binary

---

## แบบฝึกปฏิบัติ

1. ออกแบบ metadata schema สำหรับเอกสารพนักงาน
2. สร้าง storage interface และ local implementation
3. เปลี่ยนเป็น object-storage adapter โดยไม่แก้ business logic
4. ออกแบบ upload state machine
5. วิเคราะห์ confused-deputy vulnerability
6. สร้าง retention matrix ตามประเภทเอกสาร

---

## Production Checklist

- [ ] Binary แยกจาก metadata อย่างมีเหตุผล
- [ ] Storage key opaque และไม่มี PII
- [ ] Upload/download เป็น streaming
- [ ] ตรวจ size, MIME และ hash
- [ ] Authorization ผ่าน document metadata
- [ ] Presigned URL อายุสั้นและขอบเขตแคบ
- [ ] มี scan/quarantine workflow
- [ ] Encryption และ key management ชัดเจน
- [ ] Retention/legal hold ถูกบังคับ
- [ ] Backup/restore ทดสอบจริง
- [ ] Monitor cost และ orphan objects

---

## References

- PHP Streams — https://www.php.net/manual/en/book.stream.php
- OWASP File Upload Cheat Sheet — https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
- AWS S3 Design Patterns — https://docs.aws.amazon.com/AmazonS3/latest/userguide/Welcome.html
- Google Cloud Storage Documentation — https://cloud.google.com/storage/docs
- Azure Blob Storage Documentation — https://learn.microsoft.com/azure/storage/blobs/
