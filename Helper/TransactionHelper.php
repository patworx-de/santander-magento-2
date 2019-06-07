<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\TransactionHelperInterface;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction as TransactionStruct;
use SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction;
use SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\CollectionFactory;
use SantanderPaymentSolutions\SantanderPayments\Model\Transaction as TransactionModel;
use SantanderPaymentSolutions\SantanderPayments\Model\TransactionFactory;

class TransactionHelper implements TransactionHelperInterface
{
    private $transactionFactory;
    private $transactionCollectionFactory;
    protected $checkoutHelper;
    protected $configHelper;
    protected $integrationHelper;
    private $transactionResource;
    protected $logger;

    use \SantanderPaymentSolutions\SantanderPayments\Library\Traits\TransactionHelper;

    public function __construct(CheckoutHelper $checkoutHelper, TransactionFactory $transactionFactory, CollectionFactory $transactionCollectionFactory, ConfigHelper $configHelper, IntegrationHelper $integrationHelper, Transaction $transactionResource, LoggerHelper $logger)
    {
        $this->transactionFactory           = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->checkoutHelper               = $checkoutHelper;
        $this->configHelper                 = $configHelper;
        $this->integrationHelper            = $integrationHelper;
        $this->transactionResource          = $transactionResource;
        $this->logger                       = $logger;
    }

    /**
     * @param string $idField
     * @param int|string $idValue
     * @param string $type
     * @param string $status
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction[]|\SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\Collection
     */
    public function getTransactions($idField, $idValue, $type, $status = 'success')
    {
        /** @var \SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\Collection $transactionCollection */
        $transactionCollection = $this->transactionCollectionFactory->create();
        $idField               = lcfirst(implode('', array_map('ucfirst', explode('_', $idField))));
        $transactionCollection->addFieldToFilter($idField, ['like' => $idValue]);

        if ($type !== null) {
            $transactionCollection->addFieldToFilter('type', ['like' => $type]);
        }
        if ($status !== null) {
            $transactionCollection->addFieldToFilter('status', ['like' => $status]);
        }

        $return = [];
        foreach ($transactionCollection->getItems() as $transactionModel) {
            $return[] = $this->convertTransactionModelToStruct($transactionModel);
        }

        return $return;

    }

    /**
     * @param \SantanderPaymentSolutions\SantanderPayments\Model\Transaction $transactionModel
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction
     */
    private function convertTransactionModelToStruct(TransactionModel $transactionModel)
    {

        /** @var TransactionModel $transactionModel */
        $transaction = new TransactionStruct();
        foreach ($transactionModel->toArray() as $field => $value) {

            if (property_exists($transaction, $field)) {
                $transaction->{$field} = $value;
            }
            $transaction->model = $transactionModel;
        }

        return $transaction;
    }

    /**
     * @return TransactionModel
     */
    public function createTransaction()
    {
        return $this->transactionFactory->create();
    }

    public function saveTransaction(TransactionStruct $transaction)
    {
        $model = $this->convertTransactionStructToModel($transaction);
        $this->transactionResource->save($model);
    }

    private function convertTransactionStructToModel(TransactionStruct $transaction)
    {
        if (!empty($transaction->model)) {
            $model = $transaction->model;
        } else {
            $model = $this->transactionFactory->create();
        }

        foreach ($transaction as $field => $value) {
            if (property_exists($model, $field)) {
                $model->{$field} = $value;
            }
        }

        return $model;
    }

}


