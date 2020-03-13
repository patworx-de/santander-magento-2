<?php

namespace SantanderPaymentSolutions\SantanderPayments\Block;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Library\Classes\InstantTransferClient;

class InstantCheckout extends Template
{

    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper
     */
    private $configHelper;

    public function __construct(Template\Context $context, ConfigHelper $configHelper, Session $checkoutSession, IntegrationHelper $integrationHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->configHelper    = $configHelper;
        $instantTransferClient = new InstantTransferClient($configHelper);
        $order                 = $checkoutSession->getLastRealOrder();
        if ($order->getId() === null) {

        } else {
            $address      = $order->getBillingAddress();
            $sessionInfos = $instantTransferClient->getSession(
                $order->getId(),
                $order->getGrandTotal(),
                $order->getOrderCurrencyCode(),
                $address->getFirstname() . ' ' . $address->getLastname(),
                implode(' ', $address->getStreet()) . ', ' . $address->getPostcode() . ' ' . $address->getCity(),
                $address->getEmail()
            );
            $integrationHelper->setInstantTransactionId($sessionInfos['transaction']);
            $this->setData('sessionKey', $sessionInfos['wizard_session_key']);
        }
    }

    public function getSessionKey()
    {
        return $this->getData('sessionKey');
    }

    public function getCancelUrl()
    {
        return $this->configHelper->getInstantTransferCancelControllerUrl();
    }

    public function getSuccessUrl()
    {
        return $this->configHelper->getInstantTransferFinishControllerUrl();
    }
}