<?php

namespace SantanderPaymentSolutions\SantanderPayments\Library\Traits;

use Heidelpay\PhpBasketApi\Object\Basket;
use Heidelpay\PhpBasketApi\Object\BasketItem;
use Heidelpay\PhpBasketApi\Request as BasketRequest;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderHirePurchasePaymentMethod;
use Heidelpay\PhpPaymentApi\PaymentMethods\SantanderInvoicePaymentMethod;
use SantanderPaymentSolutions\SantanderPayments\Library\Classes\XmlApiClient;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult;
use SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction;

trait TransactionHelper
{
    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\CheckoutHelperInterface
     */
    protected $checkoutHelper;

    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface
     */
    protected $configHelper;

    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\Logger
     */
    protected $logger;

    public function initialize($method, $birthday = null)
    {
        return $this->call(
            $method,
            'initialize',
            [
                'birth_date'      => $birthday,
                'basket_overview' => $this->checkoutHelper->getBasketOverview(),
                'basket_items'    => $this->checkoutHelper->getBasketItems()
            ]
        );
    }

    /**
     * @param string $method
     * @param string $call
     * @param array $parameters
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult
     * @throws \Exception
     */
    private function call($method, $call, $parameters)
    {
        $callParameters = [
            'action'          => $call,
            'method'          => $method,
            'callback_url'    => $this->configHelper->getCallbackUrl(),
            'basket_overview' => (!empty($parameters["basket_overview"]) ? $parameters["basket_overview"] : null),
            'basket_items'    => (!empty($parameters["basket_items"]) ? $parameters["basket_items"] : []),
            'auth'            => $this->configHelper->getAuth($method),
            'is_live'         => $this->configHelper->isLive($method),
            'birth_date'      => (!empty($parameters["birth_date"]) ? $parameters["birth_date"] : ''),
            'unique_id'       => (!empty($parameters["unique_id"]) ? $parameters["unique_id"] : ''),
            'reference'       => (!empty($parameters["reference"]) ? $parameters["reference"] : ''),
            'order_id'        => (!empty($parameters["order_id"]) ? $parameters["order_id"] : ''),
        ];

        if ($call === 'basket') {
            $result = $this->_basketCall($callParameters);
        } else {
            if (!in_array($call, ['finalize', 'reversal', 'refund'])) {
                $callParameters['basket_id'] = $this->getBasketId($method);
                //TODO SantanderIntegrationHelper::setLastBasketId($callParameters['basket_id']);
                $gender                    = (!empty($parameters["gender"]) ? $parameters["gender"] : 'MR');
                $callParameters['address'] = $this->checkoutHelper->getAddress($gender);
            } else {
                if (isset($parameters["basket_id"])) {
                    $callParameters['basket_id'] = $parameters["basket_id"];
                }
            }
            $result = $this->_transactionCall($callParameters);
        }

        /**
         * @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\CallResult $result
         */

        $this->logger->log('CALL RESULT', 'info', [
            'method'     => $method . '::' . $call,
            'parameters' => $callParameters,
            'result'     => $result
        ]);
        $type = $call;

        if ($method === 'invoice' && $call === 'authorize') {
            $type = 'initialize';
        } elseif ($method === 'hire' && $call === 'authorize_on_registration') {
            $type = 'reservation';
        }

        $status = $result->isSuccess ? 'success' : 'error';
        if ($call === 'authorize_on_registration' && $status === 'success') {
            $status = 'open';
        }

        $transaction                 = new Transaction();
        $transaction->amount         = (!empty($result->responseArray["presentation"]["amount"]) ? (float)$result->responseArray["presentation"]["amount"] : null);
        $transaction->method         = $method;
        $transaction->type           = $type;
        $transaction->reference      = (!empty($result->responseArray["identification"]["transactionid"]) ? (string)$result->responseArray["identification"]["transactionid"] : null);
        $transaction->uniqueId       = (!empty($result->responseArray["identification"]["uniqueid"]) ? (string)$result->responseArray["identification"]["uniqueid"] : null);
        $transaction->createDatetime = date('Y-m-d H:i:s');
        $transaction->status         = $status;
        $transaction->response       = (string)json_encode($result->responseArray);
        $transaction->request        = (string)json_encode($result->requestArray);
        $transaction->customerId     = (!empty($parameters["basket_overview"]->customer_id) ? $parameters["basket_overview"]->customerId : null);
        $transaction->currency       = (!empty($parameters["basket_overview"]->currency) ? $parameters["basket_overview"]->currency : 'EUR');

        if ($transaction->type === 'initialize') {
            $transaction->sessionId = $this->checkoutHelper->getSessionIdentifier();
        }
        if (!empty($parameters["order_id"])) {
            $transaction->orderId = (int)$parameters["order_id"];
        }
        $this->saveTransaction($transaction);
        $result->transaction = $transaction;

        return $result;
    }

    private function _basketCall($parameters)
    {

        $auth   = $parameters['auth'];
        $isLive = (bool)$parameters['is_live'];
        /** @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview $basketOverview */
        $basketOverview = $parameters['basket_overview'];
        /** @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem[] $basketItems */
        $basketItems = $parameters['basket_items'];

        $request = new BasketRequest();
        $request->setIsSandboxMode(!$isLive);
        $request->setAuthentication(
            $auth["login"],
            $auth["password"],
            $auth["sender_id"]
        );

        $basket = new Basket();
        $request->setBasket($basket);
        $basket->setAmountTotalNet((int)round($basketOverview->amountNet * 100));
        $basket->setAmountTotalVat((int)round($basketOverview->vat * 100));
        $basket->setCurrencyCode($basketOverview->currency);
        $remainingAmount = $basketOverview->amount;
        $position        = 1;
        /** @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketItem $item */
        foreach ($basketItems as $item) {
            $basketItem = new BasketItem();
            $basketItem->setPosition($position++);
            $basketItem->setTitle($item->name);
            $basketItem->setAmountGross((int)round($item->price * $item->quantity * 100));
            $basketItem->setVat((int)round($item->vat));
            $basketItem->setAmountNet((int)round(($item->price / (100 + $item->vat) * 100) * $item->quantity * 100));
            $basketItem->setQuantity((int)round($item->quantity));
            $basketItem->setBasketItemReferenceId($item->id . '_' . uniqid());
            $basketItem->setAmountPerUnit((int)round($item->price * 100));
            $basket->addBasketItem($basketItem);
            $remainingAmount -= $item->price * $item->quantity;
        }

        if ($remainingAmount != 0) {
            $basketItem = new BasketItem();
            $basketItem->setPosition($position);
            $basketItem->setTitle('---');
            $basketItem->setAmountGross((int)round($remainingAmount * 100));
            $basketItem->setVat(0);
            $basketItem->setAmountNet((int)round($remainingAmount * 100));
            $basketItem->setQuantity(1);
            $basketItem->setBasketItemReferenceId('final_' . uniqid());
            $basketItem->setAmountPerUnit((int)round($remainingAmount * 100));
            $basket->addBasketItem($basketItem);

        }
        $response               = $request->addNewBasket();
        $result                 = new CallResult();
        $result->id             = $response->getBasketId();
        $result->isSuccess      = $response->isSuccess();
        $result->responseObject = $response;
        $result->responseArray  = json_decode(json_encode($response), true);
        $result->requestObject  = $request;
        $result->requestArray   = json_decode(json_encode($request), true);

        return $result;
    }

    public function getBasketId($method, $basketOverview = null, $basketItems = null)
    {
        $result = $this->call($method, 'basket', [
            'basket_overview' => ($basketOverview !== null ? $basketOverview : $this->checkoutHelper->getBasketOverview()),
            'basket_items'    => ($basketItems !== null ? $basketItems : $this->checkoutHelper->getBasketItems())
        ]);
        if ($result->isSuccess) {
            return $result->id;
        }

        return false;
    }

    private function _transactionCall($parameters)
    {

        $action   = $parameters['action'];
        $auth     = $parameters['auth'];
        $isLive   = (bool)$parameters['is_live'];
        $basketId = (!empty($parameters['basket_id']) ? $parameters['basket_id'] : null);
        /** @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\BasketOverview $basketOverview */
        $basketOverview = $parameters['basket_overview'];

        /** @var \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Address $address */
        $address     = (!empty($parameters['address']) ? $parameters['address'] : null);
        $method      = $parameters['method'];
        $callbackUrl = $parameters['callback_url'];
        $birthDate   = $parameters['birth_date'];
        $uniqueId    = $parameters['unique_id'];
        $orderId     = $parameters['order_id'];
        $reference   = $parameters['reference'];

        switch ($method) {
            case 'invoice':
                $paymentMethodObject = new SantanderInvoicePaymentMethod();
                break;
            case 'hire':
                $paymentMethodObject = new SantanderHirePurchasePaymentMethod();
                break;
            default:
                throw new \Exception('Payment method unknown: ' . $method);
        }

        $request = $paymentMethodObject->getRequest();

        if (in_array($action, ['reservation', 'initialize', 'authorize', 'authorize_on_registration'])) {
            $request->getContact()->setIp($_SERVER['REMOTE_ADDR']);
        }

        $request->authentification(
            $auth["sender_id"],
            $auth["login"],
            $auth["password"],
            $auth["channel"],
            !$parameters["is_live"]
        );

        $request->async(
            $this->configHelper->getLanguage(),
            $callbackUrl
        );

        if (!empty($address)) {
            if ($isLive) {
                $request->customerAddress(
                    $address->firstName,
                    $address->lastName,
                    $address->company,
                    $basketOverview->customerId,
                    $address->street,
                    '',
                    $address->postcode,
                    $address->city,
                    $address->country,
                    $address->email
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
            $request->getName()->setSalutation($address->gender);
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

        $mageVersion = 2;
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
            $mageVersion = $productMetadata->getVersion();
        } catch (\Exception $e) {
            $mageVersion = 2;
        }
        $request->getCriterion()->set('SHOP.TYPE', 'Magento ' . $mageVersion);
        $request->getCriterion()->set('SHOPMODULE.VERSION', 'SantanderPaymentSolutions Magento 2 1.3.3');

        if (in_array($action, ['reservation', 'initialize', 'authorize', 'authorize_on_registration'])) {
            $request->getContact()->setIp($_SERVER['REMOTE_ADDR']);
        }

        if (!empty($basketOverview)) {
            $request->basketData(
                ($reference ? $reference : $this->configHelper->getPluginId() . '_' . uniqid()),
                round($basketOverview->amount, 2),
                $basketOverview->currency,
                $this->configHelper->getCallSecret()
            );
            $request->getRiskInformation()->setCustomerGuestCheckout(($basketOverview->isGuest === false ? false : true));
            $request->getRiskInformation()->setCustomerOrderCount($basketOverview->numberOfOrders);
            if (!empty($basketOverview->registrationDate)) {
                $request->getRiskInformation()->setCustomerSince($basketOverview->registrationDate);
            }
        }

        switch ($action) {
            case 'authorize':
                $paymentMethodObject->authorize();
                break;
            case 'initialize':
                $paymentMethodObject->initialize();
                break;
            case 'finalize':
                $request->getIdentification()->setReferenceid($uniqueId);
                $paymentMethodObject->finalize($uniqueId);
                break;
            case 'reversal':
                $request->getIdentification()->setReferenceid($uniqueId);
                $paymentMethodObject->reversal($uniqueId);
                break;
            case 'refund':
                $request->getIdentification()->setReferenceid($uniqueId);
                $paymentMethodObject->refund($uniqueId);
                break;
            case 'authorize_on_registration':
                $paymentMethodObject->authorizeOnRegistration($uniqueId);
                break;
            default:
                return ['isSuccess' => false, 'error' => 'unknown action: "' . $action . '"'];
        }

        $response                      = $paymentMethodObject->getResponse();
        $result                        = new CallResult();
        $result->isSuccess             = $response->isSuccess();
        $result->responseObject        = $response;
        $result->responseArray         = json_decode(json_encode($response), true);
        $result->originalResponseArray = $paymentMethodObject->getResponseArray();
        $result->requestObject         = $request;
        $result->requestArray          = json_decode(json_encode($request), true);

        return $result;
    }

    abstract function saveTransaction(Transaction $transaction);

    public function authorize($method)
    {
        return $this->call(
            $method,
            'authorize',
            [
                'basket_overview' => $this->checkoutHelper->getBasketOverview(),
                'basket_items'    => $this->checkoutHelper->getBasketItems()
            ]
        );
    }

    public function finalize(Transaction $reservation, $basketId, $amount)
    {
        return $this->call(
            $reservation->method,
            'finalize',
            [
                'unique_id'       => $reservation->uniqueId,
                'order_id'        => $reservation->orderId,
                'reference'       => $reservation->reference,
                'basket_overview' => new BasketOverview([
                    'amount'   => $amount,
                    'currency' => $reservation->currency,
                ]),
                'basket_id'       => $basketId
            ]
        );
    }

    public function hybridRefund(Transaction $reservation, $amount, $basketId)
    {
        $result = $this->reversal($reservation, $amount, $basketId);
        if ($result === null || !$result->isSuccess) {
            $result = $this->refund($reservation, $amount, $basketId);
        }

        return $result;
    }

    public function reversal(Transaction $reservation, $amount, $basketId)
    {
        if ($reservation) {
            return $this->call(
                $reservation->method,
                'reversal',
                [
                    'unique_id'       => $reservation->uniqueId,
                    'order_id'        => $reservation->orderId,
                    'reference'       => $reservation->reference,
                    'basket_overview' => new BasketOverview([
                        'currency' => $reservation->currency,
                        'amount'   => $amount
                    ]),
                    'basket_id'       => $basketId
                ]
            );
        }

        return null;
    }

    public function refund(Transaction $reservation, $amount, $basketId)
    {
        if ($reservation) {
            $xmlApiClient = new XmlApiClient($reservation->method, $this->configHelper);
            if ($uniqueId = $xmlApiClient->getReceivedPaymentUniqueId($reservation->uniqueId)) {
                return $this->call(
                    $reservation->method,
                    'refund',
                    [
                        'unique_id'       => $uniqueId,
                        'order_id'        => $reservation->orderId,
                        'reference'       => $reservation->reference,
                        'basket_overview' => new BasketOverview([
                            'currency' => $reservation->currency,
                            'amount'   => $amount
                        ]),
                        'basket_id'       => $basketId
                    ]
                );
            }
        }

        return null;
    }

    public function authorizeOnRegistration(Transaction $initialize2Transaction)
    {
        return $this->call(
            $initialize2Transaction->method,
            'authorize_on_registration',
            [
                'unique_id'       => $initialize2Transaction->uniqueId,
                'reference'       => $initialize2Transaction->reference,
                'basket_overview' => new BasketOverview([
                    'currency' => $initialize2Transaction->currency,
                    'amount'   => $initialize2Transaction->amount
                ])
            ]
        );
    }

    /**
     * @param string $reference
     * @param string $type
     * @param string $status
     *
     * @return null|\SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction
     */
    public function getByReference($reference, $type = 'initialize', $status = 'success')
    {
        return $this->getTransaction('reference', $reference, $type, $status);
    }

    /**
     * @param $idField
     * @param $idValue
     * @param string $type
     * @param string $status
     *
     * @return \SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction|null
     */
    public function getTransaction($idField, $idValue, $type = 'initialize', $status = 'success')
    {
        if ($transactions = $this->getTransactions($idField, $idValue, $type, $status)) {
            return $transactions[0];
        }

        return null;
    }

    abstract function getTransactions($idField, $idValue, $type, $status);

    /**
     * @param $uniqueId
     * @param string $type
     * @param null $status
     *
     * @return null|\SantanderPaymentSolutions\SantanderPayments\Library\Struct\Transaction
     */
    public function getByUniqueId($uniqueId, $type, $status)
    {
        return self::getTransaction('unique_id', $uniqueId, $type, $status);
    }
}
