<?php

namespace SantanderPaymentSolutions\SantanderPayments\Controller\Instant;

use Helper\Integration;
use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Library\Classes\InstantTransferClient;

/**
 * Class PlaceOrder
 */
class Finish extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper
     */
    private $configHelper;
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper
     */
    private $integrationHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper $configHelper
     * @param \SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper $integrationHelper
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        CartManagementInterface $cartManagement,
        ConfigHelper $configHelper,
        IntegrationHelper $integrationHelper
    ) {
        $this->checkoutSession   = $checkoutSession;
        $this->cartManagement    = $cartManagement;
        $this->configHelper      = $configHelper;
        $this->integrationHelper = $integrationHelper;

        return parent::__construct($context);
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $order          = $this->checkoutSession->getLastRealOrder();
        if (($transaction = $this->integrationHelper->getInstantTransactionId()) && $order->getId()) {
            try {
                $paymentClient = new InstantTransferClient($this->configHelper);
                $details       = $paymentClient->getTransactionDetails($transaction);
                if (!empty($details['transaction']) && round($details['amount'], 2) === round($order->getGrandTotal(), 2)) {
                    $order->setStatus('processing');
                } else {
                    var_dump($details);
                    $order->setStatus('fraud');
                }
            } catch (\Exception $e) {
                $order->setStatus('payment_review');
            }
        } else {
            $order->setStatus('payment_review');
        }
        $order->save();

        return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
    }

}
