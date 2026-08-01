# Changelog

All notable changes to this learning repository are documented here.

The format follows the principles of [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), while course versions are identified by release date and PHP baseline.

## [Unreleased]

### Planned

- Add container-based integration tests for PostgreSQL, Redis and MongoDB
- Add static analysis and coding-style automation
- Add architecture capstone project integrating modules 07–19
- Review legacy root lessons and add canonical cross-links

## [2026.08 — PHP Application Engineering Phase 2]

### Added

- Database driver and portable data-access lesson
- SQLite local application and integration-testing lesson
- PostgreSQL advanced SQL, JSONB and concurrency lesson
- MongoDB professional document-modeling lesson
- Redis/Valkey cache, session and distributed rate-limit lesson
- Filesystem, streams and safe file-processing lesson
- CSV, JSON, NDJSON, XML and ZIP processing lesson
- HTTP client, cURL, URI and PSR-7/17/18 lesson
- TCP, UDP, TLS, network streams and socket lesson
- Unicode, Thai language and internationalization lesson
- Queue, worker, idempotency, outbox/inbox and DLQ lesson
- PSR-3 logging, metrics, SLO and observability lesson
- Storage architecture, object storage and retention lesson
- Executable streaming `CsvRecordReader`
- PHPUnit tests for CSV header mapping and failure cases
- Optional extension/package matrix in `composer.json`
- Technology matrix document for module prerequisites and production concerns

### Changed

- Expanded README from seven foundational lessons to a twenty-lesson application-engineering curriculum
- Grouped learning path into Foundation, Database, Data Exchange, Network and Operations modules
- Added explicit failure modes, security controls, testing strategy and production checklists to every Phase 2 lesson
- Clarified that optional infrastructure technologies are not mandatory core dependencies
- Extended production guidance to cover SSRF, archive extraction, cache stampede, retry, backpressure, Unicode and storage governance

### Validation

- PHP 8.4 and PHP 8.5 CI matrix
- Composer strict validation
- PHP syntax checks
- PHPUnit 13 tests
- CSV parser tests use temporary files and require no external service

## [2026.07 — PHP 8.4–8.5 Edition]

### Added

- PHP 8.5 primary teaching baseline
- PHP 8.4 minimum supported version
- Version governance document
- Composer platform requirements
- PHPUnit 13 development dependency
- GitHub Actions matrix for PHP 8.4 and PHP 8.5
- Canonical lessons under `lessons/`
- PHP 8.4 and PHP 8.5 feature lesson
- Secure PDO and MySQL lesson
- REST API and JSON validation lesson
- Authentication, session and CSRF security lesson
- PHPUnit 13 lesson
- Executable `Email` Value Object and automated tests
- PHP 8.4–8.5 migration guide
- `.gitignore` for PHP development artifacts

### Changed

- Reworked `README.md` as the canonical course index
- Replaced an unspecified PHP baseline with an explicit version matrix
- Updated testing guidance from PHPUnit 12 to PHPUnit 13
- Clarified that PHP 8.6 pre-release builds are for testing only
- Added production security and deployment checks

### Compatibility

| Component | Supported |
|---|---|
| PHP | 8.4 and 8.5 |
| PHPUnit | 13.x |
| Composer | 2.x |
| PDO | Required |
| PDO MySQL | Required for MySQL examples |

## [2026.03 — Initial Modern Learning Edition]

### Added

- Object-Oriented Programming in PHP
- Namespaces and Autoloading with Composer
- Forms and Validation in PHP
- PHP with MySQL using PDO
- Error Handling and Exceptions in PHP
- REST API Basics with PHP
- Authentication and Password Hashing in PHP
- PHP Testing with PHPUnit
- Clean Code and Project Structure in PHP

### Changed

- Repository license changed to MIT
