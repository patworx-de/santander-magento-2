<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;

class CheckoutHelper
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

    public function getBasketOverview()
    {
        $quote = $this->checkoutSession->getQuote();
        $vat = $quote->getGrandTotal() - $quote->getSubtotal();

        return [
            'customer_id' => 1, //TODO
            'amount' => $quote->getGrandTotal(),
            'amount_net' => $quote->getSubtotal(),
            'vat' => $vat,
            'currency' => ($quote->getQuoteCurrencyCode() ? $quote->getQuoteCurrencyCode() : 'EUR')
        ];
    }

    public function getBasketItems()
    {
        $items = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($this->checkoutSession->getQuote()->getAllItems() as $item) {
            if ($price = $item->getPriceInclTax()) {
                $items[] = [
                    'name' => $item->getName(),
                    'price' => $price,
                    'vat' => 0,
                    'quantity' => $item->getQty(),
                    'id' => $item->getSku()
                ];
            }
        }
        return $items;
    }

    public function getAddress()
    {
        $quote = $this->checkoutSession->getQuote();
        $address = $quote->getShippingAddress();

        return [
            'first_name' => $address->getFirstname(),
            'last_name' => $address->getLastname(),
            'company' => $address->getCompany(),
            'street' => $address->getStreetFull(),
            'zip' => $address->getPostcode(),
            'city' => $address->getCity(),
            'country' => $address->getCountryModel()->getCountryId(),
            'email' => $address->getEmail()
        ];

    }
}


