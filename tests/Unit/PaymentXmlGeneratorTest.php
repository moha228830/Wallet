<?php

namespace Tests\Unit;

use App\Services\Payment\DTO\PaymentInstruction;
use App\Services\Payment\PaymentXmlGenerator;
use PHPUnit\Framework\TestCase;

class PaymentXmlGeneratorTest extends TestCase
{
    protected PaymentXmlGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new PaymentXmlGenerator;
    }

    public function test_generates_xml_matching_sample_structure(): void
    {
        $instruction = new PaymentInstruction(
            reference: 'e0f4763d-28ea-42d4-ac1c-c4013c242105',
            date: '2025-02-25 06:33:00+03',
            amount: 177.39,
            currency: 'SAR',
            senderAccountNumber: 'SA6980000204608016212908',
            receiverBankCode: 'FDCSSARI',
            receiverAccountNumber: 'SA6980000204608016211111',
            beneficiaryName: 'Jane Doe',
            notes: ['Lorem Epsum', 'Dolor Sit Amet'],
            paymentType: 421,
            chargeDetails: 'RB',
        );

        $xml = $this->generator->generate($instruction);

        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-8"?>', $xml);
        $this->assertStringContainsString('<Reference>e0f4763d-28ea-42d4-ac1c-c4013c242105</Reference>', $xml);
        $this->assertStringContainsString('<Date>2025-02-25 06:33:00+03</Date>', $xml);
        $this->assertStringContainsString('<Amount>177.39</Amount>', $xml);
        $this->assertStringContainsString('<Currency>SAR</Currency>', $xml);
        $this->assertStringContainsString('<AccountNumber>SA6980000204608016212908</AccountNumber>', $xml);
        $this->assertStringContainsString('<BankCode>FDCSSARI</BankCode>', $xml);
        $this->assertStringContainsString('<AccountNumber>SA6980000204608016211111</AccountNumber>', $xml);
        $this->assertStringContainsString('<BeneficiaryName>Jane Doe</BeneficiaryName>', $xml);
        $this->assertStringContainsString('<Notes>', $xml);
        $this->assertStringContainsString('<Note>Lorem Epsum</Note>', $xml);
        $this->assertStringContainsString('<Note>Dolor Sit Amet</Note>', $xml);
        $this->assertStringContainsString('<PaymentType>421</PaymentType>', $xml);
        $this->assertStringContainsString('<ChargeDetails>RB</ChargeDetails>', $xml);
    }

    public function test_notes_tag_omitted_when_no_notes_provided(): void
    {
        $instruction = new PaymentInstruction(
            reference: 'ref-1',
            date: '2025-02-25 06:33:00+03',
            amount: 50.00,
            currency: 'SAR',
            senderAccountNumber: 'SA01',
            receiverBankCode: 'BANK',
            receiverAccountNumber: 'SA02',
            beneficiaryName: 'John',
            notes: [],
            paymentType: 100,
            chargeDetails: 'OUR',
        );

        $xml = $this->generator->generate($instruction);

        $this->assertStringNotContainsString('<Notes>', $xml);
        $this->assertStringNotContainsString('<Note>', $xml);
    }

    public function test_payment_type_tag_omitted_when_value_is_99(): void
    {
        $instruction = new PaymentInstruction(
            reference: 'ref-2',
            date: '2025-02-25 06:33:00+03',
            amount: 50.00,
            currency: 'SAR',
            senderAccountNumber: 'SA01',
            receiverBankCode: 'BANK',
            receiverAccountNumber: 'SA02',
            beneficiaryName: 'John',
            notes: ['Test note'],
            paymentType: 99,
            chargeDetails: 'OUR',
        );

        $xml = $this->generator->generate($instruction);

        $this->assertStringNotContainsString('<PaymentType>', $xml);
    }

    public function test_charge_details_tag_omitted_when_value_is_sha(): void
    {
        $instruction = new PaymentInstruction(
            reference: 'ref-3',
            date: '2025-02-25 06:33:00+03',
            amount: 50.00,
            currency: 'SAR',
            senderAccountNumber: 'SA01',
            receiverBankCode: 'BANK',
            receiverAccountNumber: 'SA02',
            beneficiaryName: 'John',
            notes: ['Test note'],
            paymentType: 421,
            chargeDetails: 'SHA',
        );

        $xml = $this->generator->generate($instruction);

        $this->assertStringNotContainsString('<ChargeDetails>', $xml);
    }
}
