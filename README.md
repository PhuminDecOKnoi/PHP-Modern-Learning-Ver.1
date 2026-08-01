# PHP Modern Learning — PHP 8.4–8.5 Application Engineering Edition

[![PHP](https://img.shields.io/badge/PHP-8.4%20%7C%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-13-3C9CD7)](https://phpunit.de/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP CI](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml/badge.svg)](https://github.com/PhuminDecOKnoi/PHP-Modern-Learning-Ver.1/actions/workflows/php-ci.yml)

หลักสูตร PHP แบบ **professional, intensive และ production-oriented** สำหรับผู้เรียน นักพัฒนา และผู้สอนที่ต้องการเชื่อมจากพื้นฐานภาษาไปสู่การออกแบบระบบจริง โดยครอบคลุม PHP 8.4–8.5, Database Engineering, Filesystem/Streams, Data Formats, HTTP/Network, Security, Queue, Storage และ Observability

> **Course baseline:** ใช้ PHP 8.5 เป็นเวอร์ชันหลัก รองรับ PHP 8.4 เป็นขั้นต่ำ และใช้ PHP 8.6 pre-release เพื่อศึกษาการเปลี่ยนแปลงเท่านั้น ไม่ใช้เป็น production baseline

---

## Version Policy

| Component | Course baseline | หมายเหตุ |
|---|---:|---|
| PHP | `8.5` | Primary teaching version |
| Minimum PHP | `8.4` | Minimum supported version |
| PHP Preview | `8.6 alpha/beta` | Preview/testing only |
| PHPUnit | `13.x` | Automated testing |
| Composer | `2.x` | Dependencies และ PSR-4 autoload |
| MySQL | `8.0+` | Core relational database example |
| PostgreSQL | Current supported release | Advanced SQL/concurrency module |
| SQLite | Current bundled/system release | Local apps และ tests |
| MongoDB | Official PHP library/extension | Optional document database module |
| Redis/Valkey | Supported server/client release | Optional cache/rate-limit module |

รายละเอียดอยู่ที่ [`docs/version-policy.md`](docs/version-policy.md) และ [`docs/technology-matrix.md`](docs/technology-matrix.md)

---

## จุดประสงค์ของ Repository

- ปูพื้นฐาน PHP อย่างถูกต้องและทันสมัย
- พัฒนาจาก syntax ไปสู่ application architecture
- สอนให้แยก business logic, data access และ infrastructure
- ส่งเสริม type safety, clean code, security และ testing
- อธิบาย trade-offs ไม่ใช่เพียงวิธีเรียก function
- ใช้เป็น lesson note, self-study course และ GitHub portfolio
- มีตัวอย่าง executable code และ GitHub Actions validation

---

# Learning Path

## Module A — Modern PHP Foundation

| บท | บทเรียน | เป้าหมาย |
|---:|---|---|
| 00 | [PHP 8.4–8.5 Course Baseline](lessons/00-php-8-4-8-5-course-baseline.md) | Version และ environment governance |
| 01 | [Modern Project Setup](lessons/01-modern-project-setup.md) | Composer, PSR-4 และ project structure |
| 02 | [PHP 8.4 and 8.5 Features](lessons/02-php-8-4-and-8-5-features.md) | ใช้ language features รุ่นใหม่ |
| 03 | [Secure PDO and MySQL](lessons/03-secure-pdo-and-mysql.md) | Prepared statements และ transactions |
| 04 | [REST API and Input Validation](lessons/04-rest-api-and-input-validation.md) | JSON API และ error handling |
| 05 | [Authentication Security](lessons/05-authentication-security.md) | Password, session และ CSRF |
| 06 | [Testing with PHPUnit 13](lessons/06-testing-with-phpunit-13.md) | Unit/integration testing foundation |

## Module B — Database Engineering

| บท | บทเรียน | เป้าหมาย |
|---:|---|---|
| 07 | [Database Drivers and Portable Data Access](lessons/07-database-drivers-and-portable-data-access.md) | PDO drivers, repository และ migrations |
| 08 | [SQLite for Local Apps and Testing](lessons/08-sqlite-local-apps-and-testing.md) | WAL, locking, foreign keys และ in-memory tests |
| 09 | [PostgreSQL Advanced SQL and Concurrency](lessons/09-postgresql-advanced-sql-and-concurrency.md) | JSONB, isolation, locking และ EXPLAIN |
| 10 | [MongoDB Document Data Modeling](lessons/10-mongodb-document-data-modeling.md) | BSON, document modeling, indexing และ aggregation |
| 11 | [Redis and Valkey](lessons/11-redis-valkey-cache-and-rate-limiting.md) | Cache-aside, TTL, stampede และ rate limiting |

## Module C — Files and Data Exchange

| บท | บทเรียน | เป้าหมาย |
|---:|---|---|
| 12 | [Filesystem, Streams and Safe File Processing](lessons/12-filesystem-streams-and-safe-file-processing.md) | Streaming, atomic write, upload security |
| 13 | [CSV, JSON, XML and ZIP Processing](lessons/13-csv-json-xml-and-zip-processing.md) | Structured data, large files และ archive safety |

## Module D — Integration and Network Engineering

| บท | บทเรียน | เป้าหมาย |
|---:|---|---|
| 14 | [HTTP Client, cURL, URI and PSR Standards](lessons/14-http-client-curl-uri-and-psr-standards.md) | HTTP reliability, SSRF, PSR-7/17/18 |
| 15 | [Network Streams and Sockets](lessons/15-network-streams-and-sockets.md) | TCP/UDP, framing, TLS และ backpressure |

## Module E — Internationalization and Operations

| บท | บทเรียน | เป้าหมาย |
|---:|---|---|
| 16 | [Unicode, Thai and Internationalization](lessons/16-unicode-thai-and-internationalization.md) | Grapheme, normalization, locale และ timezone |
| 17 | [Queue, Workers and Background Jobs](lessons/17-queue-workers-and-background-jobs.md) | Idempotency, outbox, retry และ DLQ |
| 18 | [Logging, Metrics and Observability](lessons/18-logging-metrics-and-observability.md) | PSR-3, structured logs, SLO และ audit trail |
| 19 | [Storage Architecture and Object Storage](lessons/19-storage-architecture-and-object-storage.md) | Storage abstraction, retention และ secure delivery |

---

## Course Design Principles

ทุกบทพยายามตอบ 5 คำถาม:

1. เทคโนโลยีนี้แก้ปัญหาอะไร
2. ใช้เมื่อใดและไม่ควรใช้เมื่อใด
3. Failure modes สำคัญคืออะไร
4. Security และ operational controls ใดจำเป็น
5. จะทดสอบและตรวจสอบ production behavior อย่างไร

---

## Requirements

```bash
php -v
composer --version
```

ค่าที่คาดหวัง:

```text
PHP 8.4.x หรือ PHP 8.5.x
Composer 2.x
```

ติดตั้ง dependencies หลัก:

```bash
composer install
```

ตรวจ configuration:

```bash
composer validate --strict
composer check-platform-reqs
```

Optional technologies เช่น PostgreSQL, MongoDB, Redis/Valkey, `intl`, `curl`, `zip` และ `xmlreader` ให้ติดตั้งตามบทที่เรียน ไม่ได้บังคับทุกเครื่องใน core course

---

## Project Structure

```text
PHP-Modern-Learning-Ver.1/
├── .github/workflows/php-ci.yml
├── docs/
│   ├── technology-matrix.md
│   └── version-policy.md
├── lessons/
│   ├── 00-php-8-4-8-5-course-baseline.md
│   ├── 01-modern-project-setup.md
│   ├── ...
│   └── 19-storage-architecture-and-object-storage.md
├── src/
│   ├── IO/CsvRecordReader.php
│   └── Support/Email.php
├── tests/
│   ├── IO/CsvRecordReaderTest.php
│   └── Support/EmailTest.php
├── composer.json
├── phpunit.xml
├── MIGRATION.md
├── CHANGELOG.md
├── LICENSE
└── README.md
```

บทเรียนเดิมที่อยู่ root repository ยังคงเก็บไว้เพื่อรักษาประวัติและลิงก์เดิม ส่วน `lessons/` เป็น canonical curriculum

---

## Executable Examples

### Email Value Object

```bash
composer test -- --filter EmailTest
```

### Streaming CSV Reader

`src/IO/CsvRecordReader.php` แสดงตัวอย่าง:

- `SplFileObject`
- Streaming records
- Header mapping
- Duplicate header validation
- Column count validation
- Explicit CSV escape parameter สำหรับ PHP 8.4+

```bash
composer test -- --filter CsvRecordReaderTest
```

---

## Coding Standard

```php
<?php

declare(strict_types=1);
```

- PSR-4 autoloading
- PSR-12/PER-compatible style
- Explicit parameter และ return types
- Constructor injection
- Prepared statements
- Output escaping
- Environment-based secrets
- Production-safe exception handling
- Automated tests สำหรับ business logic

---

## Testing and CI

```bash
composer lint
composer test
composer check
```

GitHub Actions ตรวจ:

- Composer validation
- PHP syntax
- PHPUnit tests
- PHP 8.4 matrix
- PHP 8.5 matrix

Optional services ควรมี integration test pipeline แยก เช่น PostgreSQL, Redis หรือ MongoDB containers

---

## Security Baseline

- Validate input ที่ server
- Encode output ตาม context
- ใช้ prepared statements
- ใช้ `password_hash()` และ `password_verify()`
- Regenerate session ID หลัง login
- ใช้ CSRF protection
- ใช้ `JSON_THROW_ON_ERROR`
- กำหนด timeout สำหรับ network calls
- ป้องกัน SSRF, path traversal และ archive extraction attacks
- ไม่ log secrets หรือ PII เกินจำเป็น
- ใช้ least privilege ทุก infrastructure component

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

---

## References

- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [PHP Manual](https://www.php.net/manual/en/)
- [Composer Documentation](https://getcomposer.org/doc/)
- [PHP-FIG Standards](https://www.php-fig.org/psr/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
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

> เรียนให้เข้าใจโครงสร้าง ออกแบบให้รองรับความล้มเหลว เขียนให้ปลอดภัย และตรวจสอบได้ก่อน production
