<?php

namespace SantanderPaymentSolutions\SantanderPayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class NewOrder implements ObserverInterface
{
    private $integrationHelper;
    private $transactionHelper;

    public function __construct(IntegrationHelper $integrationHelper, TransactionHelper $transactionHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $this->transactionHelper = $transactionHelper;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getId();

        $reference = $this->integrationHelper->getLastReference();
        if (!empty($reference)) {
            $transactions = $this->transactionHelper->getTransactions([['field' => 'reference', 'value' => $reference], ['field' => 'orderId', 'value' => 0]]);
            foreach ($transactions as $transaction) {
                $transaction->orderId = $orderId;
                $this->transactionHelper->saveTransaction($transaction);
            }
        }
        $this->integrationHelper->setLastReference('');
    }
}