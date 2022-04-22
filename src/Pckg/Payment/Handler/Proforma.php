<?php

namespace Pckg\Payment\Handler;

use Derive\Basket\Service\Pdf;
use Derive\Utils\Service\QR;
use Pckg\Payment\Handler\Upn\QRCodeGenerator;

class Proforma extends AbstractHandler implements Handler
{
    protected $downloadView = 'Derive/Basket:payment/start_upn';
    protected $downloadFolder = 'upn';
    public function initHandler()
    {
        $this->config = [
            'url_waiting' => $this->environment->config('proforma.url_waiting'),
        ];
    }

    public function getDownload()
    {
        assetManager()->addAssets(path('apps') . 'derive/public/less/pages/upnsepa.less', 'blank');
        return view($this->downloadView, [
            'bills' => $this->order->getBills(),
            'order' => $this->order->getBills()->first()->order,
            'payment' => $this->paymentRecord,
            'iban' => $this->environment->config($this->downloadFolder . '.iban'),
            'swiftbic' => $this->environment->config($this->downloadFolder . '.swiftbic'),
        ]);
    }

    public function downloadFile()
    {
        $original = path('private') . $this->downloadFolder . '/' . $this->paymentRecord->hash . '.pdf';
        return response()->download($original, strtoupper($this->downloadFolder) . ' payment.pdf');
    }

    protected function generateDownload()
    {
        $url = $this->getDownloadUrl();
        $outputDir = path('private') . $this->downloadFolder . '/';
        $outputFile = $this->paymentRecord->hash . '.pdf';
        $pdf = Pdf::make($url, $outputDir, $outputFile);
        return $outputFile;
    }

    /**
     * Triggered when user submitts "Pay now" button in payment popup.
     *
     * @return array|void
     */
    public function postStart()
    {
        $download = !post('nodownload');
        if (!$download) {
            $this->waitPayment('Bank Transfer #' . $this->paymentRecord->id, null, $this->paymentRecord->id);
            return [
                'success' => true,
                'modal' => 'success',
            ];
        }

        $this->generateDownload();
        return [
            'success' => true,
            'redirect' => '/payment/' . $this->paymentRecord->hash . '/download-file',
        ];
    }

    public function getQrAction()
    {
        $qrGenerator = new QRCodeGenerator();
        $company = $this->paymentRecord->getOrdersAttribute()[0]->company;
        $qrGenerator->setAmount($this->paymentRecord->price);
        $qrGenerator->setDueDate(new \DateTime($this->paymentRecord->getBills()[0]->dt_valid));
        $qrGenerator->setPayerAddress('');
        $qrGenerator->setPayerName('');
        $qrGenerator->setPayerPost('');
        $qrGenerator->setCode('COST');
        $qrGenerator->setPurpose($this->paymentRecord->id);
        $qrGenerator->setReceiverName($company->short_name);
        $qrGenerator->setReceiverIban(str_replace(' ', '', config('pckg.payment.provider.bank-transfer.iban', null)));
        $qrGenerator->setReceiverAddress($company->address_line1);
        $qrGenerator->setReceiverPost(explode(' ', $company->address_line2)[0]);
        $qrGenerator->setReference('00-' . str_pad($this->paymentRecord->id, 8, '0', STR_PAD_LEFT));
        $path = path('private') . 'qr-payment/';
        $file = $this->paymentRecord->id . '.png';
        $qr = QR::make($path, $file, $qrGenerator->getQRCodeText(), function ($options) {

            $options['version'] = 15;
            return $options;
        });
        response()->printFile($path . $file, $file);
    }
}
