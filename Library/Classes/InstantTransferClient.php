<?php

namespace SantanderPaymentSolutions\SantanderPayments\Library\Classes;

use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface;
use Exception;

class InstantTransferClient
{
    /**
     * @var string URL
     */
    const URL = 'https://api.xs2a.com/v1/payments';

    /**
     * @var string USER_NAME
     */
    const USER_NAME = 'api';

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $iban;

    /**
     * @var string
     */
    protected $accountHolder;

    /**
     * @var string
     */
    protected $subjectRaw;

    /**
     * InstantTransferClient constructor.
     *
     * @param \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface $configHelper
     *
     * @throws \Exception
     */
    public function __construct(ConfigHelperInterface $configHelper)
    {
        if (!($this->apiKey = $configHelper->get('instant/api_key'))) {
            throw new Exception('Instant transfer api key is empty');
        }
        if (!($this->iban = $configHelper->get('instant/iban'))) {
            throw new Exception('Instant transfer iban is empty');
        }
        if (!($this->accountHolder = $configHelper->get('instant/account_holder'))) {
            throw new Exception('Instant transfer account holder is empty');
        }
        if (!($this->subjectRaw = $configHelper->get('instant/subject'))) {
            $this->subjectRaw = 'Order %order_id%';
        }
    }

    /**
     * @param $orderId
     * @param $amount
     * @param string $currency
     *
     * @param null $name
     * @param null $address
     * @param null $emailAndPhone
     *
     * @return array
     * @throws \Exception
     */
    public function getSession($orderId, $amount, $currency = 'EUR', $name = null, $address = null, $emailAndPhone = null)
    {
        $metaData = [];
        if ($name) {
            $metaData['name'] = $name;
        }
        if ($address) {
            $metaData['address'] = $address;
        }
        if ($emailAndPhone) {
            $metaData['mail_phone'] = $emailAndPhone;
        }
        $payload = [
            'amount'           => round($amount, 2),
            'currency_id'      => $currency,
            'purpose'          => str_replace('%order_id%', $orderId, $this->subjectRaw),
            'recipient_iban'   => $this->iban,
            'recipient_holder' => $this->accountHolder
        ];
        if ($metaData) {
            $payload['metadata'] = array_map(function ($str) {
                return substr($str, 0, 120);
            }, $metaData);
        }

        return $this->send('', $payload);
    }

    /**
     * @param string $method
     * @param array $payload
     *
     * @return array
     * @throws \Exception
     */
    private function send($method, $payload = null)
    {

        $request = curl_init();
        curl_setopt($request, CURLOPT_USERPWD, self::USER_NAME . ':' . $this->apiKey);
        curl_setopt($request, CURLOPT_URL, self::URL . $method);
        curl_setopt($request, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($request, CURLOPT_HEADER, false);
        curl_setopt($request, CURLOPT_FAILONERROR, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 60);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 60);
        if (!empty($payload)) {
            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_USERAGENT, 'PatworxSantander');
        $response = curl_exec($request);
        if (!($response = json_decode($response, true))) {
            $errors = curl_error($request);
            throw new Exception('XS2A API Error: ' . print_r($errors, true));
        }

        return $response;
    }

    public function getTransactionDetails($transactionId)
    {
        return $this->send('/' . $transactionId);
    }

}