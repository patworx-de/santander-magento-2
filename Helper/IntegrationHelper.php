<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Framework\Session\SessionManagerInterface;

class IntegrationHelper
{

    private $session;

    public function __construct(SessionManagerInterface $session)
    {

        $this->session = $session;
    }

    public function translate($textId)
    {
        return $textId;
    }

    public function log($level, $id, $msg, $body = '')
    {
        file_put_contents(BP . '/var/log/santander_' . $level . '.log', date('Y-m-d H:i:s') . ' [' . $id . '] ' . $msg . ($body ? "\n\n" . print_r($body, true) . "\n\n" : '') . "\n", FILE_APPEND);
    }

    public function setLastReference($reference)
    {
        $this->session->setSantanderLastReference($reference);

    }

    public function getLastReference()
    {
        return $this->session->getSantanderLastReference();

    }

    public function setLastBasketId($basketId)
    {
        $this->session->setSantanderLastBasketId($basketId);

    }

    public function getLastBasketId()
    {
        return $this->session->getSantanderLastBasketId();

    }

}


