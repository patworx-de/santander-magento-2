<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Struct;

class CallResult extends Base{
    public $id;
    public $requestObject;
    public $requestArray;
    public $responseObject;
    public $responseArray;
    public $isSuccess;
    public $errors;
    public $transaction;
}