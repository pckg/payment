<?php namespace Pckg\Payment\Handler;

use Derive\Internal\Konto\Service\Konto;
use Derive\Orders\Record\User;

class CommsWallet extends AbstractHandler implements Handler
{

    public function postStart()
    {
        /**
         * We simply have to execute the payment.
         */
        $userId = auth()->user('id');
        if (!$userId) {
            $this->errorPayment('Login required');
            return [
                'success' => true,
                'modal' => 'error',
                'message' => 'Login required',
            ];
        }

        /**
         * Set current user.
         */
        $kontoService = new Konto();
        $kontoService->setUser(User::getOrFail($userId));

        /**
         * Validate payment.
         */
        $payment = $this->getTotalToPay();
        $balance = $kontoService->getBalance();
        if ($balance < $payment || !($balance >= $payment)) {
            /**
             * We need to split the instalment?
             */
            $this->errorPayment('Insufficient balance');
            
            return [
                'success' => true,
                'modal' => 'error',
                'message' => 'Partial payments are not supported, insufficient balance.',
            ];
        }

        /**
         * Try to deduct.
         */
        $success = $kontoService->withdraw($payment, 'payment', $e);
        if ($success && !$e) {
            $this->approvePayment("MyWallet #" . $success, null, $success);

            return [
                'success' => true,
                'modal' => 'success',
            ];
        }

        $this->errorPayment(exception($e));

        return [
            'success' => true,
            'modal' => 'error',
            'message' => 'Error making payment',
        ];
    }

}