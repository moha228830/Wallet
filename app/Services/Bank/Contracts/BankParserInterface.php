<?php

namespace App\Services\Bank\Contracts;

use App\Services\Bank\DTO\ParsedTransaction;
use Generator;

interface BankParserInterface
{
    public function getBankName(): string;

    public function parseLine(string $line): ParsedTransaction;

    /**
     * @return Generator<ParsedTransaction>
     */
    public function parse(string $payload): Generator;
}
