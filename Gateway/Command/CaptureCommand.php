<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\OrderHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class CaptureCommand implements CommandInterface
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
        $order = $paymentDO->getOrder();
        $orderId = $order->getId();
        $amount = $commandSubject['amount'];
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $reservation = $this->transactionHelper->getTransaction('orderId', $orderId, 'reservation');

        if ($reservation->id) {
            $result = $this->transactionHelper->finalize($reservation, $this->orderHelper->getBasketFromOrder($order, $amount), $amount);
            if ($result->isSuccess) {
                $payment->setTransactionId($reservation->uniqueId);
                $payment->setTransactionAdditionalInfo('raw_response', json_encode($result->responseArray));
                $payment->addTransaction(Transaction::TYPE_CAPTURE);
                $payment->setAdditionalInformation('response', json_encode($result->responseArray));
                $payment->save();
                return true;
            } else {
                throw new \Exception('Santander Error: ' . json_encode($result->responseArray));
            }
        } else {
            throw new \Exception('Santander Error: No Reservation');
        }
    }
}