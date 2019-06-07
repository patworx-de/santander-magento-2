<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Checkout\Model\Session;
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
        $this->quote = $quote;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview
     */
    public function getBasketOverview()
    {

        $quote = $this->checkoutSession->getQuote();
        $vat = $quote->getGrandTotal() - $quote->getSubtotal();
        return new BasketOverview([
            'amount'           => $quote->getGrandTotal(),
            'vat'              => $vat,
            'amountNet'        => $quote->getGrandTotal()-$vat,
            'currency'         => ($quote->getQuoteCurrencyCode() ? $quote->getQuoteCurrencyCode() : 'EUR'),
            'customerId'       => 0, //TODO
            'isGuest'          => null, //TODO
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
                $item->vat      = 0;
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
                'email'     => $address->getEmail(),
                'gender'    => $gender
            ]);
        }
        return new Address([]);
    }
}


