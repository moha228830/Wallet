<?php

namespace Tests\Unit;

use App\Services\Bank\Parsers\PayTechParser;
use PHPUnit\Framework\TestCase;

class PayTechParserTest extends TestCase
{
    protected PayTechParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new PayTechParser;
    }

    public function test_parses_sample_transaction_line(): void
    {
        $line = '20250615156,50#202506159000001#note/debt payment march/internal_reference/A462JE81';

        $parsed = $this->parser->parseLine($line);

        $this->assertSame('202506159000001', $parsed->reference);
        $this->assertSame(156.50, $parsed->amount);
        $this->assertSame('2025-06-15', $parsed->date);
        $this->assertSame([
            'note' => 'debt payment march',
            'internal_reference' => 'A462JE81',
        ], $parsed->metadata);
        $this->assertSame('9000001', $parsed->clientIdentifier);
    }

    public function test_parses_multiline_payload(): void
    {
        $payload = implode("\n", [
            '20250615100,00#202506151000001#note/rent',
            '20250616250,75#202506162000002#note/groceries/tag/food',
        ]);

        $results = iterator_to_array($this->parser->parse($payload));

        $this->assertCount(2, $results);
        $this->assertSame(100.00, $results[0]->amount);
        $this->assertSame(250.75, $results[1]->amount);
        $this->assertSame('2025-06-16', $results[1]->date);
    }
}
