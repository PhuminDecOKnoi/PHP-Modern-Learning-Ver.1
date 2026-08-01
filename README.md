# PHP Modern Learning — PHP 8.4–8.5 Application Engineering Edition

[![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-13-3C9CD7)](https://phpunit.de/)
[![Composer](https://img.shields.io/badge/Composer-2.x-885630?logo=composer&logoColor=white)](https://getcomposer.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP CI](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml/badge.svg)](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml)

หลักสูตร PHP แบบ **professional, intensive และ production-oriented** สำหรับผู้เรียน นักพัฒนา และผู้สอนที่ต้องการพัฒนาจากพื้นฐานภาษาไปสู่การออกแบบระบบจริงอย่างเป็นระบบ

เนื้อหาครอบคลุม **Modern PHP, Database Engineering, Filesystem and Streams, Structured Data, HTTP and Network Programming, Security, Internationalization, Queue, Observability และ Storage Architecture** พร้อมตัวอย่างโค้ดที่รันได้จริง แบบฝึกคิดเชิงสถาปัตยกรรม และ GitHub Actions ที่ตรวจสอบบน PHP 8.4 และ PHP 8.5

> **Course baseline:** ใช้ PHP 8.5 เป็นเวอร์ชันหลัก รองรับ PHP 8.4 เป็นขั้นต่ำ และใช้ PHP 8.6 pre-release เพื่อศึกษาการเปลี่ยนแปลงเท่านั้น ไม่ใช้เป็น production baseline

---

## Table of Contents

- [Course Overview](#course-overview)
- [What You Will Learn](#what-you-will-learn)
- [Technology Coverage](#technology-coverage)
- [Learning Path](#learning-path)
- [How Each Lesson Is Designed](#how-each-lesson-is-designed)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Learning Environment Profiles](#learning-environment-profiles)
- [Project Structure](#project-structure)
- [Executable Examples](#executable-examples)
- [Coding and Architecture Standards](#coding-and-architecture-standards)
- [Testing and CI](#testing-and-ci)
- [Security Baseline](#security-baseline)
- [Recommended Study Method](#recommended-study-method)
- [Migration](#migration)
- [References](#references)

---

## Course Overview

Repository นี้พัฒนาจากชุดบทเรียน PHP พื้นฐานไปสู่หลักสูตร **PHP Application Engineering** ที่เน้นทั้งการเขียนโค้ด การออกแบบระบบ ความปลอดภัย การทดสอบ และการดูแลระบบใน production

### เป้าหมายหลัก

- ปูพื้นฐาน PHP 8.4–8.5 ให้ถูกต้องและทันสมัย
- เชื่อมจาก syntax ไปสู่ OOP, Composer, API และ application architecture
- แยก business logic, data access และ infrastructure อย่างชัดเจน
- อธิบาย trade-offs และ failure modes ไม่ใช่เพียงวิธีเรียก function
- ใช้ security, testing และ observability เป็นส่วนหนึ่งของการออกแบบตั้งแต่ต้น
- รองรับการเรียนด้วยตนเอง การสอนในชั้นเรียน และการใช้เป็น GitHub portfolio
- เตรียมผู้เรียนให้สามารถพัฒนาและทบทวนระบบ PHP ในระดับทีมงานมืออาชีพ

### เหมาะสำหรับ

- ผู้เริ่มต้นที่ต้องการเรียน PHP อย่างเป็นระบบ
- นักพัฒนา PHP ที่ต้องการยกระดับจาก script ไปสู่ application engineering
- ผู้พัฒนาที่ต้องทำงานกับฐานข้อมูล API ไฟล์ เครือข่าย และระบบภายนอก
- ผู้สอนที่ต้องการ lesson note พร้อมตัวอย่างและ production checklist
- ผู้ทำงานด้าน HR, Labour Compliance, Audit, Legal Tech หรือระบบธุรกิจที่ต้องประมวลผลข้อมูลจำนวนมาก

---

## What You Will Learn

เมื่อเรียนครบ ผู้เรียนควรสามารถ:

1. สร้างโปรเจกต์ PHP แบบ modern ด้วย Composer และ PSR-4
2. ใช้ฟีเจอร์ PHP 8.4–8.5 อย่างเหมาะสมและตรวจ compatibility ได้
3. ออกแบบ data access ด้วย PDO, Repository Pattern และ transaction boundary
4. เลือกใช้ MySQL, SQLite, PostgreSQL, MongoDB และ Redis/Valkey ตามลักษณะงาน
5. ประมวลผลไฟล์และข้อมูลขนาดใหญ่แบบ streaming
6. สร้างและเรียก REST API อย่างปลอดภัยและทนต่อความล้มเหลว
7. เข้าใจ TCP/UDP, timeout, TLS, message framing และ backpressure
8. รองรับ Unicode ภาษาไทย locale currency และ timezone อย่างถูกต้อง
9. ออกแบบ queue worker ให้รองรับ retry, idempotency และ dead-letter queue
10. สร้าง structured logging, metrics, tracing และ audit trail
11. เลือก storage architecture ให้เหมาะกับข้อมูล ขนาดไฟล์ retention และ security
12. เขียน automated tests และใช้ CI เป็น quality gate ก่อน merge หรือ deploy

---

## Technology Coverage

| Domain | Technologies and Concepts |
|---|---|
| Language | PHP 8.4, PHP 8.5, strict types, typed properties, enums, attributes, property hooks |
| Dependency Management | Composer 2, PSR-4 autoloading, platform requirements |
| Relational Database | PDO, MySQL, SQLite, PostgreSQL, transactions, migrations, indexing |
| Document Database | MongoDB, BSON, document modeling, aggregation, indexing |
| Cache and Key-Value | Redis, Valkey, TTL, cache-aside, rate limiting, distributed state |
| Files and Streams | Filesystem API, `SplFileObject`, stream wrappers, atomic writes, file locking |
| Data Formats | CSV, JSON, NDJSON, XMLReader, XMLWriter, ZIP archives |
| HTTP Integration | cURL, URI handling, retries, idempotency, circuit breaker, SSRF prevention |
| PHP Standards | PSR-3, PSR-4, PSR-7, PSR-12, PSR-17, PSR-18 |
| Network | TCP, UDP, TLS streams, framing, partial I/O, non-blocking I/O |
| Security | Input validation, output encoding, authentication, session, CSRF, secret management |
| Internationalization | Unicode, grapheme, normalization, ICU, Thai collation, locale and timezone |
| Background Processing | Queue, worker, retry, jitter, inbox/outbox, DLQ, graceful shutdown |
| Observability | Structured logs, metrics, SLI/SLO, tracing, correlation ID, audit log |
| Storage | Local, shared, database BLOB, object storage, presigned URL, lifecycle and retention |
| Quality | PHPUnit 13, syntax linting, Composer validation, GitHub Actions |

รายละเอียด prerequisites และ optional technologies อยู่ที่ [`docs/technology-matrix.md`](docs/technology-matrix.md)

---

## Version Policy

| Component | Course Baseline | หมายเหตุ |
|---|---:|---|
| PHP | `8.5` | Primary teaching version |
| Minimum PHP | `8.4` | Minimum supported version |
| PHP Preview | `8.6 alpha/beta` | Preview and compatibility study only |
| PHPUnit | `13.x` | Automated testing |
| Composer | `2.x` | Dependency management and PSR-4 autoload |
| MySQL | `8.0+` | Core relational database example |
| PostgreSQL | Current supported release | Advanced SQL and concurrency module |
| SQLite | Current bundled/system release | Local application and testing module |
| MongoDB | Official PHP library and extension | Optional document database module |
| Redis/Valkey | Supported server and client release | Optional cache and rate-limiting module |

รายละเอียดนโยบายอยู่ที่ [`docs/version-policy.md`](docs/version-policy.md)

---

# Learning Path

## Module A — Modern PHP Foundation

| บท | บทเรียน | Learning Outcome |
|---:|---|---|
| 00 | [PHP 8.4–8.5 Course Baseline](lessons/00-php-8-4-8-5-course-baseline.md) | จัดการ version และ environment governance |
| 01 | [Modern Project Setup](lessons/01-modern-project-setup.md) | สร้างโปรเจกต์ด้วย Composer, PSR-4 และโครงสร้างมาตรฐาน |
| 02 | [PHP 8.4 and 8.5 Features](lessons/02-php-8-4-and-8-5-features.md) | ใช้ language features รุ่นใหม่และประเมิน compatibility |
| 03 | [Secure PDO and MySQL](lessons/03-secure-pdo-and-mysql.md) | ใช้ prepared statements, transactions และ connection configuration |
| 04 | [REST API and Input Validation](lessons/04-rest-api-and-input-validation.md) | สร้าง JSON API พร้อม validation และ error handling |
| 05 | [Authentication Security](lessons/05-authentication-security.md) | ใช้ password hashing, session hardening และ CSRF protection |
| 06 | [Testing with PHPUnit 13](lessons/06-testing-with-phpunit-13.md) | เขียน unit tests และ integration tests อย่างเป็นระบบ |

## Module B — Database Engineering

| บท | บทเรียน | Learning Outcome |
|---:|---|---|
| 07 | [Database Drivers and Portable Data Access](lessons/07-database-drivers-and-portable-data-access.md) | ออกแบบ PDO drivers, repositories, migrations และ portability boundary |
| 08 | [SQLite for Local Apps and Testing](lessons/08-sqlite-local-apps-and-testing.md) | ใช้ SQLite, WAL, foreign keys, locking และ in-memory tests |
| 09 | [PostgreSQL Advanced SQL and Concurrency](lessons/09-postgresql-advanced-sql-and-concurrency.md) | ใช้ JSONB, isolation, optimistic concurrency, indexing และ EXPLAIN |
| 10 | [MongoDB Document Data Modeling](lessons/10-mongodb-document-data-modeling.md) | ออกแบบ BSON documents, indexes, aggregation และ transaction boundary |
| 11 | [Redis and Valkey](lessons/11-redis-valkey-cache-and-rate-limiting.md) | ใช้ cache-aside, TTL, stampede protection, session และ rate limiting |

## Module C — Files and Data Exchange

| บท | บทเรียน | Learning Outcome |
|---:|---|---|
| 12 | [Filesystem, Streams and Safe File Processing](lessons/12-filesystem-streams-and-safe-file-processing.md) | ประมวลผลไฟล์แบบ streaming, atomic write และ secure upload |
| 13 | [CSV, JSON, XML and ZIP Processing](lessons/13-csv-json-xml-and-zip-processing.md) | จัดการ structured data, large files และ archive safety |

## Module D — Integration and Network Engineering

| บท | บทเรียน | Learning Outcome |
|---:|---|---|
| 14 | [HTTP Client, cURL, URI and PSR Standards](lessons/14-http-client-curl-uri-and-psr-standards.md) | สร้าง HTTP integration ที่มี timeout, retry, SSRF controls และ PSR interfaces |
| 15 | [Network Streams and Sockets](lessons/15-network-streams-and-sockets.md) | เข้าใจ TCP/UDP, framing, TLS, partial I/O และ backpressure |

## Module E — Internationalization and Operations

| บท | บทเรียน | Learning Outcome |
|---:|---|---|
| 16 | [Unicode, Thai and Internationalization](lessons/16-unicode-thai-and-internationalization.md) | จัดการ grapheme, normalization, Thai collation, locale และ timezone |
| 17 | [Queue, Workers and Background Jobs](lessons/17-queue-workers-and-background-jobs.md) | ออกแบบ idempotent worker, retry, outbox/inbox และ DLQ |
| 18 | [Logging, Metrics and Observability](lessons/18-logging-metrics-and-observability.md) | ใช้ PSR-3, structured logs, metrics, SLO, tracing และ audit trail |
| 19 | [Storage Architecture and Object Storage](lessons/19-storage-architecture-and-object-storage.md) | เลือก storage model, retention, integrity และ secure delivery |

---

## How Each Lesson Is Designed

แต่ละบทถูกออกแบบให้ตอบคำถามระดับมืออาชีพ ไม่ใช่เพียงสาธิต syntax

1. **Problem Context** — เทคโนโลยีนี้แก้ปัญหาอะไร
2. **Core Concepts** — แนวคิดและคำศัพท์ที่ต้องเข้าใจ
3. **Architecture Decision** — ใช้เมื่อใดและไม่ควรใช้เมื่อใด
4. **Implementation** — ตัวอย่างโค้ดพร้อมคำอธิบาย
5. **Failure Modes** — ระบบอาจล้มเหลวอย่างไร
6. **Security Controls** — ต้องป้องกันอะไรบ้าง
7. **Testing Strategy** — จะพิสูจน์ความถูกต้องอย่างไร
8. **Production Checklist** — สิ่งที่ต้องตรวจสอบก่อนนำไปใช้จริง
9. **Exercises** — แบบฝึกเพื่อเชื่อมความรู้กับสถานการณ์จริง
10. **References** — แหล่งข้อมูลทางการและมาตรฐานที่เกี่ยวข้อง

---

## Requirements

### Core Requirements

```bash
php -v
composer --version
```

ค่าที่คาดหวัง:

```text
PHP 8.4.x หรือ PHP 8.5.x
Composer 2.x
```

Core PHP extensions:

```text
ext-json
ext-pdo
```

### Recommended Extensions

ติดตั้งตามบทที่เรียน:

```text
pdo_mysql
pdo_sqlite
pdo_pgsql
curl
intl
mbstring
xmlreader
xmlwriter
zip
```

Optional services เช่น PostgreSQL, MongoDB, Redis/Valkey, queue broker และ object storage ไม่ได้บังคับสำหรับ core course

---

## Quick Start

### 1. Clone Repository

```bash
git clone https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1.git
cd PHP-Modern-Learning-Ver.1
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Validate Environment

```bash
composer validate --strict
composer check-platform-reqs
```

### 4. Run Quality Checks

```bash
composer check
```

หรือแยกคำสั่ง:

```bash
composer lint
composer test
```

### 5. Start Learning

เริ่มจาก:

```text
lessons/00-php-8-4-8-5-course-baseline.md
```

แล้วเรียนตามลำดับบท 00–19

---

## Learning Environment Profiles

### Profile 1 — Core Learner

```text
PHP 8.4 หรือ 8.5
Composer 2
ext-json
ext-pdo
PHPUnit 13
```

เหมาะสำหรับบท 00–06 และ automated examples

### Profile 2 — Database Engineering

```text
Core Profile
pdo_mysql
pdo_sqlite
pdo_pgsql
MySQL / PostgreSQL
```

เหมาะสำหรับบท 07–09

### Profile 3 — Distributed Data

```text
Core Profile
MongoDB extension and library
Redis/Valkey client and service
```

เหมาะสำหรับบท 10–11

### Profile 4 — Integration Engineering

```text
Core Profile
ext-curl
ext-xmlreader
ext-xmlwriter
ext-zip
```

เหมาะสำหรับบท 12–15

### Profile 5 — Internationalization and Operations

```text
Core Profile
ext-intl
ext-mbstring
Queue or broker of choice
Object storage or emulator
```

เหมาะสำหรับบท 16–19

---

## Project Structure

```text
PHP-Modern-Learning-Ver.1/
├── .github/
│   └── workflows/
│       └── php-ci.yml
├── docs/
│   ├── technology-matrix.md
│   └── version-policy.md
├── lessons/
│   ├── 00-php-8-4-8-5-course-baseline.md
│   ├── 01-modern-project-setup.md
│   ├── ...
│   └── 19-storage-architecture-and-object-storage.md
├── src/
│   ├── IO/
│   │   └── CsvRecordReader.php
│   └── Support/
│       └── Email.php
├── tests/
│   ├── IO/
│   │   └── CsvRecordReaderTest.php
│   └── Support/
│       └── EmailTest.php
├── composer.json
├── phpunit.xml
├── MIGRATION.md
├── CHANGELOG.md
├── LICENSE
└── README.md
```

บทเรียนเดิมที่อยู่ root repository ยังคงเก็บไว้เพื่อรักษาประวัติและลิงก์เดิม ส่วน `lessons/` เป็น **canonical curriculum** สำหรับหลักสูตรปัจจุบัน

---

## Executable Examples

### Email Value Object

ไฟล์:

```text
src/Support/Email.php
tests/Support/EmailTest.php
```

รันเฉพาะชุดทดสอบ:

```bash
composer test -- --filter EmailTest
```

### Streaming CSV Reader

ไฟล์:

```text
src/IO/CsvRecordReader.php
tests/IO/CsvRecordReaderTest.php
```

ตัวอย่างนี้แสดง:

- การอ่าน CSV แบบ streaming ด้วย `SplFileObject`
- Header mapping
- Duplicate header validation
- Column-count validation
- Unreadable file handling
- Explicit CSV escape parameter สำหรับ PHP 8.4+
- Success and failure test cases

รันเฉพาะชุดทดสอบ:

```bash
composer test -- --filter CsvRecordReaderTest
```

---

## Coding and Architecture Standards

ตัวอย่างใหม่เริ่มต้นด้วย:

```php
<?php

declare(strict_types=1);
```

หลักที่ใช้ใน repository:

- PSR-4 autoloading
- PSR-12/PER-compatible coding style
- Explicit parameter and return types
- Constructor injection
- Single Responsibility Principle
- Repository and service boundaries
- Prepared statements
- Context-aware output encoding
- Environment-based secrets
- Production-safe exception handling
- Idempotency for retryable operations
- Automated tests for important business logic

---

## Testing and CI

คำสั่งหลัก:

```bash
composer lint
composer test
composer check
```

GitHub Actions ตรวจสอบ:

| Quality Gate | PHP 8.4 | PHP 8.5 |
|---|:---:|:---:|
| Composer validation | ✅ | ✅ |
| Dependency installation | ✅ | ✅ |
| PHP syntax lint | ✅ | ✅ |
| PHPUnit 13 tests | ✅ | ✅ |

Optional services ควรมี integration-test workflow แยก เช่น PostgreSQL, MongoDB หรือ Redis containers เพื่อไม่ทำให้ core CI ซับซ้อนเกินจำเป็น

---

## Security Baseline

ทุกบทควรยึดหลักต่อไปนี้:

- Validate input ที่ server เสมอ
- Encode output ตาม context
- ใช้ prepared statements กับข้อมูลที่มาจากภายนอก
- ใช้ `password_hash()` และ `password_verify()`
- Regenerate session ID หลัง login
- ใช้ CSRF protection กับ state-changing requests
- ใช้ `JSON_THROW_ON_ERROR`
- กำหนด connect timeout และ total timeout สำหรับ network calls
- ตรวจ certificate และ hostname เมื่อใช้ TLS
- ป้องกัน SSRF, path traversal, CSV injection และ archive extraction attacks
- ไม่ log secrets, credentials หรือ PII เกินความจำเป็น
- ใช้ least privilege กับฐานข้อมูล service account และ storage
- กำหนด retention, deletion และ audit requirements อย่างชัดเจน

---

## Recommended Study Method

### วิธีเรียนแต่ละบท

1. อ่าน Learning Objectives และ Architecture Context
2. ทดลองรันตัวอย่างโค้ด
3. เปลี่ยน input เพื่อทดสอบ failure cases
4. เขียน test เพิ่มอย่างน้อยหนึ่งกรณี
5. ตอบ Production Review Questions ท้ายบท
6. สรุปว่าเทคโนโลยีนี้เหมาะหรือไม่เหมาะกับระบบประเภทใด

### แนวทางสำหรับผู้สอน

- ใช้บท 00–06 เป็น foundation course
- ใช้บท 07–13 เป็น data and integration track
- ใช้บท 14–19 เป็น professional application engineering track
- ให้ผู้เรียนทำ mini project ที่มี database, import/export, API, queue และ logging
- ใช้ CI result เป็นส่วนหนึ่งของเกณฑ์ประเมิน

### Suggested Capstone

สร้างระบบ **Employee Compliance Data Hub** ที่ประกอบด้วย:

- PHP 8.5 และ Composer
- REST API
- MySQL หรือ PostgreSQL
- CSV import แบบ streaming
- Redis/Valkey rate limiting
- Background job สำหรับประมวลผลรายงาน
- Structured logs และ audit trail
- Object storage สำหรับเอกสาร
- PHPUnit tests และ GitHub Actions

---

## Migration

อ่าน [`MIGRATION.md`](MIGRATION.md) ก่อนนำโค้ดเก่ามาใช้ โดยเฉพาะ:

- Deprecated syntax
- Implicitly nullable parameters
- PHPUnit major versions
- Session security
- JSON error handling
- PDO configuration
- CSV escape behavior ใน PHP 8.4+
- Extension and platform requirements

ติดตามการเปลี่ยนแปลงของหลักสูตรที่ [`CHANGELOG.md`](CHANGELOG.md)

---

## References

### PHP and Standards

- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [PHP Manual](https://www.php.net/manual/en/)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHP-FIG Standards](https://www.php-fig.org/psr/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

### Security

- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

### Data Platforms

- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/current/)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [MongoDB PHP Library](https://www.mongodb.com/docs/php-library/current/)
- [Redis PHP Documentation](https://redis.io/docs/latest/develop/clients/php/)

---

## License

เผยแพร่ภายใต้ [MIT License](LICENSE)

## Author

**Phumin Decoknoi**  
GitHub: [`PhuminDecOKnoi`](https://github.com/PhuminDecOKnoi)

---

> **Learn the language. Design for failure. Build securely. Test continuously. Operate professionally.**
