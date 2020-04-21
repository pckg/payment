<?php namespace Pckg\Payment\Handler;

use Carbon\Carbon;
use Derive\Orders\Record\OrdersBill;
use Pckg\Payment\Handler\Valu\CMoneta;
use Pckg\Payment\Handler\Valu\ValuHelper;

class Valu extends AbstractHandler
{

    /**
     * @var CMoneta
     */
    protected $moneta;

    public function initHandler()
    {
        $this->config = [
            'tarifficationId' => $this->environment->config('valu.tarifficationId'),
            'url' => $this->environment->config('valu.url'),
        ];

        return $this;
    }

    /**
     * User submits the intent, we redirect it to Valu.
     *
     * @return array|void
     */
    public function postStart()
    {
        ValuHelper::Functions_ResponseExpires();

        $nSkupnaCena = $this->getTotalToPay();

        $user = $this->order->getOrder()->user;
        $sIme = $user->name;
        $sPriimek = $user->surname;
        $sDavcna = null;
        $sEmail = $user->email;
        $sUlica = null;
        $sHisnast = null;
        $sPosta = null;
        $sKraj = null;

        // kreiramo xml
        $sXMLData = ValuHelper::MakeOrderHead(
            $sDavcna,
            $sIme,
            $sPriimek,
            "",
            $sUlica,
            $sHisnast,
            $sPosta,
            $sKraj,
            "",
            "",
            $sEmail,
            $nSkupnaCena
        );

        // dodamo artikel
        $description = $this->paymentRecord->getDescription();
        /*$sXMLData = $sXMLData . ValuHelper::MakeOrderLine(
                $description,
                $nSkupnaCena,
                0.0,
                1,
                "kol",
                "rez"
            );

        // generiramo zaključek naročila
        $sXMLData = $sXMLData . ValuHelper::MakeOrderEnd();*/

        $sXMLData = '<meta name="Price" content="' . $this->getTotalToPay() . '">
 <meta name="Quantity" content="1">
 <meta name="VATRate" content="">
 <meta name="Description" content="' . htmlentities($this->getDescription()) .'">
 <meta name="Currency" content="' . $this->getCurrency() . '">';

        /**
         * Update data in database.
         */
        $sProviderDataX = $sXMLData; // str_replace("'", "''", $sXMLData); // ???

        $this->paymentRecord->addLog('valu:purchasestatus', 'vobdelavi');
        $this->paymentRecord->addLog('valu:refreshcounter', 0);
        $this->paymentRecord->addLog('valu:providerdata', $sProviderDataX);

        // sestavimo url
        $url = $this->config['url'] . "?TARIFFICATIONID=" . $this->config['tarifficationId'] . "&ConfirmationID=" . $this->getPaymentRecord()->hash;

        return [
            'success' => true,
            'redirect' => $url,
        ];
    }

    /**
     * User is redirected back to our store, we need to display him the status.
     * Should we redirect him to /waiting page and there make some checks?
     *
     * a.k.a nakup.php
     *
     * @return string|void
     */
    public function getInfo()
    {
        $this->paymentRecord->addLog('getInfo', request()->get()->all());
        ValuHelper::Functions_ResponseExpires();

        $sMyName = config('url');

        $nRefreshCounter = $this->paymentRecord->getLog('valu:refreshcounter');
        $sPurchaseStatus = $this->paymentRecord->getLog('valu:purchasestatus');
        $sProviderData = $this->paymentRecord->getLog('valu:providerdata');

        $this->paymentRecord->updateLog('valu:refreshcounter', $nRefreshCounter + 1);

        $nextUrl = request()->getUrl();
        if ($nRefreshCounter > 60) {
            $nextUrl = $this->getErrorUrl();
            // response()->redirect($this->getErrorUrl());
        } else if ($sPurchaseStatus == "vobdelavi") {
            // ok
            // response()->redirect($this->getWaitingUrl()); //
            $sStatus = 'čakam na potrditev...';
        } else if ($sPurchaseStatus == "potrjeno") {
            $this->paymentRecord->updateLog('valu:purchasestatus', 'prikazano');
            $nextUrl = $this->getSuccessUrl();
        } else if ($sPurchaseStatus == "zavrnjeno") {
            $sStatus = "Potrditvena stran je bila klicana s TARIFFICATIONERROR=1.";
            $nextUrl = $this->getErrorUrl();
        } else {
            $nextUrl = $this->getErrorUrl();
            $sStatus = "Napaka.";
        }

        $return = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>' . $sProviderData . '<meta http-equiv="refresh" content="3; url=' . $nextUrl . '"></head><body><b>Status nakupa:</b> ' . $sStatus . '<br /><br /><a href="' . $this->getCheckUrl() . '">Preveri nakup</a></body></html>';


        $this->paymentRecord->addLog('getInfo:response', $return);
        die($return);
    }

    /**
     * Valu calls our endpoint to confirm the purchase, async.
     * a.k.a. potrditev.php
     */
    public function getNotification()
    {
        $this->paymentRecord->addLog('getNotification', request()->get()->all());
        ValuHelper::Functions_ResponseExpires();

        // branje vhodnih parametrov
        $sConfirmationID = ValuHelper::Functions_RequestString("ConfirmationID", 32);
        $sConfirmationSignature = ValuHelper::Functions_RequestString("ConfirmationSignature", 250);
        $nTarifficationError = ValuHelper::Functions_RequestNumber("TARIFFICATIONERROR", 0, 1, 1);
        $sConfirmationIDStatus = ValuHelper::Functions_RequestString("ConfirmationIDStatus", 32);
        $sIP = ValuHelper::Functions_GetServerVariable('REMOTE_ADDR');
        $sOutput = "<error>1</error>";

        $this->paymentRecord->addLog('check', ['confirmationSignature' => $sConfirmationSignature, 'tarifficationError' => $nTarifficationError, 'confirmationIdStatus' => $sConfirmationIDStatus]);

        /**
         * @T00D00 - this can be implemented after we HTTPS-offload traffic on proxy.
         */
        // preverjanje IP Monete
        if (true || ($sIP == "213.229.249.103") || ($sIP == "213.229.249.104") || ($sIP == "213.229.249.117")) {
            // kreiranje CMoneta objekta
            $purchaseStatus = $this->paymentRecord->getLog('valu:purchasestatus');

            // zahtevek za status nakupa?
            if ($sConfirmationIDStatus != "") {
                $sOutput = "<status>" . $content . "</status>";
            } else if ($purchaseStatus == "vobdelavi") {
                if ($nTarifficationError == 0) {
                    $sOutput = "<error>0</error>"; // tell moneta to make a payment

                    $this->approvePayment('Moneta #' . $sConfirmationID, null, $sConfirmationID);

                    $this->paymentRecord->updateLog('valu:purchasestatus', 'potrjeno');

                } else {
                    $this->errorPayment();

                    $this->paymentRecord->updateLog('valu:purchasestatus', 'zavrnjeno');
                }
            }
        }
        $this->paymentRecord->addLog('getNotification:output', $sOutput);

        die($sOutput);
    }

}