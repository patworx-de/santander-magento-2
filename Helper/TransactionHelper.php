<?php

namespace SantanderPaymentSolutions\SantanderPayments\Helper;

use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderInvoicePaymentMethod;
use Magento\Framework\DataObject;
use SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\CollectionFactory;
use SantanderPaymentSolutions\SantanderPayments\Model\Transaction;
use SantanderPaymentSolutions\SantanderPayments\Model\Transaction as TransactionModel;
use SantanderPaymentSolutions\SantanderPayments\Model\TransactionFactory;

class TransactionHelper
{
    private $transactionFactory;
    private $transactionCollectionFactory;
    private $checkoutHelper;
    private $configHelper;
    private $integrationHelper;
    private $transactionResource;

    public function __construct(CheckoutHelper $checkoutHelper, TransactionFactory $transactionFactory, CollectionFactory $transactionCollectionFactory, ConfigHelper $configHelper, IntegrationHelper $integrationHelper, \SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction $transactionResource)
    {
        $this->transactionFactory = $transactionFactory;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->checkoutHelper = $checkoutHelper;
        $this->configHelper = $configHelper;
        $this->integrationHelper = $integrationHelper;
        $this->transactionResource = $transactionResource;
    }

    /**
     * @param $criteria
     * @return DataObject|TransactionModel
     */
    public function getTransaction($criteria)
    {
        return $this->getTransactions($criteria)->getFirstItem();
    }

    /**
     * @param $criteria
     * @return \SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\Collection
     */
    public function getTransactions($criteria)
    {
        /** @var \SantanderPaymentSolutions\SantanderPayments\Model\ResourceModel\Transaction\Collection $transactionCollection */
        $transactionCollection = $this->transactionCollectionFactory->create();
        foreach ($criteria as $criterion) {
            $transactionCollection->addFieldToFilter($criterion["field"], ['like' => $criterion["value"]]);
        }
        return $transactionCollection;

    }

    public function getBasketId($method)
    {
        $result = $this->call($method, 'basket', ['basket_overview' => $this->checkoutHelper->getBasketOverview(), 'basket_items' => $this->checkoutHelper->getBasketItems()]);
        if ($result["isSuccess"]) {
            return $result["id"];
        }
        return false;
    }

    private function call($method, $call, $parameters)
    {
        $callParameters = [
            'action' => $call,
            'method' => $method,
            'callback_url' => $this->configHelper->getCallbackUrl(),
            'basket_overview' => (!empty($parameters["basket_overview"]) ? $parameters["basket_overview"] : []),
            'basket_items' => (!empty($parameters["basket_items"]) ? $parameters["basket_items"] : []),
            'auth' => [
                'login' => $this->configHelper->get($method . '/login'),
                'password' => $this->configHelper->get($method . '/password'),
                'sender_id' => $this->configHelper->get($method . '/sender_id'),
                'channel' => $this->configHelper->get($method . '/channel')
            ],
            'is_live' => (bool)$this->configHelper->get($method . '/is_live'),
            'birth_date' => (!empty($parameters["birth_date"]) ? $parameters["birth_date"] : ''),
            'unique_id' => (!empty($parameters["uniqueId"]) ? $parameters["uniqueId"] : ''),
            'reference' => (!empty($parameters["reference"]) ? $parameters["reference"] : ''),
            'order_id' => (!empty($parameters["order_id"]) ? $parameters["order_id"] : ''),
        ];

        if ($call === 'basket') {
            $result = $this->_basketCall($callParameters);
        } else {
            if (!in_array($call, ['finalize', 'reversal'])) {
                $callParameters['basket_id'] = $this->getBasketId($method);
                $this->integrationHelper->setLastBasketId($callParameters['basket_id']);
                $callParameters['address'] = $this->checkoutHelper->getAddress();
            } elseif ($call == 'authorize_on_registration') {
                $callParameters['basket_id'] = $this->integrationHelper->getLastBasketId();
            }
            $result = $this->_transactionCall($callParameters);

        }
        $this->integrationHelper->log('info', __CLASS__ . '::' . __METHOD__ . '::' . __LINE__, 'CALL RESULT', [
            'method' => $method . '::' . $call,
            'parameters' => $callParameters,
            'result' => $result
        ]);
        $type = $call;
        if ($method === 'invoice' && $call === 'authorize') {
            $type = 'initialize';
        } elseif ($method === 'hire' && $call === 'authorize_on_registration') {
            $type = 'reservation';
        }

        $status = $result["isSuccess"] ? 'success' : 'error';
        if ($call === 'authorize_on_registration' && $status === 'success') {
            $status = 'open';
        }

        $transaction = $this->createTransaction();
        $transaction->amount = (!empty($result["response"]["presentation"]["amount"]) ? (float)$result["response"]["presentation"]["amount"] : null);
        $transaction->method = $method;
        $transaction->type = $type;
        $transaction->reference = (!empty($result["response"]["identification"]["transactionid"]) ? (string)$result["response"]["identification"]["transactionid"] : null);
        $transaction->sessionId = '';
        $transaction->createDatetime = date('Y-m-d H:i:s');
        $transaction->status = $status;
        $transaction->response = (string)$result["responseJson"];
        $transaction->request = $result["request"];
        $transaction->customerId = (!empty($parameters["basket_overview"]["customer_id"]) ? $parameters["basket_overview"]["customer_id"] : null);
        $transaction->currency = (!empty($parameters["basket_overview"]["currency"]) ? $parameters["basket_overview"]["currency"] : 'EUR');

        if (!empty($parameters["order_id"])) {
            $transaction->orderId = (int)$parameters["order_id"];
        }
        $this->saveTransaction($transaction);
        return $result;
    }

    private function _basketCall($parameters)
    {

        $auth = $parameters['auth'];
        $isLive = (bool)$parameters['is_live'];
        $basketOverview = $parameters['basket_overview'];
        $basketItems = $parameters['basket_items'];

        $request = new Request();
        $request->setIsSandboxMode(!$isLive);
        $request->setAuthentication(
            $auth["login"],
            $auth["password"],
            $auth["sender_id"]
        );

        $basket = new Basket();
        $request->setBasket($basket);
        $basket->setAmountTotalNet((int)($basketOverview['amount_net'] * 100));
        $basket->setAmountTotalVat((int)($basketOverview['vat'] * 100));
        $basket->setCurrencyCode($basketOverview["currency"]);
        $remainingAmount = $basketOverview["amount"];
        $position = 1;
        foreach ($basketItems as $item) {
            $basketItem = new BasketItem();
            $basketItem->setPosition($position++);
            $basketItem->setTitle($item["name"]);
            $basketItem->setAmountGross((int)($item['price'] * 100));
            $basketItem->setVat((int)$item['vat'] * 100);
            $basketItem->setAmountNet((int)($item['price'] * 100));
            $basketItem->setQuantity((int)$item['quantity']);
            $basketItem->setBasketItemReferenceId($item["id"] . '_' . uniqid());
            $basketItem->setAmountPerUnit(1);
            $basket->addBasketItem($basketItem);
            $remainingAmount -= $item['price'] * $item['quantity'];
        }

        if ($remainingAmount != 0) {
            $basketItem = new BasketItem();
            $basketItem->setPosition($position);
            $basketItem->setTitle('---');
            $basketItem->setAmountGross((int)($remainingAmount * 100));
            $basketItem->setVat(0);
            $basketItem->setAmountNet((int)($remainingAmount * 100));
            $basketItem->setQuantity(1);
            $basketItem->setBasketItemReferenceId('final_' . uniqid());
            $basketItem->setAmountPerUnit(1);
            $basket->addBasketItem($basketItem);

        }
        $response = $request->addNewBasket();

        return [
            'id' => $response->getBasketId(),
            'isSuccess' => $response->isSuccess(),
            'response' => json_decode(json_encode($response), true),
            'responseJson' => json_encode($response),
            'request' => serialize($request)
        ];

    }

    private function _transactionCall($parameters)
    {

        $action = $parameters['action'];
        $auth = $parameters['auth'];
        $isLive = (bool)$parameters['is_live'];
        $basketId = (!empty($parameters['basket_id']) ? $parameters['basket_id'] : null);
        $basketOverview = $parameters['basket_overview'];
        $address = (!empty($parameters['address']) ? $parameters['address'] : null);
        $method = $parameters['method'];
        $callbackUrl = $parameters['callback_url'];
        $birthDate = $parameters['birth_date'];
        $uniqueId = $parameters['unique_id'];
        $orderId = $parameters['order_id'];
        $reference = $parameters['reference'];

        switch ($method) {
            case 'invoice':
                $paymentMethodObject = new SantanderInvoicePaymentMethod();
                break;
            case 'hire':
                $paymentMethodObject = new SantanderHirePurchasePaymentMethod();
                break;
        }

        $request = $paymentMethodObject->getRequest();
        $request->authentification(
            $auth["sender_id"],
            $auth["login"],
            $auth["password"],
            $auth["channel"],
            !$parameters["is_live"]
        );

        $request->async(
            'DE', //TODO
            $callbackUrl
        );

        if (!empty($address)) {
            if ($isLive) {
                $request->customerAddress(
                    $address["first_name"],
                    $address["last_name"],
                    $address["company"],
                    $basketOverview["customer_id"],
                    $address["street"],
                    '',
                    $address["zip"],
                    $address["city"],
                    $address["country"],
                    $address["email"]
                );
            } else {
                $request->customerAddress(
                    'Grün',
                    'Ampel',
                    null,
                    0,
                    'Lichtweg 2',
                    'DE-BW',
                    '12345',
                    'Laterne',
                    'DE',
                    'gruene@ampeltesting.de'
                );
            }
            $request->getName()->setSalutation('MR'); //TODO
        }

        if (!empty($birthDate)) {
            $request->getName()->setBirthdate($birthDate);
        }

        if (!empty($basketId)) {
            $request->getBasket()->setId($basketId);
        }

        if (!empty($orderId)) {
            $request->getCriterion()->set('OrderReference', $orderId);
        }

        if (!empty($basketOverview)) {
            $request->basketData(
                ($reference ? $reference : 'santander_magento2_' . uniqid()),
                round($basketOverview["amount"], 2),
                $basketOverview["currency"],
                'ChT4JJ9x9xvsU88hjdFA28vJwKpZvJHGQ6B6kLJnTjFDZ8RKwkzmTqHjrRS8R82L'
            );
        }

        /* TODO
        $request->getRiskInformation()->setCustomerGuestCheckout(
            $isGuest ? 'true' : 'false'
        );
        $request->getRiskInformation()->setCustomerSince($registerDateString);
        $request->getRiskInformation()->setCustomerOrderCount($r["order_count"]);
        */

        switch ($action) {
            case 'authorize':
                $paymentMethodObject->authorize();
                break;
            case 'initialize':
                $paymentMethodObject->initialize();
                break;
            case 'finalize':
                $paymentMethodObject->finalize($uniqueId);
                break;
            case 'reversal':
                $paymentMethodObject->reversal($uniqueId);
                break;
            case 'authorize_on_registration':
                $paymentMethodObject->authorizeOnRegistration($uniqueId);
                break;
            default:
                return ['isSuccess' => false, 'error' => 'unknown action: "' . $action . '"'];
        }

        $response = $paymentMethodObject->getResponse();

        $return = [
            'isSuccess' => $response->isSuccess(),
            'responseJson' => json_encode($response),
            'response' => json_decode(json_encode($response), true),
            'request' => serialize($request)
        ];

        if ($optIn = $response->getConfig()->getOptinText()) {
            $return["optinTexts"] = $optIn;
        }

        return $return;

    }

    /**
     * @return TransactionModel
     */
    public function createTransaction()
    {
        return $this->transactionFactory->create();
    }

    public function saveTransaction(Transaction $transaction)
    {
        $this->transactionResource->save($transaction);
    }

    public function initialize($method, $birthday = null)
    {
        return $this->call(
            $method,
            'initialize',
            [
                'birth_date' => $birthday,
                'basket_overview' => $this->checkoutHelper->getBasketOverview(),
                'basket_items' => $this->checkoutHelper->getBasketItems()
            ]
        );
    }

    public function authorize($method)
    {
        return $this->call(
            $method,
            'authorize',
            [
                'basket_overview' => $this->checkoutHelper->getBasketOverview(),
                'basket_items' => $this->checkoutHelper->getBasketItems()
            ]
        );
    }

    public function finalize(TransactionModel $reservation)
    {

        return $this->call(
            $reservation->method,
            'finalize',
            [
                'uniqueId' => $reservation->uniqueId,
                'order_id' => $reservation->orderId,
                'basket_overview' => [
                    'amount' => $reservation->amount,
                    'currency' => $reservation->currency,
                ]
            ]
        );

    }

    public function reversal($uniqueId, $amount)
    {
        $reservation = $this->transactionRepository->getTransaction([
            ['uniqueId', '=', $uniqueId],
            ['type', '=', 'reservation'],
            ['status', '=', 'success']
        ]);
        if ($reservation) {
            return $this->call($reservation->method, 'reversal', ['uniqueId' => $uniqueId, 'order_id' => $reservation->orderId], ['currency' => $reservation->currency, 'basketAmount' => $amount], []);
        }
    }

    public function authorizeOnRegistration(TransactionModel $initialize2Transaction)
    {
        return $this->call(
            $initialize2Transaction->method,
            'authorize_on_registration',
            [
                'uniqueId' => $initialize2Transaction->uniqueId,
                'reference' => $initialize2Transaction->reference,
                'basket_overview' => [
                    'currency' => $initialize2Transaction->currency,
                    'amount' => $initialize2Transaction->amount
                ]
            ]
        );

    }

}


