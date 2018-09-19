<?php namespace Pckg\Payment\Handler;

use CMoneta;
use Derive\Orders\Record\OrdersBill;

class Moneta extends AbstractHandler
{

    /**
     * @var CMoneta
     */
    protected $moneta;

    public function initHandler()
    {
        $this->config = [
            'tarifficationid' => $this->environment->config('moneta.tarifficationId'),
            'url'             => $this->environment->config('moneta.url'),
        ];

        include path('src') . "moneta" . path('ds') . "moneta.php";
        include path('src') . "moneta" . path('ds') . "functions.php";
        include path('src') . "moneta" . path('ds') . "xmlfunctions.php";

        $this->moneta = new CMoneta();

        return $this;
    }

    public function getTotal()
    {
        return $this->order->getTotal();
    }

    public function getTotalToPay()
    {
        return $this->order->getTotalToPay();
    }

    public function getPublicKey()
    {
        return $this->config['public_key'];
    }

    public function startPartial()
    {
        Functions_ResponseExpires();

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
        $sXMLData = MakeOrderHead(
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
        $sXMLData = $sXMLData . MakeOrderLine(
                __('order_payment') . " #" . $this->order->getId() . ' (' . $this->order->getNum(
                ) . ' - ' . $this->order->getBills()->map('id')->implode(',') . ')',
                $nSkupnaCena,
                0.0,
                1,
                "kol",
                "rez"
            );

        // generiramo zaključek naročila
        $sXMLData = $sXMLData . MakeOrderEnd();

        // kreiramo CMoneta objekt
        $myMoneta = $this->moneta;

        // dodaj nakup v DB
        $sConfirmationID = $myMoneta->AddMonetaPurchase(
            $sXMLData,
            [
                "user_id"  => $user->id,
                "order_id" => $this->order->getId(),
                "data"     => json_encode(
                    [
                        'billIds' => $this->order->getBills()->map('id'),
                    ]
                ),
            ]
        );

        // sestavimo url
        $url = $this->config['url'] . "?TARIFFICATIONID=" . $this->config['tarifficationid'] . "&ConfirmationID=" . $sConfirmationID;

        response()->redirect($url);

        return '<meta http-equiv="refresh" content="3; url=' . $url . '" /><h4>Poteka preusmeritev na Moneto ...</h4><a href="' . $url . '">Naročilo ' . $url . '</a>';

    }

    public function check()
    {
        Functions_ResponseExpires();

        // branje vhodnih parametrov
        $sConfirmationID = Functions_RequestString("ConfirmationID", 32);
        $sConfirmationSignature = Functions_RequestString("ConfirmationSignature", 250);
        $nTarifficationError = Functions_RequestNumber("TARIFFICATIONERROR", 0, 1, 1);
        $sConfirmationIDStatus = Functions_RequestString("ConfirmationIDStatus", 32);
        $sIP = Functions_GetServerVariable('REMOTE_ADDR');
        $sOutput = "<error>1</error>";

        // preverjanje IP Monete
        if (($sIP == "213.229.249.103") || ($sIP == "213.229.249.104") || ($sIP == "213.229.249.117")) {
            // kreiranje CMoneta objekta
            $myMoneta = $this->moneta;

            // zahtevek za status nakupa?
            if ($sConfirmationIDStatus != "") {
                if ($myMoneta->FindConfirmationID($sConfirmationIDStatus)) {
                    $sOutput = "<status>" . $myMoneta->Get_PurchaseStatus() . "</status>";
                }
            } else {
                // Iskanje ConfirmationID nakupa in potrjevanje nakupa
                if ($myMoneta->FindConfirmationID($sConfirmationID)) {
                    $sPurchaseStatus = $myMoneta->Get_PurchaseStatus();

                    if ($sPurchaseStatus == "vobdelavi") {
                        if ($nTarifficationError == 0) {
                            $myMoneta->ConfirmPurchase(
                                "potrjeno",
                                $sConfirmationID,
                                $sConfirmationSignature,
                                $nTarifficationError
                            );
                            $sOutput = "<error>0</error>";

                            $this->approvePayment('Moneta #' . $sConfirmationID, null, $sConfirmationID);

                        } else {
                            $this->errorPayment();
                            $myMoneta->ConfirmPurchase(
                                "zavrnjeno",
                                $sConfirmationID,
                                $sConfirmationSignature,
                                $nTarifficationError
                            );
                        }
                    }
                }
            }
        }

        error_log("=== Moneta: " . nl2br($sOutput));

        die($sOutput);
    }

    public function waiting()
    {
        Functions_ResponseExpires();

        //$sMyName = 'http://' . Functions_GetServerVariable('HTTP_HOST') . Functions_GetServerVariable('SCRIPT_NAME');
        $sMyName = "https://gremonaparty.com";

        $sConfirmationID = "";
        $sStatus = "";
        $sProviderData = "";

        $sStatus = "";
        $sData = "";

        // Branje parametra ConfirmationID
        $sConfirmationID = Functions_RequestString("ConfirmationID", 32);
        //$sConfirmationID = "09082013100741";

        // kreiranje CMoneta objekta
        $myMoneta = $this->moneta;

        // Iskanje ConfirmationID nakupa
        if (!$myMoneta->FindConfirmationID($sConfirmationID)) {
            $sStatus = "ConfirmationID ne obstaja.";
        } else {
            $nRefreshCounter = $myMoneta->Get_RefreshCounter();
            $sPurchaseStatus = $myMoneta->Get_PurchaseStatus();
            $sProviderData = $myMoneta->Get_ProviderData();

            if ($nRefreshCounter > 60) {
                $sStatus = "Potrditev ni uspela.";
            } else if ($sPurchaseStatus == "vobdelavi") {
                $sStatus = "čakam na potrditev...";
            } else if ($sPurchaseStatus == "zavrnjeno") {
                $sStatus = "Potrditvena stran je bila klicana s TARIFFICATIONERROR=1.";
            } else if ($sPurchaseStatus == "potrjeno") {
                $sStatus = "Potrjevanje uspešno.";
                $sData = "<h1>Zahvaljujemo se vam za nakup.</h1>";

                $myMoneta->SetPurchaseStatus("prikazano", $sConfirmationID);
            } else {
                $sStatus = "Napaka.";
            }

            // povečaj števec osvežitev
            $myMoneta->AddRefreshCounter($sConfirmationID);

            if ($sPurchaseStatus == "potrjeno") {
                response()->redirect(
                    url('derive.payment.success', ['handler' => 'moneta', 'order' => $this->order->getOrder()])
                );
            }
        }

        $return = ($sStatus == "čakam na potrditev..."
                ? ('<meta http-equiv="refresh" content="3; url=' . $sMyName . $_SERVER["REQUEST_URI"] . '" />')
                : null) .
                  $sProviderData . '<br /><b>Status nakupa:</b> ' . $sStatus . '<br />' .
                  $sData . '<br /><br /><br /><a href="index.php">Nazaj</a>';

        error_log("=== Moneta: " . nl2br($return));

        // HTML vsebina plačljive strani
        return $return;
    }

    protected function makeTransaction($paymentId)
    {
    }

    protected function handleTransactionResponse($response)
    {
        if ($response->getStatus() == 'closed') {
            $this->order->setPaid();
        }
    }

    public function getStartUrl()
    {
        return $this->environment->url(
            'derive.payment.start',
            ['handler' => 'moneta', 'order' => $this->order->getOrder()]
        );
    }

}