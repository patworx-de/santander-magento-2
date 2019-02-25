<?php

namespace SantanderPaymentSolutions\SantanderPayments\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use SantanderPaymentSolutions\SantanderPayments\Helper\ConfigHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;


final class InvoiceConfigProvider implements ConfigProviderInterface
{
    const CODE = 'santander_invoice';
    private $transactionHelper;
    private $integrationHelper;
    private $configHelper;

    public function __construct(TransactionHelper $transactionHelper, IntegrationHelper $integrationHelper, ConfigHelper $configHelper)
    {
        $this->transactionHelper = $transactionHelper;
        $this->integrationHelper = $integrationHelper;
        $this->configHelper = $configHelper;
    }

    public function getConfig()
    {
        $response = $this->transactionHelper->authorize('invoice');
        if (!empty($response["optinTexts"])) {
            $this->integrationHelper->setLastReference($response["response"]["identification"]["transactionid"]);
            return [
                'payment' => [
                    self::CODE => [
                        'callback_url' => $this->configHelper->getCallbackUrl(),
                        'privacy_optin' => $response["optinTexts"]["privacy_policy"],
                        'additional_optin' => $response["optinTexts"]["optin"],
                        'logo' => $response["optinTexts"]["logolink"]
                    ]
                ]
            ];
        }
        return null;
    }
}
