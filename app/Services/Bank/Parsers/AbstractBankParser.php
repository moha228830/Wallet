<?php

namespace App\Services\Bank\Parsers;

use App\Services\Bank\Contracts\BankParserInterface;
use App\Services\Bank\DTO\ParsedTransaction;
use Generator;
use InvalidArgumentException;

abstract class AbstractBankParser implements BankParserInterface
{
    /**
     * @return Generator<ParsedTransaction>
     */
    public function parse(string $payload): Generator
    {
        $line = strtok($payload, "\r\n");

        while ($line !== false) {
            $trimmed = trim($line);

            if ($trimmed !== '') {
                yield $this->parseLine($trimmed);
            }

            $line = strtok("\r\n");
        }
    }

    protected function normalizeAmount(string $value): float
    {
        $cleaned = trim(str_replace([' ', "\t"], '', $value));
        $normalized = str_replace(',', '.', $cleaned);

        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("Invalid transaction amount: {$value}");
        }

        return round((float) $normalized, 2);
    }

    protected function normalizeDate(string $rawDate): string
    {
        $raw = trim($rawDate);

        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $raw, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        throw new InvalidArgumentException("Unrecognized date format: {$rawDate}");
    }
}
