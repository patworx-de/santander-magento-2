<?php

namespace SantanderPaymentSolutions\SantanderPayments\Block;

use Magento\Framework\DataObject;
use Magento\Payment\Block\ConfigurableInfo;

class HireInfo extends ConfigurableInfo
{

    protected function getLabel($field)
    {
        return __($field);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = new DataObject();

        return $transport;
    }

}
