<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    #[Test]
    public function it_normalizes_a_valid_email(): void
    {
        $email = Email::fromString(' USER@EXAMPLE.COM ');

        self::assertSame('user@example.com', $email->value);
    }

    public static function invalidEmails(): iterable
    {
        yield 'empty value' => [''];
        yield 'plain text' => ['not-an-email'];
        yield 'missing domain' => ['user@'];
    }

    #[DataProvider('invalidEmails')]
    public function test_invalid_emails_are_rejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address.');

        Email::fromString($input);
    }
}
