<?php

/**
 * Novalnet payment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Novalnet End User License Agreement
 * that is bundled with this package in the file freeware_license_agreement.txt
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet payment extension for your needs, 
 * please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) 2019 Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
class Novalnet_Payment_Model_Service_Api_Gateway extends Novalnet_Payment_Model_Service_Abstract
{

    /**
     * Send API request to Novalnet gateway
     *
     * @param  array       $requestData
     * @param  string      $requestUrl
     * @param  string|null $type
     * @return mixed
     */
    public function payportRequestCall($requestData, $requestUrl, $type = null)
    {
        if (!$requestUrl) {
            $this->_helper->showException('Server Request URL is Empty', false);
        }

        $httpClientConfig = array('maxredirects' => 0);
        $client = new Varien_Http_Client($requestUrl, $httpClientConfig);
        // Assign post payport params
        if ($type == 'XML') {
            $client->setUri($requestUrl);
            $client->setRawData($requestData)->setMethod(Varien_Http_Client::POST);
        } else {
            $client->setParameterPost($requestData)->setMethod(Varien_Http_Client::POST);
        }

        // Get response from payment gateway
        try {
            $response = $client->request();
        } catch (Exception $e) {
            $this->_helper->showException($e->getMessage(), false);
        }

        // Show exception if payment unsuccessful
        if (!$response->isSuccessful()) {
            $this->_helper->showException($this->_helper->__('Gateway request error: %s', $response->getMessage()), false);
        }

        // Convert xml response to array
        if ($type == 'XML') {
            $result = new Varien_Simplexml_Element($response->getRawBody());
            $response = new Varien_Object($result->asArray());
        }

        return $response;
    }
}
