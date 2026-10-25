<?php

namespace App\Services\Bank\DTO;

readonly class ParsedTransaction
{
    public function __construct(
        public string $reference,
        public float $amount,
        public string $date,
        public ?array $metadata = null,
        public ?string $clientIdentifier = null,
    ) {}

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'amount' => $this->amount,
            'date' => $this->date,
            'metadata' => $this->metadata,
            'client_identifier' => $this->clientIdentifier,
        ];
    }
}
