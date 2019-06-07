<?php

namespace SantanderPaymentSolutions\SantanderPayments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\OrderHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class OrderStatus implements ObserverInterface
{
    private $integrationHelper;
    private $transactionHelper;
    private $configHelper;
    private $orderHelper;

    public function __construct(IntegrationHelper $integrationHelper, TransactionHelper $transactionHelper, ConfigHelper $configHelper, OrderHelper $orderHelper)
    {
        $this->integrationHelper = $integrationHelper;
        $this->transactionHelper = $transactionHelper;
        $this->configHelper = $configHelper;
        $this->orderHelper = $orderHelper;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order   = $observer->getEvent()->getOrder();
        $orderId = $order->getId();
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment   = $order->getPayment();
        if (in_array($payment->getMethod(), ['santander_invoice', 'santander_hire'])) {
            if ($this->configHelper->get(str_replace('santander_', '', $payment->getMethod()) . '/finalize_status') === $order->getStatus()) {
                if ($reservation = $this->transactionHelper->getTransaction('orderId', $orderId, 'reservation')) {
                    if ($amount = $this->orderHelper->getOpenAmount($reservation->reference)) {
                        try {
                            $payment->getMethodInstance()->capture($payment, $amount);
                        } catch (\Exception $e) {

                        }
                    }
                }
            }
        }
    }
}