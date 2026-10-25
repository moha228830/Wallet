<?php

namespace App\Services\Bank\Parsers;

use App\Services\Bank\DTO\ParsedTransaction;
use InvalidArgumentException;

class AcmeParser extends AbstractBankParser
{
    public function getBankName(): string
    {
        return 'acme';
    }

    public function parseLine(string $line): ParsedTransaction
    {
        $parts = explode('//', $line);

        if (count($parts) < 3) {
            throw new InvalidArgumentException("Invalid Acme format line: {$line}");
        }

        $amount = $this->normalizeAmount(trim($parts[0]));
        $reference = trim($parts[1]);
        $date = $this->normalizeDate(trim($parts[2]));

        $rawDateDigits = str_replace('-', '', $date);
        $clientHint = null;
        if (strlen($reference) > 8 && str_starts_with($reference, $rawDateDigits)) {
            $clientHint = substr($reference, 8);
        }

        return new ParsedTransaction(
            reference: $reference,
            amount: $amount,
            date: $date,
            metadata: null,
            clientIdentifier: $clientHint,
        );
    }
}
