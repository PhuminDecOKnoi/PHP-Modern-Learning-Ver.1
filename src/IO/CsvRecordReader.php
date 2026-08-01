<?php

declare(strict_types=1);

namespace App\IO;

use Generator;
use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

/**
 * อ่าน CSV แบบ streaming และ map แต่ละแถวด้วย header row
 *
 * Class นี้ตั้งใจใช้เป็นตัวอย่างการแยก file I/O ออกจาก business logic
 * ผู้เรียกต้องรับผิดชอบ domain validation ของแต่ละ record เพิ่มเติม
 */
final readonly class CsvRecordReader
{
    public function __construct(
        private string $separator = ',',
        private string $enclosure = '"',
        private string $escape = '',
    ) {
        if (strlen($this->separator) !== 1) {
            throw new InvalidArgumentException(
                'CSV separator must be exactly one byte.',
            );
        }

        if (strlen($this->enclosure) !== 1) {
            throw new InvalidArgumentException(
                'CSV enclosure must be exactly one byte.',
            );
        }

        if (strlen($this->escape) > 1) {
            throw new InvalidArgumentException(
                'CSV escape must be empty or exactly one byte.',
            );
        }
    }

    /**
     * @return Generator<int, array<string, string|null>>
     */
    public function records(string $path): Generator
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('CSV file is not readable.');
        }

        $file = new SplFileObject($path, 'rb');
        $file->setCsvControl(
            $this->separator,
            $this->enclosure,
            $this->escape,
        );

        $header = $file->fgetcsv();

        if (!is_array($header) || $header === [null]) {
            throw new RuntimeException('CSV header row is missing.');
        }

        $normalizedHeader = array_map(
            static fn (mixed $value): string => trim((string) $value),
            $header,
        );

        if (in_array('', $normalizedHeader, true)) {
            throw new RuntimeException('CSV header contains an empty column.');
        }

        if (count(array_unique($normalizedHeader)) !== count($normalizedHeader)) {
            throw new RuntimeException('CSV header contains duplicate columns.');
        }

        $lineNumber = 1;

        while (!$file->eof()) {
            $row = $file->fgetcsv();
            $lineNumber++;

            if (!is_array($row) || $row === [null]) {
                continue;
            }

            if (count($row) !== count($normalizedHeader)) {
                throw new RuntimeException(
                    "CSV column count mismatch on line {$lineNumber}.",
                );
            }

            $record = array_combine($normalizedHeader, $row);

            if ($record === false) {
                throw new RuntimeException(
                    "Unable to map CSV row on line {$lineNumber}.",
                );
            }

            yield $lineNumber => $record;
        }
    }
}
