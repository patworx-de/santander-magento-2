<?php

namespace SantanderPaymentSolutions\SantanderPayments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CountryValidator extends AbstractValidator
{

    private $countries;

    public function __construct(ResultInterfaceFactory $resultFactory)
    {
        $this->countries = ['DE'];
        parent::__construct($resultFactory);
    }

    /**
     * @param array $validationSubject
     */
    public function validate(array $validationSubject)
    {
        $isValid = in_array($validationSubject['country'], $this->countries);
        return $this->createResult($isValid);
    }
}
