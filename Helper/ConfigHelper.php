<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface;

class ConfigHelper extends AbstractHelper implements ConfigHelperInterface
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
        return $this->_urlBuilder->getRouteUrl('santander/callback/');
    }

    public function getInstantTransferWebHookUrl()
    {
        return $this->_urlBuilder->getRouteUrl('santander/webhook/');
    }

    public function getInstantTransferControllerUrl()
    {
        return $this->_urlBuilder->getRouteUrl('santander/instant/');
    }

    public function getInstantTransferFinishControllerUrl()
    {
        return $this->_urlBuilder->getRouteUrl('santander/instant/finish/');
    }

    public function getInstantTransferCancelControllerUrl()
    {
        return $this->_urlBuilder->getRouteUrl('santander/instant/cancel/');
    }

    public function getAuth($method, $store = null)
    {
        return [
            'login' => $this->get($method . '/login'),
            'password' => $this->get($method . '/password'),
            'sender_id' => $this->get($method . '/sender_id'),
            'channel' => $this->get($method . '/channel')
        ];
    }

    public function isLive($method, $store = null)
    {
        return (bool)$this->get($method . '/is_live');
    }

    public function getLanguage()
    {
        return 'DE'; // TODO
    }

    public function getPluginId()
    {
        return 'santander_magento2';
    }

    public function getCallSecret()
    {
        return 'VLKSZuLPwtaNXNwST87W3jYSUdwxQjV7KFpjqQcA2GW3cSeE';
    }
}