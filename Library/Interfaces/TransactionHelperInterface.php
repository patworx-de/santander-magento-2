<?php

namespace SantanderPaymentSolutions\SantanderPayments\Library\Interfaces;

use \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction;

interface TransactionHelperInterface
{

    /**
     * @param string $method
     * @param null|string $birthday
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     */
    public function initialize($method, $birthday = null);

    /**
     * @param string $method
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     */
    public function authorize($method);

    /**
     * @param \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction $reservation
     * @param string $basketId
     * @param float $amount
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     */
    public function finalize(Transaction $reservation, $basketId, $amount);

    /**
     * @param \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction $reservation
     * @param float $amount
     * @param string $basketId
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     */
    public function reversal(Transaction $reservation, $amount, $basketId);

    /**
     * @param \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction $initialize2Transaction
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     */
    public function authorizeOnRegistration(Transaction $initialize2Transaction);

    /**
     * @param string $idField
     * @param string|int $idValue
     * @param string $type
     * @param string $status
     *
     * @return Transaction[]
     */
    public function getTransactions($idField, $idValue, $type, $status = 'success');

    /**
     * @param $idField
     * @param $idValue
     * @param string $type
     * @param string $status
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction|null
     */
    public function getTransaction($idField, $idValue, $type, $status);

    /**
     * @param string $reference
     * @param string $type
     * @param string $status
     *
     * @return null|\SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction
     */
    public function getByReference($reference, $type, $status);

    /**
     * @param $uniqueId
     * @param string $type
     * @param null $status
     *
     * @return null|\SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction
     */
    public function getByUniqueId($uniqueId, $type, $status);
}