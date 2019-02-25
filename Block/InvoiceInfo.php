<?php

namespace SantanderPaymentSolutions\SantanderPayments\Block;

use Magento\Framework\DataObject;
use Magento\Payment\Block\ConfigurableInfo;

class InvoiceInfo extends ConfigurableInfo
{

    protected function getLabel($field)
    {
        return __($field);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = new DataObject();
        $additionalInformation = $this->getData("info")->getData("additional_information");
        if (!empty($additionalInformation["bank"])) {
            if ($bankInfo = json_decode($additionalInformation["bank"], true)) {
                $transport->setData('Empfänger', $bankInfo["account_holder"]);
                $transport->setData('IBAN', $bankInfo["account_iban"]);
                $transport->setData('BIC', $bankInfo["account_bic"]);
                $transport->setData('Verwendungszweck', $bankInfo["account_usage"]);
            }
        }

        return $transport;
    }

}
