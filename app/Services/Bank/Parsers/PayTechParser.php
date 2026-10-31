<?php

namespace App\Services\Bank\Parsers;

use App\Services\Bank\DTO\ParsedTransaction;
use InvalidArgumentException;

class PayTechParser extends AbstractBankParser
{
    public function getBankName(): string
    {
        return 'paytech';
    }

    public function parseLine(string $line): ParsedTransaction
    {
        $parts = explode('#', $line);

        if (count($parts) < 3) {
            throw new InvalidArgumentException("Invalid PayTech format line: {$line}");
        }

        $firstSegment = trim($parts[0]);
        $reference = trim($parts[1]);
        $metadataSegment = trim($parts[2]);

        if (! preg_match('/^(\d{8})[\s,]*(.+)$/', $firstSegment, $matches)) {
            throw new InvalidArgumentException("Unable to extract date and amount from PayTech line: {$firstSegment}");
        }

        $date = $this->normalizeDate($matches[1]);
        $amount = $this->normalizeAmount($matches[2]);

        $metadata = [];
        $tokens = explode('/', $metadataSegment);
        $totalTokens = count($tokens);

        for ($i = 0; $i < $totalTokens; $i += 2) {
            $key = trim($tokens[$i]);
            $val = isset($tokens[$i + 1]) ? trim($tokens[$i + 1]) : null;

            if ($key !== '') {
                $metadata[$key] = $val;
            }
        }

        $clientHint = null;
        if (strlen($reference) > 8 && str_starts_with($reference, $matches[1])) {
            $clientHint = substr($reference, 8);
        }

        return new ParsedTransaction(
            reference: $reference,
            amount: $amount,
            date: $date,
            metadata: ! empty($metadata) ? $metadata : null,
            clientIdentifier: $clientHint,
        );
    }
}
