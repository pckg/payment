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

        $this->moneta = new CMoneta();

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

        $nSkupnaCena = $this->order->getTotal();

        $user = $this->order->getOrder()->user;
        $sIme = $user->name;
        $sPriimek = $user->surname;
        $sDavcna = null;
        $sEmail = $user->email;
        $sUlica = $user->address;
        $sHisnast = null;
        $sPosta = null;//@$city['code'];
        $sKraj = null;//@$city['title'];

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
        $sXMLData = $sXMLData . ValuHelper::MakeOrderLine(
                $description,
                $nSkupnaCena,
                0.0,
                1,
                "kol",
                "rez"
            );

        // generiramo zaključek naročila
        $sXMLData = $sXMLData . ValuHelper::MakeOrderEnd();

        /**
         * Update data in database.
         */
        $confirmationId = $this->getPaymentRecord()->hash;
        $sProviderDataX = $sXMLData; // str_replace("'", "''", $sXMLData); // ???

        $this->paymentRecord->addLog('valu:purchasestatus', 'vobdelavi');
        $this->paymentRecord->addLog('valu:refreshcounter', 0);
        $this->paymentRecord->addLog('valu:providerdata', $sProviderDataX);

        // sestavimo url
        $url = $this->config['url'] . "?TARIFFICATIONID=" . $this->config['tarifficationId'] . "&ConfirmationID=" . $sConfirmationID;

        return [
            'success' => true,
            'redirect' => $url,
        ];
    }

    /**
     * Valu calls our endpoint to confirm the purchase.
     */
    public function getInfo()
    {
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
            $myMoneta = $this->moneta;
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

        die($sOutput);
    }

    /**
     * User is redirected back to our store, we need to display him the status.
     * Should we redirect him to /waiting page and there make some checks?
     *
     * @return string|void
     */
    public function getNotification()
    {
        ValuHelper::Functions_ResponseExpires();

        $sMyName = config('url');

        $sStatus = "";
        $sProviderData = "";

        $sStatus = "";
        $sData = "";

        // Branje parametra ConfirmationID
        $sConfirmationID = $this->paymentRecord->hash;

        // kreiranje CMoneta objekta
        $myMoneta = $this->moneta;

        $nRefreshCounter = $this->paymentRecord->getLog('valu:refreshcounter');
        $sPurchaseStatus = $this->paymentRecord->getLog('valu:purchasestatus');
        $sProviderData = $this->paymentRecord->getLog('valu:providerdata');
        $this->paymentRecord->updateLog('valu:refreshcounter', $nRefreshCounter + 1);

        if ($nRefreshCounter > 60) {
            response()->redirect($this->getErrorUrl());
        } else if ($sPurchaseStatus == "vobdelavi") {
            response()->redirect($this->getWaitingUrl());
        } else if ($sPurchaseStatus == "potrjeno") {
            $this->paymentRecord->updateLog('valu:purchasestatus', 'prikazano');
            response()->redirect($this->getSuccessUrl());
        } else if ($sPurchaseStatus == "zavrnjeno") {
            $sStatus = "Potrditvena stran je bila klicana s TARIFFICATIONERROR=1.";
        } else {
            $sStatus = "Napaka.";
        }

        $return = $sProviderData . '<br /><b>Status nakupa:</b> ' . $sStatus . '<br />' . $sData . '<br /><br /><br /><a href="' . $sMyName . '">Na domačo stran</a>';

        // HTML vsebina plačljive strani
        return $return;
    }

}