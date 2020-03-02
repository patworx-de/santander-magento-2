<?php

namespace SantanderPaymentSolutions\SantanderPayments\Controller\Instant;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class PlaceOrder
 * @deprecated
 */
class Cancel extends Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $checkoutSession
     */
    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;

        return parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->checkoutSession->getLastRealOrder()->setStatus('canceled')->save();
        $this->checkoutSession->restoreQuote();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
    }
}
