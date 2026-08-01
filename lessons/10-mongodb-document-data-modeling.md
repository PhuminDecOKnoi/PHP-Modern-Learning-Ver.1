# 10 — MongoDB and Professional Document Data Modeling

## บทนำ

MongoDB เป็น document database ที่เก็บข้อมูลในรูปแบบ BSON document แทน row และ table แบบ relational database จุดแข็งไม่ได้อยู่ที่คำว่า “ไม่ต้องมี schema” แต่คือความสามารถในการออกแบบ aggregate ที่ข้อมูลซึ่งถูกอ่านและเปลี่ยนแปลงร่วมกันสามารถอยู่ใน document เดียวได้

ผู้พัฒนาระดับมืออาชีพต้องเข้าใจว่า MongoDB ไม่ใช่ตัวแทน MySQL/PostgreSQL โดยอัตโนมัติ การเลือกใช้ต้องพิจารณา access pattern, consistency boundary, document growth, indexing, transaction requirement และ operational complexity

---

## ผลลัพธ์การเรียนรู้

- เข้าใจ BSON, document, collection และ ObjectId
- ติดตั้ง MongoDB PHP extension และ high-level library
- เชื่อมต่อผ่าน `MongoDB\Client`
- ออกแบบ embedded และ referenced documents
- ใช้ CRUD, projection, indexes และ aggregation
- เข้าใจ write concern, read concern และ transactions
- ป้องกัน operator injection และข้อมูลที่มีโครงสร้างไม่คาดคิด
- ตัดสินใจได้ว่าเมื่อใดควรใช้ document database

---

## 1. Driver Architecture

MongoDB PHP stack มีสองชั้นหลัก:

1. `mongodb` extension — connection management, BSON, cursors และ low-level operations
2. `mongodb/mongodb` library — high-level API สำหรับ Client, Database และ Collection

ติดตั้ง extension ตาม environment และติดตั้ง library ผ่าน Composer:

```bash
composer require mongodb/mongodb
```

ตรวจสอบ extension:

```bash
php --ri mongodb
```

> โปรเจกต์ทั่วไปควรใช้ high-level library ไม่ควรเขียน application โดยพึ่ง low-level driver โดยตรงทั้งหมด

---

## 2. Connection และ Secret Management

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use RuntimeException;

$uri = $_ENV['MONGODB_URI'] ?? '';

if ($uri === '') {
    throw new RuntimeException('MONGODB_URI is required.');
}

$client = new Client($uri);
$database = $client->selectDatabase('hr_compliance');
$employees = $database->selectCollection('employees');
```

### Professional baseline

- เก็บ URI ใน environment/secret manager
- เปิด TLS ตาม deployment requirement
- จำกัด network access
- ใช้ database user ตาม least privilege
- กำหนด server selection และ connect timeout
- ไม่ log credential หรือ URI เต็ม

---

## 3. BSON ไม่ใช่ JSON ธรรมดา

BSON รองรับชนิดข้อมูลที่ JSON ไม่มี เช่น:

- ObjectId
- UTCDateTime
- Decimal128
- Binary
- Timestamp
- Regular expression

ตัวอย่าง document:

```php
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

$document = [
    '_id' => new ObjectId(),
    'employeeCode' => 'EMP-0001',
    'fullName' => 'Example Employee',
    'hiredAt' => new UTCDateTime(
        new DateTimeImmutable('2026-01-15T00:00:00Z'),
    ),
    'skills' => ['PHP', 'SQL', 'Audit'],
];
```

อย่าแปลงวันที่เป็น string ทุกกรณี เพราะจะเสีย semantics ของ date query และ index

---

## 4. Embed หรือ Reference

### Embedded document

```json
{
  "employeeCode": "EMP-0001",
  "profile": {
    "fullName": "Example Employee",
    "department": "Compliance"
  },
  "contacts": [
    {"type": "email", "value": "employee@example.com"}
  ]
}
```

เหมาะเมื่อ:

- อ่านข้อมูลพร้อมกัน
- เปลี่ยนแปลงใน consistency boundary เดียว
- จำนวนสมาชิกมีขอบเขต
- ไม่ถูกแชร์กับ aggregate อื่นจำนวนมาก

### Referenced document

```json
{
  "employeeCode": "EMP-0001",
  "departmentId": {"$oid": "..."}
}
```

เหมาะเมื่อ:

- ข้อมูลถูกแชร์หลาย document
- child collection โตไม่จำกัด
- lifecycle แยกจาก parent
- ต้อง query child โดยอิสระ

### Anti-pattern

- ฝัง array ที่โตไม่จำกัด
- duplicate ข้อมูลโดยไม่มี update strategy
- reference ทุกอย่างจนต้องทำ client-side join จำนวนมาก
- สร้าง document ตามหน้าตา form แทน access pattern

---

## 5. Insert และ Schema Validation

```php
$result = $employees->insertOne([
    'employeeCode' => 'EMP-0001',
    'fullName' => 'Example Employee',
    'status' => 'active',
    'skills' => ['PHP', 'Audit'],
    'createdAt' => new UTCDateTime(),
]);

$insertedId = $result->getInsertedId();
```

MongoDB มี collection-level schema validation จึงควรใช้เพื่อป้องกัน document ที่ผิดรูปแบบ

ตัวอย่างแนวคิด validator:

```javascript
{
  $jsonSchema: {
    bsonType: 'object',
    required: ['employeeCode', 'fullName', 'status'],
    properties: {
      employeeCode: { bsonType: 'string' },
      fullName: { bsonType: 'string' },
      status: { enum: ['active', 'inactive'] }
    }
  }
}
```

Application validation และ database validation ควรทำงานร่วมกัน ไม่ใช่เลือกอย่างใดอย่างหนึ่ง

---

## 6. Query, Projection และ Limit

```php
$cursor = $employees->find(
    ['status' => 'active'],
    [
        'projection' => [
            '_id' => 1,
            'employeeCode' => 1,
            'fullName' => 1,
        ],
        'sort' => ['employeeCode' => 1],
        'limit' => 100,
    ],
);

foreach ($cursor as $employee) {
    // Map BSON document ไปยัง DTO/domain object ก่อนส่งต่อ
}
```

### หลักปฏิบัติ

- ใช้ projection ลดข้อมูลที่ส่งผ่าน network
- กำหนด limit
- หลีกเลี่ยง query ที่ไม่มี index ใน collection ใหญ่
- อย่าส่ง BSON document ตรงออก API โดยไม่ mapping
- กำหนด pagination strategy

---

## 7. ป้องกัน Operator Injection

อันตรายเกิดเมื่อรับ filter object จาก client แล้วส่งเข้า MongoDB โดยตรง:

```php
// ไม่ควรทำ
$filter = json_decode($requestBody, true, flags: JSON_THROW_ON_ERROR);
$employees->findOne($filter);
```

Client อาจส่ง operator เช่น `$ne`, `$gt`, `$where` หรือ nested structure ที่เปลี่ยนความหมาย query

แนวทางที่ปลอดภัย:

```php
$input = json_decode(
    $requestBody,
    true,
    flags: JSON_THROW_ON_ERROR,
);

$employeeCode = $input['employeeCode'] ?? null;

if (!is_string($employeeCode) || $employeeCode === '') {
    throw new InvalidArgumentException('employeeCode is required.');
}

$employee = $employees->findOne([
    'employeeCode' => $employeeCode,
]);
```

สร้าง filter จาก allowlist ของ application เท่านั้น

---

## 8. Index Strategy

```php
$employees->createIndex(
    ['employeeCode' => 1],
    ['unique' => true, 'name' => 'employee_code_unique'],
);

$employees->createIndex(
    ['status' => 1, 'departmentId' => 1],
    ['name' => 'status_department'],
);
```

### Index design

- เริ่มจาก query patterns
- Compound index order สำคัญ
- Unique index ใช้บังคับ business key
- Partial index ลดขนาด index ในบาง workload
- TTL index เหมาะกับข้อมูลหมดอายุ เช่น temporary tokens
- ตรวจ explain plan และ index usage

อย่าสร้าง index ทุก field เพราะเพิ่ม write amplification และใช้ memory/storage

---

## 9. Atomic Updates

การ update field ใน document เดียวเป็น atomic operation:

```php
$result = $employees->updateOne(
    [
        '_id' => $employeeId,
        'version' => $expectedVersion,
    ],
    [
        '$set' => [
            'fullName' => $newFullName,
            'updatedAt' => new UTCDateTime(),
        ],
        '$inc' => ['version' => 1],
    ],
);

if ($result->getModifiedCount() !== 1) {
    throw new RuntimeException('Concurrent update detected.');
}
```

ใช้ version field เพื่อ optimistic concurrency control ได้เช่นเดียวกับ relational database

---

## 10. Aggregation Pipeline

ตัวอย่างสรุปพนักงานตามแผนก:

```php
$pipeline = [
    ['$match' => ['status' => 'active']],
    ['$group' => [
        '_id' => '$departmentId',
        'employeeCount' => ['$sum' => 1],
    ]],
    ['$sort' => ['employeeCount' => -1]],
];

$summary = $employees->aggregate($pipeline);
```

Aggregation pipeline ประกอบด้วย stages ที่ส่งผลลัพธ์ต่อกัน เช่น:

- `$match`
- `$project`
- `$group`
- `$sort`
- `$unwind`
- `$lookup`
- `$facet`

ควรย้าย `$match` ให้เร็วเมื่อทำได้เพื่อลด working set

---

## 11. Transactions

MongoDB รองรับ multi-document transactions แต่ไม่ควรใช้เพื่อชดเชย data model ที่ออกแบบผิด

```php
$session = $client->startSession();

$session->startTransaction();

try {
    $employees->updateOne(
        ['_id' => $employeeId],
        ['$set' => ['status' => 'inactive']],
        ['session' => $session],
    );

    $auditEvents->insertOne(
        [
            'employeeId' => $employeeId,
            'eventType' => 'employee.deactivated',
            'createdAt' => new UTCDateTime(),
        ],
        ['session' => $session],
    );

    $session->commitTransaction();
} catch (Throwable $error) {
    $session->abortTransaction();
    throw $error;
}
```

### หลักปฏิบัติ

- Transaction ต้องสั้น
- เตรียม retry transient transaction errors
- หลีกเลี่ยง network side effects ระหว่าง transaction
- ใช้ atomic document update ก่อน multi-document transaction เมื่อ data model อนุญาต

---

## 12. Relational vs Document Decision Matrix

| Requirement | Relational DB | MongoDB |
|---|---:|---:|
| Foreign keys เข้มงวด | เด่น | ต้องออกแบบใน application/document boundary |
| Multi-row reporting | เด่น | ทำได้ผ่าน aggregation แต่ต้องออกแบบ |
| Aggregate document ที่เปลี่ยนแปลงร่วมกัน | ทำได้ | เด่น |
| Schema evolution ที่มี variation | ต้อง migration | ยืดหยุ่นกว่า แต่ยังต้อง governance |
| Complex joins | เด่น | มี `$lookup` แต่ไม่ควรพึ่งมากเกินไป |
| Event/metadata payload | ทำได้ | เหมาะมาก |
| Unlimited child collection | แยก table | ควร reference ไม่ควร embed |

---

## แบบฝึกปฏิบัติ

1. ออกแบบ employee aggregate แบบ embedded และ referenced แล้วเปรียบเทียบ
2. สร้าง schema validation สำหรับ audit event
3. เขียน query ด้วย projection และ compound index
4. ป้องกัน operator injection จาก search API
5. สร้าง aggregation pipeline สรุป finding ตาม severity
6. เพิ่ม optimistic concurrency ด้วย version field
7. อธิบายว่า use case ใดควรใช้ PostgreSQL แทน MongoDB

---

## Production Checklist

- [ ] ใช้ official high-level PHP library
- [ ] URI อยู่ใน secret manager
- [ ] มี schema validation
- [ ] Filters สร้างจาก allowlist
- [ ] มี index รองรับ query หลัก
- [ ] ไม่ embed array ที่โตไม่จำกัด
- [ ] ใช้ projection และ limit
- [ ] มี timeout และ retry policy
- [ ] Transactions สั้นและจำเป็นจริง
- [ ] มี backup, restore และ monitoring

---

## References

- MongoDB PHP Library — https://www.mongodb.com/docs/php-library/current/
- MongoDB PHP Get Started — https://www.mongodb.com/docs/php-library/current/get-started/
- MongoDB Data Modeling — https://www.mongodb.com/docs/manual/data-modeling/
- MongoDB Indexes — https://www.mongodb.com/docs/manual/indexes/
- MongoDB Transactions — https://www.mongodb.com/docs/manual/core/transactions/
