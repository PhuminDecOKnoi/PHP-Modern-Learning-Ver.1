<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Value Object สำหรับเก็บอีเมลที่ผ่านการตรวจสอบแล้ว
 *
 * ตัวอย่างนี้ตั้งใจใช้ syntax ที่รันได้ทั้ง PHP 8.4 และ PHP 8.5
 * เพื่อให้ GitHub Actions ตรวจ compatibility ได้จริง
 */
final readonly class Email
{
    private function __construct(public string $value)
    {
    }

    public static function fromString(string $email): self
    {
        // Normalize ก่อน validate เพื่อให้ผลลัพธ์สม่ำเสมอ
        $normalized = strtolower(trim($email));

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        return new self($normalized);
    }
}
