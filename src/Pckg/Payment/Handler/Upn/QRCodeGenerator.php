<?php

namespace Pckg\Payment\Handler\Upn;

use Exception;

class QRCodeGenerator
{
    private string $payer_name;
    private string $payer_address;
    private string $payer_post;

    private float $amount;

    private string $receiver_name;
    private string $receiver_address;
    private string $receiver_post;
    private string $receiver_iban;
    private string $reference;

    private string $code;
    private string $purpose = '';
    private \DateTime $due_date;

    public function getQRCode()
    {
        $qrEncoder = \QR_Code\Encoder\Encoder::factory(QR_ECLEVEL_M, 3, 0);
        $qrEncoder->version = 15;

        $tab = $qrEncoder->encode($this->getQRCodeText());

        return \QR_Code\Encoder\Image::image(
            $tab,
            min(max(1, 3), (int)(QR_PNG_MAXIMUM_SIZE / (count($tab)))),
            0
        );
    }

    public function getQRCodeText(): string
    {
        $text = [
            'UPNQR',
            '',
            '    ',
            '',
            '',
            $this->payer_name ?? '',
            $this->payer_address ?? '',
            $this->payer_post ?? '',
            sprintf('%011d', $this->amount * 100) ?? '',
            '',
            '',
            $this->code ?? '',
            $this->purpose ?? '',
            $this->due_date->format('d.m.Y') ?? '',
            $this->receiver_iban ?? '',
            $this->reference ?? '',
            $this->receiver_name ?? '',
            $this->receiver_address ?? '',
            $this->receiver_post ?? '',
        ];

        array_walk($text, fn(&$i) => $i = trim($i));
        $text = implode("\n", $text) . "\n";
        $text .= mb_strlen($text) . "\n"; // append control code

        return $text;
    }

    public function getFormatedPrice(): string
    {
        return number_format($this->amount, 2, ',', '.');
    }

    public function getFormatedReceiverIban(): string
    {
        return wordwrap($this->receiver_iban, 4, ' ', true);
    }

    public function getFormatedReference(): string
    {
        return $this->getReferencePrefix() . ' ' . $this->getReferenceSuffix();
    }

    public function getReferencePrefix(): string
    {
        return substr($this->reference, 0, 4);
    }

    public function getReferenceSuffix(): string
    {
        return substr($this->reference, 4);
    }

    public function getPayerName(): string
    {
        return $this->payer_name;
    }

    public function setPayerName(string $payer_name): self
    {
        $this->payer_name = mb_substr($payer_name, 0, 33);

        return $this;
    }

    public function getPayerAddress(): string
    {
        return $this->payer_address;
    }

    public function setPayerAddress(string $payer_address): self
    {
        $this->payer_address = mb_substr($payer_address, 0, 33);

        return $this;
    }

    public function getPayerPost(): string
    {
        return $this->payer_post;
    }

    public function setPayerPost(string $payer_post): self
    {
        $this->payer_post = mb_substr($payer_post, 0, 33);

        return $this;
    }

    public function getReceiverName(): string
    {
        return $this->receiver_name;
    }

    public function setReceiverName(string $receiver_name): self
    {
        $this->receiver_name = mb_substr($receiver_name, 0, 33);

        return $this;
    }

    public function getReceiverAddress(): string
    {
        return $this->receiver_address;
    }

    public function setReceiverAddress(string $receiver_address): self
    {
        $this->receiver_address = mb_substr($receiver_address, 0, 33);

        return $this;
    }

    public function getReceiverPost(): string
    {
        return $this->receiver_post;
    }

    public function setReceiverPost(string $receiver_post): self
    {
        $this->receiver_post = mb_substr($receiver_post, 0, 33);

        return $this;
    }

    public function getReceiverIban(): string
    {
        return $this->receiver_iban;
    }

    public function setReceiverIban(string $receiver_iban): self
    {
        $iban = str_replace(' ', '', $receiver_iban);

        if (strlen($iban) !== 19) {
            throw new \Exception('IBAN must be 19 characters long;');
        }

        $this->receiver_iban = $iban;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $reference = str_replace(' ', '', $reference);

        if (strlen($reference) > 26) {
            throw new \Exception('Max length for reference is 26 char');
        }

        $this->reference = $reference;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        if (strlen($code) !== 4) {
            throw new \Exception('CODE must be 4 charatcers');
        }
        $this->code = strtoupper($code);

        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): self
    {
        $this->purpose = mb_substr($purpose, 0, 42);

        return $this;
    }

    public function getDueDate(): \DateTime
    {
        return $this->due_date;
    }

    public function setDueDate(\DateTime $due_date): self
    {
        $this->due_date = $due_date;

        return $this;
    }
}
