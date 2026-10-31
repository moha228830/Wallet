<?php

namespace App\Services\Payment;

use App\Services\Payment\DTO\PaymentInstruction;
use DOMDocument;
use DOMElement;

class PaymentXmlGenerator
{
    public function generate(PaymentInstruction $instruction): string
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;

        $root = $doc->createElement('PaymentRequestMessage');
        $doc->appendChild($root);

        $transferInfo = $doc->createElement('TransferInfo');
        $this->appendElement($doc, $transferInfo, 'Reference', $instruction->reference);
        $this->appendElement($doc, $transferInfo, 'Date', $instruction->date);
        $this->appendElement($doc, $transferInfo, 'Amount', number_format((float) $instruction->amount, 2, '.', ''));
        $this->appendElement($doc, $transferInfo, 'Currency', $instruction->currency);
        $root->appendChild($transferInfo);

        $senderInfo = $doc->createElement('SenderInfo');
        $this->appendElement($doc, $senderInfo, 'AccountNumber', $instruction->senderAccountNumber);
        $root->appendChild($senderInfo);

        $receiverInfo = $doc->createElement('ReceiverInfo');
        $this->appendElement($doc, $receiverInfo, 'BankCode', $instruction->receiverBankCode);
        $this->appendElement($doc, $receiverInfo, 'AccountNumber', $instruction->receiverAccountNumber);
        $this->appendElement($doc, $receiverInfo, 'BeneficiaryName', $instruction->beneficiaryName);
        $root->appendChild($receiverInfo);

        $cleanNotes = array_values(array_filter(array_map('trim', $instruction->notes)));
        if (! empty($cleanNotes)) {
            $notesElement = $doc->createElement('Notes');
            foreach ($cleanNotes as $note) {
                $this->appendElement($doc, $notesElement, 'Note', $note);
            }
            $root->appendChild($notesElement);
        }

        if ($instruction->paymentType !== null && (string) $instruction->paymentType !== '99') {
            $this->appendElement($doc, $root, 'PaymentType', (string) $instruction->paymentType);
        }

        if ($instruction->chargeDetails !== null && strtoupper(trim($instruction->chargeDetails)) !== 'SHA') {
            $this->appendElement($doc, $root, 'ChargeDetails', trim($instruction->chargeDetails));
        }

        return (string) $doc->saveXML();
    }

    protected function appendElement(DOMDocument $doc, DOMElement $parent, string $name, string $value): DOMElement
    {
        $element = $doc->createElement($name);
        $element->appendChild($doc->createTextNode($value));
        $parent->appendChild($element);

        return $element;
    }
}
