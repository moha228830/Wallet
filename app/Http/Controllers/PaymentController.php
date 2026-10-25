<?php

namespace App\Http\Controllers;

use App\Services\Payment\DTO\PaymentInstruction;
use App\Services\Payment\PaymentXmlGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentXmlGenerator $xmlGenerator,
    ) {}

    public function generateXml(Request $request): Response
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'date' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'nullable|string',
            'sender_account_number' => 'required|string',
            'receiver_bank_code' => 'required|string',
            'receiver_account_number' => 'required|string',
            'beneficiary_name' => 'required|string',
            'notes' => 'nullable|array',
            'payment_type' => 'nullable',
            'charge_details' => 'nullable|string',
        ]);

        $instruction = PaymentInstruction::fromArray($validated);
        $xml = $this->xmlGenerator->generate($instruction);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
