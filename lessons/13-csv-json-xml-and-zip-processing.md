# 13 — CSV, JSON, XML and ZIP Processing

## บทนำ

การแลกเปลี่ยนข้อมูลระหว่างระบบไม่ได้มีรูปแบบเดียว CSV เหมาะกับข้อมูลตาราง, JSON เหมาะกับ API และ object structure, XML เหมาะกับมาตรฐานเอกสาร/ระบบเดิม และ ZIP เหมาะกับการรวมหลายไฟล์หรือบีบอัดข้อมูล ผู้พัฒนาต้องเลือก parser, memory strategy, validation และ security controls ให้ตรงกับชนิดข้อมูล

---

## ผลลัพธ์การเรียนรู้

- อ่านและเขียน CSV แบบ streaming
- ใช้ JSON แบบ strict พร้อมจัดการ error
- เลือก SimpleXML, DOM, XMLReader และ XMLWriter ให้เหมาะสม
- จัดการ ZIP โดยป้องกัน path traversal และ decompression bomb
- ทำ schema/version governance สำหรับข้อมูลแลกเปลี่ยน
- สร้าง import pipeline ที่ validate, report error และ retry ได้

---

## 1. CSV: Format ที่ดูง่ายแต่มีรายละเอียดมาก

CSV ไม่มีมาตรฐานเดียวที่ทุกระบบใช้เหมือนกัน ต้องกำหนด:

- Encoding
- Delimiter
- Enclosure
- Escape behavior
- Header names
- Newline convention
- Null/empty semantics
- Date/number format

### อ่านด้วย SplFileObject

```php
$file = new SplFileObject($path, 'rb');
$file->setFlags(
    SplFileObject::READ_CSV
    | SplFileObject::SKIP_EMPTY
    | SplFileObject::DROP_NEW_LINE,
);
$file->setCsvControl(',', '"', '');

$header = $file->fgetcsv();

if (!is_array($header)) {
    throw new RuntimeException('CSV header is missing.');
}

foreach ($file as $rowNumber => $row) {
    if (!is_array($row) || $row === [null]) {
        continue;
    }

    if (count($row) !== count($header)) {
        reportRowError($rowNumber + 1, 'Column count mismatch.');
        continue;
    }

    $record = array_combine($header, $row);
    processRecord($record, $rowNumber + 1);
}
```

> ใน PHP 8.4 การพึ่ง default escape parameter ของ CSV ถูก deprecate ควรกำหนด escape เป็น string ว่างอย่างชัดเจนเมื่อใช้รูปแบบมาตรฐาน

---

## 2. CSV Injection

ค่าที่ขึ้นต้นด้วย `=`, `+`, `-`, `@` อาจถูก spreadsheet software ตีความเป็น formula

```php
function safeSpreadsheetCell(string $value): string
{
    return preg_match('/^[=+\-@]/', $value) === 1
        ? "'" . $value
        : $value;
}
```

ต้องกำหนด policy ตามระบบปลายทาง ไม่ควรคิดว่า CSV เป็น plain text ที่ปลอดภัยเสมอ

---

## 3. JSON แบบ Strict

```php
$data = json_decode(
    $json,
    true,
    512,
    JSON_THROW_ON_ERROR,
);

if (!is_array($data)) {
    throw new InvalidArgumentException('JSON root must be an object.');
}
```

Encode:

```php
$json = json_encode(
    $payload,
    JSON_THROW_ON_ERROR
    | JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES,
);
```

### Professional concerns

- Integer precision ระหว่างภาษา
- Date/time format
- Null vs missing field
- Duplicate keys
- Depth limit
- Payload size limit
- Schema version
- Backward compatibility

ตัวอย่าง envelope:

```json
{
  "schemaVersion": 2,
  "generatedAt": "2026-08-01T01:00:00Z",
  "data": []
}
```

---

## 4. NDJSON สำหรับ Streaming Records

NDJSON ใช้ JSON object หนึ่งรายการต่อหนึ่งบรรทัด เหมาะกับ logs และ data export ขนาดใหญ่

```php
$file = new SplFileObject($path, 'rb');

while (!$file->eof()) {
    $line = trim($file->fgets());

    if ($line === '') {
        continue;
    }

    $record = json_decode(
        $line,
        true,
        128,
        JSON_THROW_ON_ERROR,
    );

    processRecord($record);
}
```

ข้อดีคือประมวลผลทีละ record และ resume ตาม line/offset ได้ง่ายกว่า JSON array ขนาดใหญ่

---

## 5. XML Parser Selection

| API | เหมาะกับ |
|---|---|
| SimpleXML | เอกสารเล็ก โครงสร้างง่าย |
| DOM | ต้องแก้ไข/ค้นหา tree |
| XMLReader | เอกสารใหญ่ อ่านไปข้างหน้า |
| XMLWriter | สร้าง XML แบบ streaming |

### XMLReader สำหรับไฟล์ใหญ่

```php
$reader = XMLReader::fromUri(
    $path,
    encoding: null,
    flags: LIBXML_NONET,
);

while ($reader->read()) {
    if (
        $reader->nodeType === XMLReader::ELEMENT
        && $reader->name === 'employee'
    ) {
        $node = $reader->expand();
        processEmployeeNode($node);
    }
}

$reader->close();
```

PHP 8.4 รองรับ `XMLReader::fromStream()` ซึ่งช่วยเชื่อม XML parser เข้ากับ stream pipeline โดยตรง

---

## 6. XML Security

ควรใช้:

- `LIBXML_NONET` ป้องกัน network access
- Input size/depth limits
- Schema validation เมื่อมี XSD
- Disable/avoid unsafe external entity behavior
- ห้ามเปิด URI ที่ผู้ใช้กำหนดโดยตรง

ภัยที่ต้องรู้:

- XXE
- Billion laughs/entity expansion
- External resource fetching
- Oversized deeply nested XML
- Namespace confusion

---

## 7. XMLWriter

```php
$writer = new XMLWriter();
$writer->openUri($outputPath);
$writer->startDocument('1.0', 'UTF-8');
$writer->startElement('employees');

foreach ($employees as $employee) {
    $writer->startElement('employee');
    $writer->writeElement('code', $employee->code);
    $writer->writeElement('fullName', $employee->fullName);
    $writer->endElement();
}

$writer->endElement();
$writer->endDocument();
$writer->flush();
```

XMLWriter เหมาะกับ output ใหญ่เพราะไม่ต้องสร้าง DOM ทั้ง document ใน memory

---

## 8. ZIP Archive

```php
$zip = new ZipArchive();
$status = $zip->open($archivePath, ZipArchive::RDONLY);

if ($status !== true) {
    throw new RuntimeException('Unable to open ZIP archive.');
}

try {
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entry = $zip->statIndex($index);

        if (!is_array($entry)) {
            continue;
        }

        validateArchiveEntry($entry);
    }
} finally {
    $zip->close();
}
```

---

## 9. ป้องกัน Zip Slip

ก่อน extract ต้องตรวจทุก entry:

```php
function validateZipPath(string $name): void
{
    $normalized = str_replace('\\', '/', $name);

    if (
        str_starts_with($normalized, '/')
        || preg_match('/^[A-Za-z]:\//', $normalized) === 1
        || in_array('..', explode('/', $normalized), true)
    ) {
        throw new RuntimeException('Unsafe ZIP entry path.');
    }
}
```

อย่าใช้ `extractTo()` กับ archive ที่ไม่เชื่อถือโดยไม่ตรวจรายการก่อน

---

## 10. Decompression Bomb Controls

ตรวจ:

- จำนวนไฟล์สูงสุด
- ขนาด uncompressed รวม
- ขนาดแต่ละ entry
- Compression ratio
- Nested archives
- Allowed extensions
- Processing time

```php
$totalUncompressed = 0;
$maximumTotal = 500 * 1024 * 1024;
$maximumEntries = 1_000;

if ($zip->numFiles > $maximumEntries) {
    throw new RuntimeException('Too many archive entries.');
}
```

---

## 11. Import Pipeline Architecture

```text
Input Acquisition
  -> Format Detection
  -> Structural Validation
  -> Record Parsing
  -> Domain Validation
  -> Deduplication
  -> Persistence Batch
  -> Error Report
  -> Audit Summary
```

หลักปฏิบัติ:

- มี import ID
- เก็บ row/record number
- แยก parse error กับ business validation error
- รองรับ dry-run
- ใช้ batch transaction อย่างมีขอบเขต
- ทำ idempotency/deduplication
- สรุป success, rejected และ skipped records

---

## 12. Format Decision Matrix

| Requirement | Format |
|---|---|
| ตารางเรียบง่าย ใช้ Excel | CSV |
| API และ nested object | JSON |
| Streaming records/log | NDJSON |
| มาตรฐานเอกสาร/legacy integration | XML |
| รวมหลายไฟล์ | ZIP |
| Binary schema ที่เข้มงวด | พิจารณา format เฉพาะ เช่น Protobuf/Avro |

---

## แบบฝึกปฏิบัติ

1. Import CSV พนักงาน 100,000 แถวแบบ streaming
2. ป้องกัน CSV formula injection ใน export
3. สร้าง NDJSON audit log reader
4. Parse XML ขนาดใหญ่ด้วย XMLReader
5. สร้าง ZIP export และตรวจ Zip Slip
6. ออกแบบ error report ที่ระบุ record number และ reason code

---

## Production Checklist

- [ ] กำหนด encoding/delimiter/schema ชัดเจน
- [ ] ใช้ streaming กับข้อมูลใหญ่
- [ ] JSON ใช้ `JSON_THROW_ON_ERROR`
- [ ] XML ปิด network access
- [ ] ZIP ตรวจ path และ uncompressed size
- [ ] มี payload/record/depth limits
- [ ] Import มี idempotency และ audit trail
- [ ] Error report ไม่เปิดเผยข้อมูลอ่อนไหวเกินจำเป็น
- [ ] มี format version และ compatibility policy

---

## References

- SplFileObject CSV — https://www.php.net/manual/en/splfileobject.fgetcsv.php
- PHP JSON — https://www.php.net/manual/en/book.json.php
- PHP XMLReader — https://www.php.net/manual/en/book.xmlreader.php
- PHP XMLWriter — https://www.php.net/manual/en/book.xmlwriter.php
- PHP ZipArchive — https://www.php.net/manual/en/class.ziparchive.php
