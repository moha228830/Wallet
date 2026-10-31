<?php

namespace Tests\Unit;

use App\Services\Bank\Parsers\AcmeParser;
use PHPUnit\Framework\TestCase;

class AcmeParserTest extends TestCase
{
    protected AcmeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AcmeParser;
    }

    public function test_parses_sample_transaction_line(): void
    {
        $line = '156,50//202506159000001//20250615';

        $parsed = $this->parser->parseLine($line);

        $this->assertSame('202506159000001', $parsed->reference);
        $this->assertSame(156.50, $parsed->amount);
        $this->assertSame('2025-06-15', $parsed->date);
        $this->assertNull($parsed->metadata);
        $this->assertSame('9000001', $parsed->clientIdentifier);
    }

    public function test_parses_multiline_payload(): void
    {
        $payload = implode("\n", [
            '50,20//202506151000001//20250615',
            '1200,00//202506161000002//20250616',
        ]);

        $results = iterator_to_array($this->parser->parse($payload));

        $this->assertCount(2, $results);
        $this->assertSame(50.20, $results[0]->amount);
        $this->assertSame(1200.00, $results[1]->amount);
        $this->assertSame('2025-06-16', $results[1]->date);
    }
}
