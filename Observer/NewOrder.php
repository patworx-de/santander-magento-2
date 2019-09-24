<?php

namespace SantanderPaymentSolutions\SantanderPayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
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
        /** @var \Magento\Sales\Model\Order $order */
        $order   = $observer->getEvent()->getOrder();
        $orderId = $order->getId();

        $reference = $this->integrationHelper->getLastReference();
        if (!empty($reference)) {
            $transactions = $this->transactionHelper->getTransactions('reference', $reference, null, null);
            foreach ($transactions as $transaction) {
                if(empty($transaction->orderId)) {
                    $transaction->orderId = $orderId;
                    $this->transactionHelper->saveTransaction($transaction);
                }
            }
        }

        if ($reservation = $this->transactionHelper->getByReference($reference, 'reservation')) {
            /** @var \Magento\Sales\Model\Order\Payment $payment */
            $payment = $order->getPayment();
            $payment->setTransactionId($reservation->uniqueId);
            $payment->setIsTransactionClosed(0);
            $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
            $payment->save();
            $transaction->save();
        }
        $this->integrationHelper->setLastReference('');
    }
}