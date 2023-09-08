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
class Novalnet_Payment_Model_Service_Abstract
{

    /**
     * Helper
     */
    protected $_helper;

    /**
     * Storeid
     */
    protected $_storeId;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Payment method code
        $this->code = Mage::registry('payment_code');
        // Assign Novalnet helper
        $this->assignUtilities();
        // Assign basic vendor informations
        $this->setVendorInfo();
    }

    /**
     * Assign utilities (Novalnet payment helper)
     *
     * @param  none
     * @return Novalnet helper
     */
    public function assignUtilities()
    {
        // Assign helper
        if (!$this->_helper) {
            $this->_helper = Mage::helper('novalnet_payment');
        }

        // Assign store id
        if (!$this->_storeId) {
            $this->_storeId = $this->_helper->getMagentoStoreId();
        }
    }

    /**
     * Set merchant configuration details
     *
     * @param  none
     * @return none
     */
    protected function setVendorInfo()
    {
        $this->vendorId = $this->getNovalnetConfig('merchant_id', true);
        $this->authcode = $this->getNovalnetConfig('auth_code', true);
        $this->productId = $this->getNovalnetConfig('product_id', true);
        $this->tariffId = $this->getNovalnetConfig('tariff_id', true);
        $this->recurringTariffId = $this->getNovalnetConfig('subscrib_tariff_id', true);
        $this->accessKey = $this->getNovalnetConfig('password', true);
        $this->loadAffiliateInfo(); // Re-assign merchant params based on affiliate
    }

    /**
     * Get affiliate account/user detail
     *
     * @param  null
     * @return mixed
     */
    public function loadAffiliateInfo()
    {
        $affiliateId = $this->_helper->getCoreSession()->getAffiliateId(); // Get affiliate user id if exist
        $customerId = $this->_helper->getCustomerId(); // Get current customer id

        if (!$affiliateId && $customerId != 'guest') { // Get affiliate id for existing customer (if available)
            $collection = $this->_helper->getModel('Mysql4_AffiliateUser')->getCollection()
                ->addFieldToFilter('customer_no', $customerId)
                ->addFieldToSelect('aff_id');
            $affiliateId = $collection->getLastItem()->getAffId() ? $collection->getLastItem()->getAffId() : null;
            $this->_helper->getCoreSession()->setAffiliateId($affiliateId);
        }

        if ($affiliateId) { // Get affiliate configuration values (if affiliate user id exist)
            $orderCollection = $this->_helper->getModel('Mysql4_AffiliateInfo')->getCollection()
                ->addFieldToFilter('aff_id', $affiliateId)
                ->addFieldToSelect('aff_authcode')
                ->addFieldToSelect('aff_accesskey');
            if ($orderCollection->getLastItem()->getAffAuthcode()
                && $orderCollection->getLastItem()->getAffAccesskey()) {
                $this->vendorId = $affiliateId;
                $this->authcode = $orderCollection->getLastItem()->getAffAuthcode();
                $this->accessKey = $orderCollection->getLastItem()->getAffAccesskey();
            }
        }
    }

    /**
     * Get the Novalnet configuration (global/payment)
     *
     * @param  string  $field
     * @param  boolean $global
     * @return mixed|null
     */
    public function getNovalnetConfig($field, $global = false)
    {
        $path = 'novalnet_global/novalnet/' . $field; // Global config value path

        if ($field == 'live_mode') { // Novalnet payment mode
            $paymentMethod = Mage::getStoreConfig($path, $this->_storeId);
            return (!preg_match('/' . $this->code . '/i', $paymentMethod)) ? false : true;
        } elseif ($field !== null) {  // Get Novalnet payment/global configuration
            return ($global != false)
                ? trim(Mage::getStoreConfig($path, $this->_storeId))
                : trim(Mage::getStoreConfig('payment/' . $this->code . '/' . $field, $this->_storeId));
        }

        return null;
    }

    /**
     * Whether current operation is order placement
     *
     * @param  Varien_Object $info
     * @return boolean
     */
    protected function _isPlaceOrder($info)
    {
        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            return false;
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) {
            return true;
        }
    }

    /**
     * Get current order/quote object
     *
     * @param  Varien_Object $info
     * @return Mage_Payment_Model_Method_Abstract
     */
    protected function _getInfoObject($info)
    {
        return ($this->_isPlaceOrder($info)) ? $info->getOrder() : $info->getQuote();
    }

    /**
     * Check the email id is valid
     *
     * @param  mixed $emailId
     * @return boolean
     */
    public function validateEmail($emailId)
    {
        $validatorEmail = new Zend_Validate_EmailAddress();
        return (bool)($validatorEmail->isValid($emailId));
    }

    /**
     * Check the value contains special characters
     *
     * @param  mixed $value
     * @return boolean
     */
    public function checkIsValid($value)
    {
        return (!$value || preg_match('/[#%\^<>@$=*!]/', $value)) ? false : true;
    }

    /**
     * Get grand total amount
     *
     * @param  Varien_Object $info
     * @return double
     */
    protected function _getAmount($info)
    {
        return ($this->_isPlaceOrder($info))
            ? (double) $info->getOrder()->getBaseGrandTotal() : (double) $info->getQuote()->getBaseGrandTotal();
    }

    /**
     * Get order increment id
     *
     * @param  none
     * @return int
     */
    protected function _getIncrementId()
    {
        $storeId = $this->_helper->getMagentoStoreId(); // Get store id
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToFilter('store_id', $storeId)
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1); // Get order collection
        $lastIncrementId = $orders->getFirstItem()->getIncrementId();
        return !empty($lastIncrementId)
            ? ++$lastIncrementId : $storeId . Mage::getModel('eav/entity_increment_numeric')->getNextId();
    }

    /**
     * Encoded the requested data
     *
     * @param  string $data
     * @return string
     */
    public function encode($data, $uniqid)
    {
        $data = trim($data);
        if ($data == null) {
            return'Error: no data';
        }

        try {
            return htmlentities(base64_encode(openssl_encrypt($data, "aes-256-cbc", $this->accessKey, true, $uniqid)));
        } catch (Exception $e) {
            Mage::logException('Error: ' . $e);
        }
    }

    /**
     * Decoded the requested data
     *
     * @param  string $data
     * @return string
     */
    public function decode($data, $uniqid)
    {
        $data = trim($data);
        if ($data == null) {
            return'Error: no data';
        }

        try {
            return openssl_decrypt(base64_decode($data), "aes-256-cbc", $this->accessKey, true, $uniqid);
        } catch (Exception $e) {
            Mage::logException('Error: ' . $e);
        }
    }

    /**
     * Hash value getter
     *
     * @param  array $request
     * @return string
     */
    public function getHash($request)
    {
        if (empty($request)) {
            return'Error: no data';
        }

        // Hash generation using sha256 and encoded merchant details
        return hash('sha256',
            ($request->getAuthCode() . $request->getProduct() . $request->getTariff() .
            $request->getAmount() . $request->getTestMode() . $request->getUniqid() . strrev($this->accessKey))
        );
    }

    /**
     * Check Hash value
     *
     * @param  array $response
     * @return boolean
     */
    public function checkHash($response)
    {
        if (!$response) {
            return false;
        }

        if ($response->hasHash2() && $response->getHash2() != $this->getHash($response)
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get encoded payment data
     *
     * @param  Varien_Object $request
     * @return none
     */
    public function getEncodedParams($request)
    {
        $params = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('hashParams');
        $uniqid = $request->getUniqid();

        foreach ($params as $value) {
            $data = $request->$value;
            $data = $this->encode($data, $uniqid);
            $request->$value = $data;
        }

        $request->setHash($this->getHash($request));
    }

    /**
     * Get decoded payment data
     *
     * @param  Varien_Object $response
     * @param  int|null      $storeId
     * @return none
     */
    public function getDecodedParams($response, $storeId = null)
    {
        $this->_storeId = ($storeId !== null) ? $storeId : $this->_storeId;
        $params = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('hashParams');
        $uniqid = $response->getUniqid();

        foreach ($params as $value) {
            $data = $response->$value;
            $data = $this->decode($data, $uniqid);
            $response->$value = $data;
        }
    }

}
