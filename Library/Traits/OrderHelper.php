<?php

namespace SantanderPaymentSolutions\SantanderPayments\Library\Traits;

use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BankAccount;

trait OrderHelper
{
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\TransactionHelperInterface
     */
    protected $transactionHelper;

    public function isAllowedToFinalize($reference)
    {
        if ($reservation = $this->transactionHelper->getByReference($reference, 'reservation', 'success')) {
            if (!($finalize = $this->transactionHelper->getByReference($reference, 'finalize', 'success'))) {
                if (!$this->isCompletelyReversed($reference)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $reference
     *
     * @return bool
     */
    public function isCompletelyReversed($reference)
    {
        return ($this->getOpenAmount($reference) <= 0);
    }

    /**
     * @param string $reference
     *
     * @return int|float
     */
    public function getOpenAmount($reference)
    {
        if (!$this->isCompleteSuccess($reference)) {
            if ($reservation = $this->transactionHelper->getByReference($reference, 'reservation', 'success')) {
                $amount = (float)$reservation->amount;
                foreach ($this->transactionHelper->getTransactions('reference', $reference, 'reversal') as $reversal) {
                    $amount -= (float)$reversal->amount;
                }

                return max(0, $amount);
            }
        }

        return 0;
    }

    /**
     * @param string $reference
     *
     * @return bool
     */
    public function isCompleteSuccess($reference)
    {
        if ($finalize = $this->transactionHelper->getByReference($reference, 'finalize', 'success')) {
            return true;
        }

        return false;
    }

    public function getReferenceFromOrderId($orderId)
    {
        if ($transaction = $this->transactionHelper->getTransaction('order_id', $orderId, null, null)) {
            return $transaction->orderId;
        }

        return null;
    }

    public function isFailure($reference)
    {
        if ($this->transactionHelper->getByReference($reference, 'finalize', 'error')) {
            return true;
        }
        if (!$this->transactionHelper->getByReference($reference, 'reservation', 'success')) {
            return true;
        }

        return false;
    }

    public function getBankAccount($responseArray)
    {
        $account = new BankAccount();
        if (!empty($responseArray["connector"]["account_iban"])) {
            $account->iban   = $responseArray["connector"]["account_iban"];
            $account->bic    = $responseArray["connector"]["account_bic"];
            $account->usage  = $responseArray["connector"]["account_usage"];
            $account->holder = $responseArray["connector"]["account_holder"];

            return $account;
        } else {
            return null;
        }
    }
}