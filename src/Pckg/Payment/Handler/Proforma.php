<?php namespace Pckg\Payment\Handler;

use Derive\Basket\Service\Pdf;

class Proforma extends AbstractHandler implements Handler
{

    public function initHandler()
    {
        $this->config = [
            'url_waiting' => $this->environment->config('proforma.url_waiting'),
        ];
    }

    public function getDownload()
    {
        assetManager()->addAssets(path('apps') . 'derive/public/less/pages/upnsepa.less', 'blank');

        return view('Derive/Basket:payment/start_upn', [
                'bills' => $this->order->getBills(),
            ]);
    }

    protected function generateSepa()
    {
        $url = $this->getDownloadUrl();
        $outputDir = path('private') . 'sepa/';
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
        $this->generateSepa();

        return [
            'success'  => true,
            'redirect' => '/payment/' . $this->paymentRecord->hash . '/download-file',
        ];
    }

}