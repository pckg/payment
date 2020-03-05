<?php namespace Pckg\Payment\Handler;

use Pckg\Manager\Upload;

class BankTransfer extends Upn
{

    protected $downloadView = 'Derive/Basket:payment/start_bank_transfer';

    protected $downloadFolder = 'bank-transfer';

    public function initPayment()
    {
        return [
            'iban'     => $this->environment->config($this->downloadFolder . '.iban'),
            'swiftbic' => $this->environment->config($this->downloadFolder . '.swiftbic'),
            'id'       => $this->paymentRecord->id,
            'hash'     => $this->paymentRecord->hash,
        ];
    }

    public function postUploadFile()
    {
        $upload = new Upload();
        if (($message = $upload->validateUpload()) !== true) {
            throw new \Exception('Invalid upload: ' . $message);
        }
        $dir = path('private') . 'bank-transfer-proof/';
        $finalName = $upload->save($dir);

        $this->paymentRecord->addLog('proof', ['file' => 'bank-transfer-proof/' . $finalName]);

        return [
            'success'  => true,
            'uploaded' => $finalName,
        ];
    }

}