<?php

namespace SantanderPaymentSolutions\SantanderPayments\Model;

use Magento\Framework\Model\AbstractModel;
use SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction as TransactionResourceModel;

class Transaction extends AbstractModel
{

    private $id;
    private $orderId;
    private $customerId;
    private $paymentId;
    private $method;
    private $type;
    private $status;
    private $uniqueId;
    private $reference;
    private $createDatetime;
    private $amount;
    private $currency;
    private $sessionId;
    private $request;
    private $response;

    public function __get($name)
    {
        return $this->getData($name);
    }

    public function __set($name, $value)
    {
        $this->setData($name, $value);
    }

    protected function _construct()
    {
        $this->_init(TransactionResourceModel::class);
    }
}