<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;

class InvoiceAuthorizeCommand implements CommandInterface
{
    private $integrationHelper;

    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    public function execute(array $commandSubject)
    {
        $this->integrationHelper->log('success', __CLASS__ . '::' . __METHOD__ . '::' . __LINE__, 'execute');
    }
}