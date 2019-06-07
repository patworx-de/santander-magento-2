<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\OrderHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class RefundCommand implements CommandInterface
{
    private $integrationHelper;
    private $transactionHelper;
    private $orderHelper;

    public function __construct(IntegrationHelper $integrationHelper, TransactionHelper $transactionHelper, OrderHelper $orderHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $this->transactionHelper = $transactionHelper;
        $this->orderHelper = $orderHelper;
    }

    public function execute(array $commandSubject)
    {
        $this->integrationHelper->log('success', __CLASS__ . '::' . __METHOD__ . '::' . __LINE__, 'execute');

        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDO */
        $paymentDO = $commandSubject['payment'];
        $amount = $commandSubject['amount'];
        $order = $paymentDO->getOrder();
        $orderId = $order->getId();
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $reservation = $this->transactionHelper->getTransaction('orderId', $orderId, 'reservation');

        if ($reservation->id) {
            $result = $this->transactionHelper->hybridRefund($reservation, $this->orderHelper->getBasketFromOrder($order, $amount), $amount);
            if ($result["isSuccess"]) {
                $payment->setTransactionId($reservation->uniqueId);
                $payment->setTransactionAdditionalInfo('raw_response', json_encode($result));
                $payment->addTransaction(Transaction::TYPE_REFUND);
                $payment->setAdditionalInformation('response', json_encode($result["response"]));
                $payment->save();
                return true;
            } else {
                throw new \Exception('Santander Error: ' . $result["responseJson"]);
            }
        } else {
            throw new \Exception('Santander Error: No Reservation');
        }
    }
}