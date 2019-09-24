<?php
namespace SantanderPaymentSolutions\SantanderPayments\Library\Classes;

use SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface;

class XmlApiClient{
    /**
     * @var string URL for the test system
     */
    const URL_TEST = 'https://test-heidelpay.hpcgw.net/TransactionCore/xml';

    /**
     * @var string URL for the live system
     */
    const URL_LIVE = 'https://heidelpay.hpcgw.net/TransactionCore/xml';

    /**
     * @var \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface
     */
    protected $configHelper;
    protected $method;


    /**
     * XmlApiClient constructor.
     *
     * @param string $method
     * @param \SantanderPaymentSolutions\SantanderPayments\Library\Interfaces\ConfigHelperInterface $configHelper
     */
    public function __construct($method, ConfigHelperInterface $configHelper)
    {
        $this->configHelper = $configHelper;
        $this->method = $method;
    }

    private function getURL()
    {
        return $this->isLive() ? self::URL_LIVE : self::URL_TEST;
    }

    private function isLive(){
        return $this->configHelper->isLive($this->method);
    }

    public function getReceivedPaymentUniqueId($uniqueId)
    {
        $auth = $this->configHelper->getAuth($this->method);
        $xml_request = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                        <Request version="1.0">
                               <Header>
                                     <Security sender="' . $auth['sender_id'] . '" />
                               </Header>
                               <Query entity="' . $auth['channel'] . '" level="CHANNEL"
                                     mode="' . ($this->isLive() ? 'LIVE' : 'CONNECTOR_TEST') . '" type="LINKED_TRANSACTIONS">
                                     <User login="' . $auth['login'] . '" pwd="' . $auth['password'] . '" /> 
                                    <Methods>
                                        <Type>'.($this->method === 'invoice'?'IV':'HP').'</Type> 
                                    </Methods>
                                    <Identification>
                                        <UniqueID>' . $uniqueId . '</UniqueID>
                                    </Identification>           
                                    <Types>
                                        <Type code="RC"/>
                                    </Types> 
                               </Query>
                        </Request>';
        $response = $this->sendPost($xml_request);
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        $responseArray = json_decode(json_encode($xml),TRUE);
        if (isset($responseArray['Result']['Transaction']['Identification']['UniqueID'])) {
            return $responseArray['Result']['Transaction']['Identification']['UniqueID'];
        }
        return false;
    }

    private function sendPost($xml_request)
    {
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $this->getURL());
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_FAILONERROR, true);
        curl_setopt($request, CURLOPT_TIMEOUT, 60);
        curl_setopt($request, CURLOPT_CONNECTTIMEOUT, 60);

        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query(['load' => $xml_request]));

        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($request, CURLOPT_SSLVERSION, 6);
        curl_setopt($request, CURLOPT_USERAGENT, 'PatworxSantander');
        $response = curl_exec($request);
        curl_error($request);
        curl_getinfo($request, CURLINFO_HTTP_CODE);
        return $response;
    }
}
