<?php
namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\OrderHelperInterface;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview;

class OrderHelper implements OrderHelperInterface
{
    use \SantanderPaymentSolutions\SantanderPayments\Library\Traits\OrderHelper;
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper $transactionHelper
     */
    protected $transactionHelper;

    public function __construct(TransactionHelper $transactionHelper)
    {
        $this->transactionHelper = $transactionHelper;
    }

    /**
     * @param  \Magento\Payment\Gateway\Data\OrderAdapterInterface $order
     * @param $amount
     *
     * @return bool|null
     */
    public function getBasketFromOrder($order, $amount)
    {
        $orderId = $order->getId();
        if ($reservation = $this->transactionHelper->getTransaction('orderId', $orderId, 'reservation', 'success')) {
            $basketOverview            = new BasketOverview();
            $basketOverview->amount    = $amount;
            $basketOverview->vat       = 0;
            $basketOverview->amountNet = $basketOverview->amount - $basketOverview->vat;
            $basketOverview->currency  = $order->getCurrencyCode();
            $basketItems               = [];

            /** @var \Magento\Sales\Api\Data\OrderItemInterface $item */
            foreach ($order->getItems() as $item) {
                $basketItem           = new BasketItem();
                $basketItem->name     = $item->getName();
                $basketItem->quantity = $item->getQtyOrdered();
                $basketItem->price    = $item->getPriceInclTax();
                $basketItem->vat      = $item->getTaxPercent();
                $basketItems[]        = $basketItem;
            }

            return $this->transactionHelper->getBasketId($reservation->method, $basketOverview, $basketItems);
        }
        return null;
    }

}