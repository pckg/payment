<?php namespace Pckg\Payment\Handler;

class BankTransfer extends Upn
{

    protected $downloadView = 'Derive/Basket:payment/start_bank_transfer';

    protected $downloadFolder = 'bank-transfer';
    
}