<?php

namespace SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init('SantanderPaymentSolutions\SantanderPayments\Model\Transaction', 'SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction');
    }
}