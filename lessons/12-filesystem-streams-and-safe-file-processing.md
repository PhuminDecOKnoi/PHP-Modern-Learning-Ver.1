# 12 — Filesystem, Streams and Safe File Processing

## บทนำ

งาน PHP จำนวนมากเกี่ยวข้องกับไฟล์ เช่น import ข้อมูลพนักงาน, export รายงาน, รับเอกสารแนบ, อ่าน log, สร้าง temporary file และส่งข้อมูลไป object storage หากออกแบบไม่ดีอาจเกิด memory exhaustion, path traversal, race condition, permission leakage หรือข้อมูลเสียหายจากการเขียนไฟล์ไม่สมบูรณ์

PHP ใช้แนวคิด **stream** เป็น abstraction กลางสำหรับไฟล์ network และข้อมูลบีบอัด จึงควรเรียนรู้ทั้ง filesystem API และ stream behavior ไม่ใช่จำเพียง `file_get_contents()`

---

## ผลลัพธ์การเรียนรู้

- เลือกวิธีอ่านไฟล์ตามขนาดและรูปแบบ
- ใช้ `SplFileObject`, `php://memory` และ `php://temp`
- เขียนไฟล์แบบ atomic และใช้ file locking
- ป้องกัน path traversal และ unsafe upload
- จัดการ permissions, ownership และ temporary files
- ออกแบบ streaming pipeline ที่ไม่โหลดข้อมูลทั้งหมดเข้า memory
- สร้าง test และ operational checklist สำหรับ file processing

---

## 1. Small File vs Large File

ไฟล์ขนาดเล็ก:

```php
$content = file_get_contents($path);

if ($content === false) {
    throw new RuntimeException('Unable to read file.');
}
```

ไฟล์ขนาดใหญ่ควรอ่านทีละส่วน:

```php
$file = new SplFileObject($path, 'rb');

while (!$file->eof()) {
    $line = $file->fgets();

    if ($line === '') {
        continue;
    }

    processLine($line);
}
```

### หลักเลือกใช้

| สถานการณ์ | วิธี |
|---|---|
| Configuration file เล็ก | `file_get_contents()` |
| Log/CSV ขนาดใหญ่ | `SplFileObject` หรือ stream loop |
| Binary file | `fread()` ตาม chunk |
| Temporary transformation | `php://temp` |
| Request body | `php://input` |

อย่าใช้ memory limit เป็นข้ออ้างในการโหลดไฟล์ทั้งหมดโดยไม่จำเป็น

---

## 2. Stream Wrappers

ตรวจ wrappers ที่ระบบรองรับ:

```php
print_r(stream_get_wrappers());
```

ตัวอย่างที่พบบ่อย:

- `file://`
- `php://`
- `http://` และ `https://`
- `ftp://` และ `ftps://`
- `data://`
- `compress.zlib://`

> การอนุญาต URL จากผู้ใช้ให้ถูกเปิดด้วย stream wrapper โดยตรงอาจนำไปสู่ SSRF หรือการอ่าน resource ที่ไม่ควรเข้าถึง ต้องใช้ allowlist และแยก HTTP client ที่ควบคุมได้

---

## 3. php://memory และ php://temp

```php
$stream = fopen('php://temp/maxmemory:2097152', 'w+b');

if ($stream === false) {
    throw new RuntimeException('Unable to open temporary stream.');
}

fwrite($stream, "employee_code,full_name\n");
fwrite($stream, "EMP-0001,Example Employee\n");
rewind($stream);

$output = stream_get_contents($stream);
fclose($stream);
```

- `php://memory` เก็บทั้งหมดใน memory
- `php://temp` ใช้ memory ก่อนและย้ายไป temporary file เมื่อเกิน threshold

เหมาะกับ export, attachment assembly และ transformation pipeline

---

## 4. Path Traversal Protection

ห้ามนำ filename จากผู้ใช้มาต่อ path โดยตรง:

```php
// ไม่ปลอดภัย
$path = __DIR__ . '/uploads/' . $_GET['file'];
```

แนวทางที่ดีกว่า:

```php
$storageRoot = realpath(__DIR__ . '/../var/uploads');

if ($storageRoot === false) {
    throw new RuntimeException('Storage root not found.');
}

$requestedName = (string) ($_GET['file'] ?? '');
$safeName = basename($requestedName);
$path = $storageRoot . DIRECTORY_SEPARATOR . $safeName;
$resolved = realpath($path);

if (
    $resolved === false
    || !str_starts_with($resolved, $storageRoot . DIRECTORY_SEPARATOR)
) {
    throw new RuntimeException('Invalid file path.');
}
```

แนวทาง professional ยิ่งกว่าคือเก็บ mapping ระหว่าง file ID กับ storage path ในระบบ และไม่เปิด filename เป็น locator โดยตรง

---

## 5. Atomic Write

การเขียนทับไฟล์โดยตรงอาจทิ้งไฟล์ครึ่งเดียวเมื่อ process ล้มเหลว

```php
function atomicWrite(string $target, string $content): void
{
    $directory = dirname($target);
    $temporary = tempnam($directory, '.tmp-');

    if ($temporary === false) {
        throw new RuntimeException('Unable to create temporary file.');
    }

    try {
        $bytes = file_put_contents($temporary, $content, LOCK_EX);

        if ($bytes === false || $bytes !== strlen($content)) {
            throw new RuntimeException('Incomplete file write.');
        }

        if (!rename($temporary, $target)) {
            throw new RuntimeException('Unable to replace target file.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}
```

### ข้อควรทราบ

- Atomicity ของ `rename()` ขึ้นกับ filesystem และต้องอยู่ volume เดียวกัน
- กำหนด permission หลังสร้างไฟล์
- Sync-to-disk requirement ต้องประเมินตามความสำคัญของข้อมูล

---

## 6. File Locking

```php
$file = fopen($path, 'c+b');

if ($file === false) {
    throw new RuntimeException('Unable to open file.');
}

try {
    if (!flock($file, LOCK_EX)) {
        throw new RuntimeException('Unable to acquire lock.');
    }

    ftruncate($file, 0);
    rewind($file);
    fwrite($file, $content);
    fflush($file);

    flock($file, LOCK_UN);
} finally {
    fclose($file);
}
```

### Locking limitations

- Advisory lock ต้องให้ทุก process ปฏิบัติตาม
- Network filesystem behavior อาจต่างกัน
- Lock ต้องมีขอบเขตสั้น
- อย่าถือ lock ระหว่างเรียก external API
- ต้องออกแบบ timeout/retry

---

## 7. Secure File Upload

ตรวจหลายชั้น:

1. Upload error code
2. File size
3. MIME type จากเนื้อหา
4. Extension allowlist
5. Random storage name
6. เก็บนอก public web root
7. Malware scanning ตาม risk
8. Authorization ตอน download

```php
$upload = $_FILES['document'] ?? null;

if (!is_array($upload) || $upload['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Upload failed.');
}

if ((int) $upload['size'] > 5 * 1024 * 1024) {
    throw new RuntimeException('File is too large.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($upload['tmp_name']);

$allowed = [
    'application/pdf' => 'pdf',
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
];

if (!isset($allowed[$mime])) {
    throw new RuntimeException('Unsupported file type.');
}

$storedName = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
$destination = $storageRoot . DIRECTORY_SEPARATOR . $storedName;

if (!move_uploaded_file($upload['tmp_name'], $destination)) {
    throw new RuntimeException('Unable to store uploaded file.');
}
```

อย่าเชื่อ MIME หรือ extension จาก client

---

## 8. Permissions และ Secret Exposure

- Application user ไม่ควรเขียนได้ทุก directory
- Secret/config files ไม่ควรอยู่ใน public document root
- Uploaded files ไม่ควรถูก execute เป็น PHP
- ใช้ umask/permissions ที่เหมาะสม
- Log access และ deletion ของ sensitive files
- กำหนด retention และ secure deletion policy ตามความเสี่ยง

---

## 9. Streaming Hash และ Copy

คำนวณ hash โดยไม่โหลดทั้งไฟล์:

```php
$hash = hash_file('sha256', $path);

if ($hash === false) {
    throw new RuntimeException('Unable to hash file.');
}
```

Copy ผ่าน streams:

```php
$source = fopen($sourcePath, 'rb');
$target = fopen($targetPath, 'wb');

if ($source === false || $target === false) {
    throw new RuntimeException('Unable to open stream.');
}

try {
    $bytes = stream_copy_to_stream($source, $target);

    if ($bytes === false) {
        throw new RuntimeException('Stream copy failed.');
    }
} finally {
    fclose($source);
    fclose($target);
}
```

---

## 10. File Processing Pipeline

โครงสร้างที่แนะนำ:

```text
UploadController
  -> UploadValidator
  -> QuarantineStorage
  -> MalwareScanner
  -> DocumentMetadataExtractor
  -> PermanentStorage
  -> AuditLogger
```

แยก responsibilities ทำให้ทดสอบและเปลี่ยน storage backend ได้ง่ายขึ้น

---

## 11. Error Handling และ Cleanup

- ใช้ `try/finally` ปิด streams
- ตรวจ return value ของทุก file function
- บันทึก operation ID ไม่บันทึก content อ่อนไหว
- Cleanup temporary files แม้เกิด exception
- ทำ processing ให้ idempotent
- เก็บ failed files ใน quarantine พร้อม retention

---

## แบบฝึกปฏิบัติ

1. เขียน atomic JSON configuration writer
2. สร้าง secure upload service ที่เก็บไฟล์นอก public root
3. ประมวลผล log 2 GB แบบ streaming
4. ทดลอง concurrent write โดยมีและไม่มี `flock()`
5. สร้าง test สำหรับ path traversal
6. ออกแบบ retention policy สำหรับเอกสารพนักงาน

---

## Production Checklist

- [ ] ไม่โหลดไฟล์ใหญ่ทั้งหมดเข้า memory
- [ ] Path มาจาก trusted mapping หรือผ่าน canonicalization
- [ ] Upload ตรวจ size/MIME/extension
- [ ] เก็บ upload นอก public root
- [ ] เขียนไฟล์สำคัญแบบ atomic
- [ ] Lock มีขอบเขตชัดเจน
- [ ] Cleanup temporary files
- [ ] Permissions เป็น least privilege
- [ ] มี retention, backup และ audit trail
- [ ] ทดสอบ disk full และ permission denied

---

## References

- PHP Filesystem Manual — https://www.php.net/manual/en/book.filesystem.php
- PHP Streams Manual — https://www.php.net/manual/en/book.stream.php
- PHP Stream Wrappers — https://www.php.net/manual/en/wrappers.php
- SplFileObject — https://www.php.net/manual/en/class.splfileobject.php
- PHP File Upload Security — https://www.php.net/manual/en/features.file-upload.php
