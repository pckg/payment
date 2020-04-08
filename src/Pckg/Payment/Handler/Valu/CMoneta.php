<?php namespace Pckg\Payment\Handler\Valu;

use Carbon\Carbon;

class CMoneta
{

    var $m_nRefreshCounter, $m_sPurchaseStatus, $m_sProviderData;

    public function SetPurchaseStatus($status, $confirmationId)
    {
        $moneta = (new Moneta())->where('confirmationid', $confirmationId)->oneOrFail();

        $moneta->purchasestatus = $status;
        $moneta->save();
    }

    public function ConfirmPurchase($status, $confirmationId, $confirmationSignature, $tarifficationError)
    {
        $moneta = (new Moneta())->where('confirmationid', $confirmationId)->oneOrFail();

        $moneta->set(
            [
                'purchasestatus'        => $status,
                'confirmationsignature' => $confirmationSignature,
                'tarifficationerror'    => $tarifficationError,
                'confirmdate'           => Carbon::now(),
            ]
        )->save();
    }

    public function AddRefreshCounter($confirmationId)
    {
        $moneta = (new Moneta())->where('confirmationid', $confirmationId)->oneOrFail();
        $moneta->refreshcounter = $moneta->refreshcounter + 1;
        $moneta->save();
    }

    public function FindConfirmationID($confirmationId)
    {
        $moneta = (new Moneta())->where('confirmationid', $confirmationId)->oneOrFail();

        $this->m_nRefreshCounter = $moneta->refreshcounter;
        $this->m_sPurchaseStatus = $moneta->purchasestatus;
        $this->m_sProviderData = $moneta->providerdata;

        return true;
    }

    public function Get_RefreshCounter()
    {
        return intval($this->m_nRefreshCounter);
    }

    public function Get_PurchaseStatus()
    {
        return $this->m_sPurchaseStatus;
    }

    public function Get_ProviderData()
    {
        return $this->m_sProviderData;
    }

    public function MakeUniqueConfirmationID()
    {
        return "" . gmdate("dmYHis");
    }

    public function Close()
    {
        return true;
    }
}
