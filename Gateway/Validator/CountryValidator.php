<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use SantanderPaymentSolutions\SantanderPayments\Helper\CheckoutHelper;

class CountryValidator extends AbstractValidator
{

    private $countries;
    private $checkoutHelper;

    public function __construct(ResultInterfaceFactory $resultFactory, CheckoutHelper $checkoutHelper)
    {
        $this->countries = ['DE'];
        $this->checkoutHelper = $checkoutHelper;
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     */
    public function validate(array $validationSubject)
    {
        $isValid = in_array($validationSubject['country'], $this->countries);
        if($isValid){
            $address = $this->checkoutHelper->getAddress();
            if(!empty($address->company)){
                $isValid = false;
            }
        }
        return $this->createResult($isValid);
    }
}
