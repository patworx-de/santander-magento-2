<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class HireCaptureCommand implements CommandInterface
{
    private $integrationHelper;
    private $transactionHelper;

    public function __construct(IntegrationHelper $integrationHelper, TransactionHelper $transactionHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $this->transactionHelper = $transactionHelper;
    }

    public function execute(array $commandSubject)
    {
        $this->integrationHelper->log('success', __CLASS__ . '::' . __METHOD__ . '::' . __LINE__, 'execute');

        /** @var \Magento\Payment\Gateway\Data\PaymentDataObject $paymentDO */
        $paymentDO = $commandSubject['payment'];
        $order = $paymentDO->getOrder();
        $orderId = $order->getId();
        //$amount = $commandSubject["amount"];
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $reservation = $this->transactionHelper->getTransaction([
            ['field' => 'orderId', 'value' => $orderId],
            ['field' => 'type', 'value' => 'reservation'],
            ['field' => 'status', 'value' => 'success']
        ]);

        if ($reservation->id) {

            $result = $this->transactionHelper->finalize($reservation);
            if (true || $result["isSuccess"]) {
                $payment->setTransactionId($reservation->uniqueId);
                $payment->setTransactionAdditionalInfo('raw_response', json_encode($result));
                $payment->addTransaction(Transaction::TYPE_CAPTURE);
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