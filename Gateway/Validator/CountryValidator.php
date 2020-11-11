<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use SantanderPaymentSolutions\SantanderPayments\Helper\CheckoutHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class CountryValidator extends AbstractValidator
{

    private $countries;
    private $checkoutHelper;
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper
     */
    private $transactionHelper;

    public function __construct(ResultInterfaceFactory $resultFactory, CheckoutHelper $checkoutHelper, TransactionHelper $transactionHelper)
    {
        $this->countries = ['DE'];
        $this->checkoutHelper = $checkoutHelper;
        $this->transactionHelper = $transactionHelper;
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     */
    public function validate(array $validationSubject)
    {
        //TODO split in several validators
        $isValid = in_array($validationSubject['country'], $this->countries);
        if($isValid){
            $isValid = $this->checkoutHelper->isAddressSet() && $this->checkoutHelper->isAddressOk() && $this->checkoutHelper->isShippingSet();
        }
        if($isValid){
            foreach($this->transactionHelper->getTransactions('sessionId', $this->checkoutHelper->getSessionIdentifier(), 'initialize') as $initialize) {
                if ($this->transactionHelper->getTransaction('reference', $initialize->reference, 'reservation', 'error')) {
                    $isValid = false;
                }
            }
        }
        return $this->createResult($isValid);
    }
}
