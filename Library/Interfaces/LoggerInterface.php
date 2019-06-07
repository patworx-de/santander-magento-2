<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Interfaces;
interface LoggerInterface{
    public function log($msg, $level, $data);
}