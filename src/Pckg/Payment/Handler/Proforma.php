<?php namespace Pckg\Payment\Handler;

use Derive\Basket\Service\Pdf;

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
            'order' => $this->order,
        ]);
    }

    public function downloadFile()
    {
        return response()->download(path('private') . $this->downloadFolder . '/' . $this->paymentRecord->hash . '.pdf',
                                    strtoupper($this->downloadFolder) . ' payment.pdf');
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
        $this->generateDownload();

        return [
            'success'  => true,
            'redirect' => '/payment/' . $this->paymentRecord->hash . '/download-file',
        ];
    }

}