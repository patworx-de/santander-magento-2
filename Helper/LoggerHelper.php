<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\LoggerInterface;

class LoggerHelper implements LoggerInterface
{

    private $integrationHelper;

    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    public function log($msg, $level, $data)
    {
        $this->integrationHelper->log($level, '', $msg, $data = '');
    }

}


