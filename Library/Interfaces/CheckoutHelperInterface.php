<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Interfaces;

interface CheckoutHelperInterface{
    /**
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview
     */
    public function getBasketOverview();

    /**
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem[]
     */
    public function getBasketItems();

    /**
     * @param null|string $gender
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Address
     */
    public function getAddress($gender = null);
}