<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper extends AbstractHelper
{

    public function get($key)
    {
        return $this->scopeConfig->getValue(
            'payment/santander_' . $key,
            ScopeInterface::SCOPE_STORE
        );

    }

    public function getCallbackUrl()
    {
        return $this->_urlBuilder->getRouteUrl('santander/callback/index/');
    }

}