# Contributing Guide

Thank you for contributing to **PHP Modern Learning**.

## Contribution Scope

Contributions may include:

- lesson corrections and clarifications;
- runnable PHP examples;
- tests and CI improvements;
- security hardening;
- migration notes;
- architecture explanations;
- Thai or English documentation improvements;
- accessibility and developer-experience improvements.

## Before You Submit

1. Read the relevant lesson and linked documentation.
2. Preserve the course baseline and version policy.
3. Keep examples educational, focused, and runnable.
4. Never include secrets, personal data, proprietary code, or copyrighted text without permission.
5. Use clear commit messages and explain why the change is needed.

## PHP Standards

- Use `declare(strict_types=1);` where appropriate.
- Follow PSR-12 formatting and PSR-4 autoloading conventions.
- Prefer typed properties, return types, and explicit exceptions.
- Use PDO prepared statements for SQL examples.
- Separate application logic, data access, and presentation concerns.
- Add comments that explain intent, trade-offs, and risks rather than restating syntax.
- Avoid deprecated functions and undocumented behavior.

## Quality Checks

Run the checks supported by the repository before submitting:

```bash
composer validate
composer install
composer test
```

Where available, also run syntax checks, static analysis, coding-style checks, and relevant integration tests.

## Documentation Standards

- Use semantic Markdown headings.
- Keep terminology consistent across lessons.
- State version assumptions explicitly.
- Link to primary documentation when adding technical claims.
- Distinguish production guidance from demonstration-only examples.

## Pull Request Expectations

A pull request should include:

- a concise summary;
- affected lessons or files;
- test or verification evidence;
- security and compatibility notes;
- migration impact, if any;
- screenshots only when they add value.

By contributing, you agree that your contribution may be distributed under the repository's MIT License.
