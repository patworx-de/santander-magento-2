<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Struct;

class Transaction extends Base{
    public $id;
    public $method;
    public $type;
    public $createDatetime;
    public $request;
    public $response;
    public $amount;
    public $currency;
    public $customerId;
    public $orderId;
    public $status;
    public $reference;
    public $uniqueId;
    public $model;
}