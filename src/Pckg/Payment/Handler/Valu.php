<?php namespace Pckg\Payment\Handler;

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
            'tarifficationid' => $this->environment->config('valu.tarifficationId'),
            'url'             => $this->environment->config('valu.url'),
        ];

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

    public function startPartial()
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
        $sXMLData = $sXMLData . ValuHelper::MakeOrderLine(
                __('order_payment') . " #" . $this->order->getId() . ' (' . $this->order->getNum(
                ) . ' - ' . $this->order->getBills()->map('id')->implode(',') . ')',
                $nSkupnaCena,
                0.0,
                1,
                "kol",
                "rez"
            );

        // generiramo zaključek naročila
        $sXMLData = $sXMLData . ValuHelper::MakeOrderEnd();

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

        return [
            'success' => true,
            'redirect' => $url,
        ];
    }

    public function check()
    {
        ValuHelper::Functions_ResponseExpires();

        // branje vhodnih parametrov
        $sConfirmationID = ValuHelper::Functions_RequestString("ConfirmationID", 32);
        $sConfirmationSignature = ValuHelper::Functions_RequestString("ConfirmationSignature", 250);
        $nTarifficationError = ValuHelper::Functions_RequestNumber("TARIFFICATIONERROR", 0, 1, 1);
        $sConfirmationIDStatus = ValuHelper::Functions_RequestString("ConfirmationIDStatus", 32);
        $sIP = ValuHelper::Functions_GetServerVariable('REMOTE_ADDR');
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
        ValuHelper::Functions_ResponseExpires();

        //$sMyName = 'http://' . Functions_GetServerVariable('HTTP_HOST') . Functions_GetServerVariable('SCRIPT_NAME');
        $sMyName = "https://gremonaparty.com";

        $sConfirmationID = "";
        $sStatus = "";
        $sProviderData = "";

        $sStatus = "";
        $sData = "";

        // Branje parametra ConfirmationID
        $sConfirmationID = ValuHelper::Functions_RequestString("ConfirmationID", 32);
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