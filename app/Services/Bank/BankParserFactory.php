<?php

namespace App\Services\Bank;

use App\Services\Bank\Contracts\BankParserInterface;
use App\Services\Bank\Parsers\AcmeParser;
use App\Services\Bank\Parsers\PayTechParser;
use InvalidArgumentException;

class BankParserFactory
{
    /**
     * @var array<string, class-string<BankParserInterface>>
     */
    protected array $parsers = [
        'paytech' => PayTechParser::class,
        'acme' => AcmeParser::class,
    ];

    public function get(string $bank): BankParserInterface
    {
        $key = strtolower(trim($bank));

        if (! isset($this->parsers[$key])) {
            throw new InvalidArgumentException("Unsupported bank parser: [{$bank}]");
        }

        return app($this->parsers[$key]);
    }

    public function register(string $bank, string $parserClass): void
    {
        $this->parsers[strtolower(trim($bank))] = $parserClass;
    }
}
