<?php

namespace SantanderPaymentSolutions\SantanderPayments\Controller\Callback;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use SantanderPaymentSolutions\SantanderPayments\Helper\IntegrationHelper;
use SantanderPaymentSolutions\SantanderPayments\Helper\TransactionHelper;

class Index extends Action
{
    protected $_pageFactory;
    private $integrationHelper;
    private $transactionHelper;
    private $context;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        IntegrationHelper $integrationHelper,
        TransactionHelper $transactionHelper)
    {
        $this->_pageFactory = $pageFactory;
        $this->context = $context;
        $this->integrationHelper = $integrationHelper;
        $this->transactionHelper = $transactionHelper;
        return parent::__construct($context);
    }

    public function execute()
    {

        $vars = $this->context->getRequest()->getPostValue();

        $this->integrationHelper->log('success', __METHOD__ . '::' . __LINE__, 'execute callback controller', ['vars'=>$vars, 'get'=>$_GET, 'post'=>$_POST, 'server'=>$_SERVER]);
        //$vars = json_decode('{"NAME_FAMILY":"Ampel","CRITERION_SDK_NAME":"Heidelpay\\\\PhpPaymentApi","IDENTIFICATION_TRANSACTIONID":"santander_magento2_5c6eccea42988","ADDRESS_COUNTRY":"DE","ADDRESS_STREET":"Lichtweg 2","CONNECTOR_ACCOUNT_BANK":"31010833","NAME_BIRTHDATE":"1950-01-01","FRONTEND_ENABLED":"TRUE","PRESENTATION_AMOUNT":"61.99","CUSTOMER_OPTIN_2":"true","TRANSACTION_MODE":"CONNECTOR_TEST","CONTACT_IP":"93.102.196.253","CRITERION_SDK_VERSION":"v1.7.0","PROCESSING_TIMESTAMP":"2019-02-21 16:12:17","CONTACT_EMAIL":"gruene@ampeltesting.de","FRONTEND_RESPONSE_URL":"http:\/\/kreusch-creative.de\/santander\/","REQUEST_VERSION":"1.0","BASKET_ID":"31HA07BC81857036923529E4F6078EC2","ACCOUNT_BRAND":"SANTANDER","PROCESSING_STATUS_CODE":"90","NAME_GIVEN":"Gren","CONNECTOR_ACCOUNT_BIC":"SCFBDE33XXX","IDENTIFICATION_SHORTID":"4146.9193.7662","ADDRESS_CITY":"Laterne","CLEARING_AMOUNT":"61.99","PROCESSING_CODE":"IV.PA.90.00","PROCESSING_STATUS":"NEW","SECURITY_SENDER":"31HA07BC8142C5A171745D00AD63D182","USER_LOGIN":"31ha07bc8142c5a171744e5aef11ffd3","CONNECTOR_ACCOUNT_HOLDER":"Santander","CUSTOMER_OPTIN":"FALSE","USER_PWD":"93167DE7","IDENTIFICATION_SHOPPERID":"0","PROCESSING_RETURN_CODE":"000.100.112","NAME_SALUTATION":"MR","CONNECTOR_ACCOUNT_COUNTRY":"DE","CRITERION_PAYMENT_METHOD":"SantanderInvoicePaymentMethod","PROCESSING_RESULT":"ACK","CLEARING_CURRENCY":"EUR","IDENTIFICATION_CREDITOR_ID":"DE87ZZZ00000019937","FRONTEND_MODE":"WHITELABEL","IDENTIFICATION_UNIQUEID":"31HA07BC818570369235A573FA395699","CRITERION_SECRET":"cbae0d6dc5d11a10fdee76e3b15e9d11484abaf8f480355a36b0e68e0e0b1e4a5b529784c20fd9054cccef878f911471c4cccd082a86c9997d1d020a70c557d9","PRESENTATION_CURRENCY":"EUR","PROCESSING_REASON_CODE":"00","lang":"DE","ADDRESS_STATE":"DE-BW","ADDRESS_ZIP":"12345","CLEARING_DESCRIPTOR":"4146.9193.7662 1593-Standard-Test-Merchant ","CONNECTOR_ACCOUNT_NUMBER":"8810076120","PROCESSING_REASON":"SUCCESSFULL","CRITERION_INSURANCE-RESERVATION":"ACCEPTED","PROCESSING_RETURN":"Request successfully processed in ","TRANSACTION_CHANNEL":"31HA07BC81856CAD6D8E07858ACD6CFB","CONNECTOR_ACCOUNT_IBAN":"DE89310108338810076120","FRONTEND_LANGUAGE":"DE","PAYMENT_CODE":"IV.PA","CONNECTOR_ACCOUNT_USAGE":"76102046122803"}', true);
        if(!empty($vars["action"]) && $vars["action"] == 'reauthorize_invoice'){
            $response = $this->transactionHelper->authorize('invoice');
            $return = ['success'=>0];
            if($response["isSuccess"]) {
                $return["success"] = 1;
                $return["redirect_url"] = $response["response"]["frontend"]["redirect_url"];
                $this->integrationHelper->setLastReference($response["response"]["identification"]["transactionid"]);
            }
            echo json_encode($return);
            die;
        }elseif(!empty($vars["action"]) && $vars["action"] == 'initialize_hire'){
            $response = $this->transactionHelper->initialize('hire', $vars["NAME_BIRTHDATE"]);
            $return = ['success'=>0];
            if($response["isSuccess"]) {
                $return["success"] = 1;
                $return["redirect_url"] = $response["response"]["frontend"]["redirect_url"];
                $this->integrationHelper->setLastReference($response["response"]["identification"]["transactionid"]);
            }
            echo json_encode($return);
            die;
        }elseif(!empty($vars["action"]) && $vars["action"] == 'authorize_on_registration'){
            $return = ['success'=>0];
            if($cReference = $this->integrationHelper->getLastReference()) {
                $transaction = $this->transactionHelper->getTransaction([
                    ['field' => 'reference', 'value' => $cReference],
                    ['field' => 'status', 'value' => 'success'],
                    ['field' => 'type', 'value' => 'initialize_2']
                ]);
                $response = $this->transactionHelper->authorizeOnRegistration($transaction);

                if ($response["isSuccess"]) {
                    $return["success"] = 1;
                    $return["redirect_url"] = $response["response"]["frontend"]["redirect_url"];
                    $this->integrationHelper->setLastReference($response["response"]["identification"]["transactionid"]);
                }
            }
            echo json_encode($return);
            die;
        }

        elseif (!empty($vars["IDENTIFICATION_TRANSACTIONID"])) {
            $this->ipnAction($vars);
        } else {
            $this->customerFrontendAction($vars);
        }
        //$integrationHelper->scheduleNotification($checkoutHelper->getGeneralErrorMessage());
        //return $this->response->redirectTo('checkout');
        return $this->_pageFactory->create();
    }

    private function ipnAction($vars)
    {
        $this->integrationHelper->log('info', __CLASS__ . '..' . __METHOD__ . '::' . __LINE__, 'IPN received', $vars);
        $reference = $vars["IDENTIFICATION_TRANSACTIONID"];
        $initialTransaction = $this->transactionHelper->getTransaction([
            ['field' => 'type', 'value' => 'initialize'],
            ['field' => 'reference', 'value' => $reference]
        ]);

        if ($initialTransaction) {
            if (round($initialTransaction->amount, 2) == round($vars["PRESENTATION_AMOUNT"], 2)) {
                if ($vars["PAYMENT_CODE"] === 'HP.PA') {
                    $openReservation = $this->transactionHelper->getTransaction([
                        ['field' => 'type', 'value' => 'reservation'],
                        ['field' => 'reference', 'value' => $reference]
                    ]);
                    if ($openReservation) {
                        $openReservation->status = ($vars["PROCESSING_RESULT"] === 'ACK' ? 'success' : 'error');
                        $openReservation->response = json_encode($vars);
                        if ($vars["IDENTIFICATION_UNIQUEID"]) {
                            $openReservation->uniqueId = $vars["IDENTIFICATION_UNIQUEID"];
                        }
                        $this->integrationHelper->log('info', __CLASS__ . '..' . __METHOD__ . '::' . __LINE__, 'update transaction', $vars);
                        $this->transactionHelper->saveTransaction($openReservation);
                    }
                } else {
                    $rawResponse = $vars;
                    $transaction = $this->transactionHelper->createTransaction();
                    $transaction->amount = $initialTransaction->amount;
                    $transaction->method = $initialTransaction->method;
                    $transaction->type = ($vars["PAYMENT_CODE"] === 'HP.IN' ? 'initialize_2' : 'reservation');
                    $transaction->reference = $reference;
                    $transaction->sessionId = $initialTransaction->sessionId;
                    $transaction->createDatetime = date('Y-m-d H:i:s');
                    $transaction->status = $vars["PROCESSING_RESULT"] === 'ACK' ? 'success' : 'error';
                    $transaction->response = json_encode($rawResponse);
                    $transaction->customerId = $initialTransaction->customerId;
                    $transaction->currency = $initialTransaction->currency;
                    if ($vars["IDENTIFICATION_UNIQUEID"]) {
                        $transaction->uniqueId = $vars["IDENTIFICATION_UNIQUEID"];
                    }
                    $this->transactionHelper->saveTransaction($transaction);
                }
            }
        }
        if ($vars["PROCESSING_RESULT"] !== 'ACK') {
            $this->integrationHelper->log('error', __CLASS__ . '..' . __METHOD__ . '::' . __LINE__, 'IPN error', $vars);
        }
        echo 'IPN DONE';die;
    }

    private function customerFrontendAction($vars)
    {
        $return = ['success'=>0];
        $this->integrationHelper->log('info', __CLASS__ . '..' . __METHOD__ . '::' . __LINE__, 'customer frontend action', $vars);

        if($cReference = $this->integrationHelper->getLastReference()) {
            $return['reference']=$cReference;
            $transaction = $this->transactionHelper->getTransaction([
                ['field' => 'reference', 'value' => $cReference]
            ]);

            $method = $transaction->method;

            $reservationTransaction = $this->transactionHelper->getTransaction([
                ['field' => 'type', 'value' => 'reservation'],
                ['field' => 'reference', 'value' => $cReference]
            ]);

            if ($reservationTransaction && $reservationTransaction->status === 'success') {
                $return["success"] = 1;
            } else {
                if ($method === 'hire') {
                    $initialize2Transaction = $this->transactionHelper->getTransaction([
                        ['field' => 'type', 'value' => 'initialize_2'],
                        ['field' => 'reference', 'value' => $cReference]
                    ]);
                    if ($initialize2Transaction && $initialize2Transaction->status === 'success') {
                        $this->integrationHelper->setLastReference($initialize2Transaction->reference);
                        $response = json_decode($initialize2Transaction->response, true);
                        echo '<script>setInterval(function(){try{window.santanderHireFinishedPaymentPlan(true, "'.$response["CRITERION_SANTANDER_HP_PDF_URL"].'");}catch(err){}}, 10);</script>';
                        die;
                    }
                    echo '<script>window.close()</script>';
                    die;
                }
            }
        }
        echo json_encode($return);
        die;
    }
}