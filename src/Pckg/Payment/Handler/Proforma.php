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
        return view('Pckg/Generic:single', [
            'content' => view('Derive/Basket:payment/start_upn', [
                'bills' => $this->order->getBills(),
            ]),
        ]);
    }

    protected function generateSepa()
    {
        $url = $this->getStartUrl();
        $outputDir = path('private') . 'sepa';
        $outputFile = 'sepa-' . $this->paymentRecord->getOrdersAttribute()->map('id')->implode('-') . '-' .
            date('YmdHis') . '.pdf';
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
        return [
            'success'  => true,
            'download' => $this->generateSepa(),
        ];
    }

}