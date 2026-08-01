# Security Policy

## Supported Scope

This repository is an educational PHP application-engineering course. Security reports should focus on repository-owned code, examples, workflows, configuration, and documentation.

## Reporting a Vulnerability

Please do not disclose a suspected vulnerability publicly before it has been reviewed.

When reporting, include:

- affected file or module;
- PHP and dependency versions;
- clear reproduction steps;
- expected and actual behavior;
- security impact;
- suggested mitigation, if available.

Do not include real credentials, personal data, production secrets, or confidential system details.

## Security Baseline

Contributors should verify that changes:

- use prepared statements for database access;
- validate and normalize external input;
- encode output for the target context;
- avoid committing `.env`, API keys, passwords, tokens, or certificates;
- use secure password hashing and session settings;
- preserve CSRF, authentication, authorization, and least-privilege controls;
- validate uploaded files and generated paths;
- avoid unsafe deserialization and dynamic code execution;
- keep Composer dependencies and GitHub Actions under review;
- include tests for security-sensitive behavior where practical.

## Educational Use

Examples are designed for learning and may require additional hardening before production deployment. Users remain responsible for threat modeling, legal compliance, privacy controls, infrastructure security, monitoring, backup, and incident response in their own environments.
