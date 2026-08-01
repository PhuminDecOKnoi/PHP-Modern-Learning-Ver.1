# Changelog

All notable changes to this learning repository are documented here.

The format follows the principles of [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), while course versions are identified by release date and PHP baseline.

## [Unreleased]

### Planned

- Add executable examples for PHP 8.4 Property Hooks
- Add PHP 8.5-only examples under a separate compatibility directory
- Add MySQL integration tests using an isolated test database
- Add static analysis and coding-style automation
- Review legacy root lessons and add canonical cross-links

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
