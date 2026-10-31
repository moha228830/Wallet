<?php

namespace App\Services\Payment\DTO;

readonly class PaymentInstruction
{
    /**
     * @param  array<string>  $notes
     */
    public function __construct(
        public string $reference,
        public string $date,
        public float|string $amount,
        public string $currency,
        public string $senderAccountNumber,
        public string $receiverBankCode,
        public string $receiverAccountNumber,
        public string $beneficiaryName,
        public array $notes = [],
        public int|string|null $paymentType = null,
        public ?string $chargeDetails = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reference: (string) ($data['reference'] ?? ''),
            date: (string) ($data['date'] ?? ''),
            amount: $data['amount'] ?? 0.0,
            currency: (string) ($data['currency'] ?? 'SAR'),
            senderAccountNumber: (string) ($data['sender_account_number'] ?? $data['sender_account'] ?? ''),
            receiverBankCode: (string) ($data['receiver_bank_code'] ?? $data['bank_code'] ?? ''),
            receiverAccountNumber: (string) ($data['receiver_account_number'] ?? $data['receiver_account'] ?? ''),
            beneficiaryName: (string) ($data['beneficiary_name'] ?? ''),
            notes: (array) ($data['notes'] ?? []),
            paymentType: $data['payment_type'] ?? null,
            chargeDetails: isset($data['charge_details']) ? (string) $data['charge_details'] : null,
        );
    }
}
