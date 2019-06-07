<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Interfaces;

interface ConfigHelperInterface{
    public function getAuth($method);
    public function isLive($method);
    public function getCallbackUrl();
    public function getLanguage();
    public function getPluginId();
    public function getCallSecret();
}