<?php

namespace SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Transaction extends AbstractDb
{

    public function _construct()
    {
        $this->_init('santander_transactions', 'id');
    }
}
