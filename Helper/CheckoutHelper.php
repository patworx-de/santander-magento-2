<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote;
use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\CheckoutHelperInterface;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\Address;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview;

class CheckoutHelper implements CheckoutHelperInterface
{
    /**
     * @var \Magento\Quote\Model\Quote $quote
     */
    private $quote;
    private $checkoutSession;

    public function __construct(Quote $quote, Session $checkoutSession)
    {
        $this->quote           = $quote;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview
     */
    public function getBasketOverview()
    {

        $quote           = $this->checkoutSession->getQuote();
        $vat             = $quote->getGrandTotal() - $quote->getSubtotal();
        $objectManager   = ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerId      = 0;
        $isGuest         = true;
        if ($customerSession->isLoggedIn()) {
            /** @var \Magento\Customer\Model\Customer $customer */
            $customer   = $customerSession->getCustomer();
            $customerId = $customer->getId();
            $isGuest    = false;
        }

        return new BasketOverview([
            'amount'           => $quote->getGrandTotal(),
            'vat'              => $vat,
            'amountNet'        => $quote->getGrandTotal() - $vat,
            'currency'         => ($quote->getQuoteCurrencyCode() ? $quote->getQuoteCurrencyCode() : 'EUR'),
            'customerId'       => $customerId,
            'isGuest'          => $isGuest,
            'registrationDate' => null, //TODO
            'numberOfOrders'   => 0 //TODO
        ]);
    }

    /**
     * @return array|\SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem[]
     */
    public function getBasketItems()
    {
        $items = [];
        /** @var \Magento\Quote\Model\Quote\Item $cartItem */
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $cartItem) {
            if ($price = $cartItem->getPriceInclTax()) {
                $item           = new BasketItem();
                $item->name     = $cartItem->getName();
                $item->id       = $cartItem->getSku();
                $item->quantity = $cartItem->getQty();
                $item->vat      = $cartItem->getTaxPercent();
                $item->price    = $price;
                $items[]        = $item;
            }
        }

        return $items;
    }

    public function getAddress($gender = null)
    {
        $quote = $this->checkoutSession->getQuote();
        if ($address = $quote->getBillingAddress()) {
            return new Address([
                'firstName' => $address->getFirstname(),
                'lastName'  => $address->getLastname(),
                'company'   => $address->getCompany(),
                'street'    => $address->getStreetFull(),
                'postcode'  => $address->getPostcode(),
                'city'      => $address->getCity(),
                'country'   => $address->getCountryModel()->getCountryId(),
                'email'     => $quote->getCustomerEmail(),
                'gender'    => $gender
            ]);
        }

        return new Address([]);
    }

    public function getSessionIdentifier(){
        return $this->checkoutSession->getSessionId().'_'.round($this->getBasketOverview()->amount*100).'_'.md5(serialize($this->getAddress()->toArray()));
    }

    public function isAddressOk()
    {
        $quote = $this->checkoutSession->getQuote();
        if (($billingAddress = $quote->getBillingAddress()) && ($shippingAddress = $quote->getShippingAddress())) {
            return (
                empty($billingAddress->getCompany())
                &&
                empty($shippingAddress->getCompany())
                &&
                $billingAddress->getFirstname() === $shippingAddress->getFirstname()
                &&
                $billingAddress->getLastname() === $shippingAddress->getLastname()
                &&
                $billingAddress->getStreetFull() === $shippingAddress->getStreetFull()
                &&
                $billingAddress->getPostcode() === $shippingAddress->getPostcode()
                &&
                $billingAddress->getCountryId() === $shippingAddress->getCountryId()
            );
        }

        return false;
    }

}


