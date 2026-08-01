# PHP Application Engineering Technology Matrix

เอกสารนี้สรุป prerequisites, use cases, failure modes และระดับความจำเป็นของเทคโนโลยีในหลักสูตร Phase 2

## Classification

- **Core** — ต้องมีเพื่อรันตัวอย่างหลักและ CI
- **Recommended** — ควรมีเมื่อเรียน module ที่เกี่ยวข้อง
- **Optional Service** — ต้องติดตั้ง service ภายนอกเฉพาะบท
- **Architecture Concept** — เรียนแนวคิดได้โดยไม่ต้องติดตั้งระบบจริง

---

## PHP Extensions

| Extension | Level | ใช้ในบท | Purpose |
|---|---|---:|---|
| `json` | Core | 04, 10, 13, 14 | Strict JSON processing |
| `pdo` | Core | 03, 07–09 | Database abstraction |
| `pdo_mysql` | Recommended | 03, 07 | MySQL examples |
| `pdo_sqlite` | Recommended | 08 | SQLite and tests |
| `pdo_pgsql` | Recommended | 09 | PostgreSQL examples |
| `curl` | Recommended | 14 | HTTP client |
| `intl` | Recommended | 16 | Locale, number, date, collation |
| `mbstring` | Recommended | 16 | Multibyte text operations |
| `xmlreader` | Recommended | 13 | Streaming XML parsing |
| `xmlwriter` | Recommended | 13 | Streaming XML generation |
| `zip` | Recommended | 13 | ZIP archive processing |
| `mongodb` | Optional | 10 | MongoDB driver |
| `pcntl` | Optional | 17 | CLI worker signal handling |

---

## Composer Packages

| Package | Level | Purpose |
|---|---|---|
| `phpunit/phpunit` | Core dev | Automated testing |
| `mongodb/mongodb` | Optional | Official high-level MongoDB library |
| `predis/predis` | Optional | Redis-compatible PHP client |
| `psr/http-message` | Optional | PSR-7 HTTP messages |
| `psr/http-factory` | Optional | PSR-17 factories |
| `psr/http-client` | Optional | PSR-18 client interface |
| `psr/log` | Optional | PSR-3 logger interface |

---

## External Services

| Service | Level | Primary use | Critical failure modes |
|---|---|---|---|
| MySQL | Core course database | OLTP/CRUD | Locking, connection exhaustion, migration failure |
| PostgreSQL | Optional Service | Advanced SQL/concurrency | Serialization failure, long transactions, index misuse |
| SQLite | Embedded | Local apps/tests | File locking, concurrent writes, unsafe backup |
| MongoDB | Optional Service | Document aggregates | Unbounded document growth, operator injection, missing indexes |
| Redis/Valkey | Optional Service | Cache/rate limit/session | Stale cache, eviction, stampede, fail-open policy |
| Object Storage | Architecture/Optional | Documents and large files | Unauthorized access, orphan objects, lifecycle errors |
| Queue/Broker | Architecture/Optional | Background jobs | Duplicate delivery, retry storm, DLQ growth |

---

## Learning Environment Profiles

### Profile 1 — Core Learner

```text
PHP 8.4/8.5
Composer 2
ext-json
ext-pdo
PHPUnit 13
```

ใช้เรียนบท 00–06 และรัน CI examples

### Profile 2 — Data Engineering Learner

```text
Core Profile
pdo_mysql
pdo_sqlite
pdo_pgsql
MySQL/PostgreSQL services
```

ใช้เรียนบท 07–09

### Profile 3 — Integration Learner

```text
Core Profile
ext-curl
ext-xmlreader
ext-xmlwriter
ext-zip
```

ใช้เรียนบท 12–15

### Profile 4 — Internationalization Learner

```text
Core Profile
ext-intl
ext-mbstring
ICU data available in environment
```

ใช้เรียนบท 16

### Profile 5 — Distributed Application Learner

```text
Core Profile
MongoDB extension/library
Redis/Valkey client/service
Queue/broker of choice
Object storage or emulator
```

ใช้เรียนบท 10–11 และ 17–19

---

## Support Policy

1. Core examples ต้องผ่าน PHP 8.4 และ 8.5
2. Optional service examples ต้องระบุ dependency และไม่ทำให้ core CI ล้มเหลว
3. Version-specific feature ต้องติดป้ายกำกับชัดเจน
4. External service integrations ควรมี container-based CI แยกจาก core tests
5. Security configuration ต้องอ้างอิง official documentation และ deployment policy ขององค์กร
6. ห้าม commit credentials, connection strings หรือ real personal data
7. เมื่อ dependency major version เปลี่ยน ต้องตรวจ compatibility ก่อนแก้บทเรียน

---

## Production Review Questions

ก่อนเลือกเทคโนโลยี ให้ตอบ:

- Source of truth อยู่ที่ใด
- Consistency requirement คืออะไร
- Failure mode ที่ยอมรับได้คืออะไร
- Retry ทำให้เกิดผลซ้ำหรือไม่
- Data retention และ privacy requirement คืออะไร
- Monitoring และ recovery ทำอย่างไร
- ทีมมี operational capability ดูแลระบบนี้หรือไม่
- การเพิ่ม service ใหม่คุ้มกับ complexity หรือไม่

---

## References

- PHP Manual — https://www.php.net/manual/en/
- Composer — https://getcomposer.org/doc/
- PHP-FIG — https://www.php-fig.org/psr/
- PHPUnit — https://phpunit.de/documentation.html
