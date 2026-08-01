<?php

declare(strict_types=1);

namespace Tests\IO;

use App\IO\CsvRecordReader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CsvRecordReaderTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    #[Test]
    public function it_reads_records_using_the_header_row(): void
    {
        $path = $this->createCsv(
            "employee_code,full_name\n"
            . "EMP-0001,Example Employee\n"
            . "EMP-0002,Second Employee\n",
        );

        $reader = new CsvRecordReader();
        $records = iterator_to_array($reader->records($path));

        self::assertCount(2, $records);
        self::assertSame(
            [
                'employee_code' => 'EMP-0001',
                'full_name' => 'Example Employee',
            ],
            array_values($records)[0],
        );
    }

    #[Test]
    public function it_rejects_duplicate_header_columns(): void
    {
        $path = $this->createCsv(
            "employee_code,employee_code\n"
            . "EMP-0001,EMP-0001\n",
        );

        $reader = new CsvRecordReader();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'CSV header contains duplicate columns.',
        );

        iterator_to_array($reader->records($path));
    }

    #[Test]
    public function it_rejects_rows_with_a_different_column_count(): void
    {
        $path = $this->createCsv(
            "employee_code,full_name\n"
            . "EMP-0001\n",
        );

        $reader = new CsvRecordReader();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSV column count mismatch');

        iterator_to_array($reader->records($path));
    }

    #[Test]
    public function it_rejects_an_unreadable_path(): void
    {
        $reader = new CsvRecordReader();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSV file is not readable.');

        iterator_to_array($reader->records('/path/that/does/not/exist.csv'));
    }

    private function createCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'php-course-csv-');

        if ($path === false) {
            self::fail('Unable to create temporary CSV file.');
        }

        $bytes = file_put_contents($path, $content);

        if ($bytes === false) {
            self::fail('Unable to write temporary CSV file.');
        }

        $this->temporaryFiles[] = $path;

        return $path;
    }
}
