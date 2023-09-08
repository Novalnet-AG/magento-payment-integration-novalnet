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
 * If you wish to customize Novalnet payment extension for your needs, please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) 2019 Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
class Novalnet_Payment_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Get current store id
     *
     * @param  none
     * @return int
     */
    public function getMagentoStoreId()
    {
        $storeId = Mage::getModel('sales/quote')->getStoreId();
        if ($this->checkIsAdmin()) {
            $storeId = $this->getAdminCheckoutSession()->getStoreId();
        }

        return $storeId;
    }

    /**
     * Get current admin scope id
     *
     * @param none
     * @return int
     */
    public function getScopeId()
    {
        $getStore = Mage::getSingleton('adminhtml/config_data')->getStore();
        $getWebsite = Mage::getSingleton('adminhtml/config_data')->getWebsite();
        if ($getStore) {
            $scopeId = Mage::getModel('core/store')->load($getStore)->getId();
        } elseif ($getWebsite) {
            $websiteId = Mage::getModel('core/website')->load($getWebsite)->getId();
            $scopeId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getId();
        } else {
            $scopeId = 0;
        }

        return $scopeId;
    }

    /**
     * Get customer id from current session
     *
     * @param  none
     * @return int|string
     */
    public function getCustomerId()
    {
        if ($this->checkIsAdmin()) {
            $quoteCustomerNo = $this->getAdminCheckoutSession()->getQuote()->getCustomerId();
            $customerNo = $quoteCustomerNo ? $quoteCustomerNo : 'guest';
        } else {
            if ($this->getCustomerSession()->isLoggedIn()) {
                $customerNo = $this->getCustomerSession()->getCustomerId();
            } else {
                $customerNo = 'guest';
            }
        }

        return $customerNo;
    }

    /**
     * Get checkout session
     *
     * @param  none
     * @return Mage_Sales_Model_Order
     */
    public function getCheckout()
    {
        if ($this->checkIsAdmin()) {
            return $this->getAdminCheckoutSession(); // Get admin checkout session
        } else {
            return $this->getCheckoutSession(); // Get frontend checkout session
        }
    }

    /**
     * Get Magento core session
     *
     * @param  none
     * @return Mage_Core_Model_Session
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Get frontend checkout session
     *
     * @param  none
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get admin checkout session
     *
     * @param  none
     * @return Mage_adminhtml_Model_Session_quote
     */
    public function getAdminCheckoutSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Get customer session
     *
     * @param  none
     * @return Mage_customer_Model_Session
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Check whether logged in as admin
     *
     * @param  none
     * @return boolean
     */
    public function checkIsAdmin()
    {
        return (bool)(Mage::app()->getStore()->isAdmin());
    }

    /**
     * Get shop default language
     *
     * @param  none
     * @return string
     */
    public function getDefaultLanguage()
    {
        $locale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        if (!empty($locale) && is_array($locale)) {
            $locale = $locale[0];
        } else {
            $locale = 'en';
        }

        return $locale;
    }

    /**
     * Get shop base url
     *
     * @param  none
     * @return string
     */
    public function getBaseUrl()
    {
        $protocol = Mage::app()->getStore()->isCurrentlySecure() ? 'https' : 'http'; // Get protocol
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB); // Get base URL
        $secureBaseUrl = Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL);
        return ($protocol == 'https' && $secureBaseUrl)
            ? str_replace('index.php/', '', $secureBaseUrl) : str_replace('index.php/', '', $baseUrl);
    }

    /**
     * Get Remote IP address
     *
     * @param  none
     * @return int
     */
    public function getRealIpAddr()
    {
        $serverVariables = Mage::app()->getRequest()->getServer();
        $remoteAddrHeaders = array('HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');

        foreach ($remoteAddrHeaders as $header) {
            if (array_key_exists($header, $serverVariables) === true) {
                return $serverVariables[$header];
            }
        }
    }

    /**
     * Get Server IP address
     *
     * @param  none
     * @return int
     */
    public function getServerAddr()
    {
        return Mage::helper('core/http')->getServerAddr();
    }

    /**
     * Get Novalnet payment model
     *
     * @param  string $modelclass
     * @return Varien_Object
     */
    public function getPaymentModel($modelclass)
    {
        return Mage::getModel('novalnet_payment/method_' . $modelclass);
    }

    /**
     * Get Novalnet model
     *
     * @param  string $model
     * @return Varien_Object
     */
    public function getModel($model)
    {
        return Mage::getModel('novalnet_payment/' . $model);
    }

    /**
     * Get payment logo URL
     *
     * @param  none
     * @return string $imageUrl
     */
    public function getPaymentLogoUrl()
    {
        $skinBaseUrl = Mage::getBaseUrl('skin');
        return $skinBaseUrl . 'frontend/base/default/images/novalnet/';
    }

    /**
     * Get custom payment logo URL
     *
     * @param  none
     * @return string $imageUrl
     */
    public function getCustomPaymentLogoUrl()
    {
        $mediaBaseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        return $mediaBaseUrl . 'novalnet/payment/';
    }

    /**
     * Get current Magento version
     *
     * @param  none
     * @return int
     */
    public function getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * Get current Novalnet version
     *
     * @param  none
     * @return string
     */
    public function getNovalnetVersion()
    {
        $versionInfo = (string) Mage::getConfig()->getNode('modules/Novalnet_Payment/version');
        return "NN({$versionInfo})";
    }

    /**
     * Show expection
     *
     * @param  string  $text
     * @param  boolean $lang
     * @return Mage_Payment_Model_Info_Exception
     */
    public function showException($text, $lang = true)
    {
        if ($lang) {
            $text = $this->__($text);
        }

        // Exception log for reference
        Mage::log($text, null, 'nn_exception.log', true);
        // Show payment exception
        return Mage::throwException($text);
    }

    /**
     * Get Novalnet payment key
     *
     * @param  string $code
     * @return int
     */
    public function getPaymentId($code)
    {
        $getPaymentId = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('paymentKey');
        return $getPaymentId[$code];
    }

    /**
     * Get the formatted amount in Cents/Euro
     *
     * @param  float  $amount
     * @param  string $type
     * @return int
     */
    public function getFormatedAmount($amount, $type = 'CENT')
    {
        return ($type == 'RAW') ? $amount / 100 : round($amount, 2) * 100;
    }

    /**
     * Get the respective payport url
     *
     * @param  string $reqType
     * @param  string $paymentCode
     * @return string
     */
    public function getPayportUrl($reqType, $paymentCode = null)
    {
        if ($paymentCode && $reqType == 'redirect') { // For redirect payment methods
            $redirectUrl = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('redirectPayportUrl');
            $payportUrl = $redirectUrl[$paymentCode];
        } else {  // For direct payment methods
            $urlType = array(
                'paygate' => Novalnet_Payment_Model_Config::PAYPORT_URL,
                'infoport' => Novalnet_Payment_Model_Config::INFO_REQUEST_URL
            );
            $payportUrl = $urlType[$reqType];
        }

        return 'https://payport.novalnet.de/' . $payportUrl;
    }

    /**
     * Get payment method session
     *
     * @param  string $paymentCode
     * @return mixed
     */
    public function getMethodSession($paymentCode = null)
    {
        $checkoutSession = $this->getCheckout();
        if ($paymentCode != null && !$checkoutSession->hasData($paymentCode)) {
            $checkoutSession->setData($paymentCode, new Varien_Object());
        }

        return $checkoutSession->getData($paymentCode);
    }

    /**
     * Unset payment method session
     *
     * @param  string $paymentCode
     * @return none
     */
    public function unsetMethodSession($paymentCode)
    {
        $checkoutSession = $this->getCheckout();
        $checkoutSession->unsetData($paymentCode);
    }

    /**
     * Extract data from additional data array
     *
     * @param  string $info
     * @param  string $key
     * @return mixed
     */
    public function getAdditionalData($info, $key = null)
    {
        $data = array();
        if ($info->getAdditionalData()) {
            $data = unserialize($info->getAdditionalData());
        }

        return ($key && isset($data[$key])) ? $data[$key] : '';
    }

    /**
     * Replace strings from the value passed
     *
     * @param  mixed $value
     * @return integer
     */
    public function makeValidNumber($value)
    {
        return preg_replace('/[^0-9]+/', '', $value);
    }

    /**
     * Get store URL
     *
     * @param  string $route
     * @param  array  $params
     * @return string
     */
    public function getUrl($route, $params = array())
    {
        $params['_type'] = Mage_Core_Model_Store::URL_TYPE_LINK;
        if (isset($params['is_secure'])) {
            $params['_secure'] = (bool) $params['is_secure'];
        } elseif ($this->getMagentoStore()->isCurrentlySecure()) {
            $params['_secure'] = true;
        }

        return parent::_getUrl($route, $params);
    }

    /**
     * Get current store details
     *
     * @param  none
     * @return Mage_Core_Model_Store
     */
    public function getMagentoStore()
    {
        return Mage::app()->getStore();
    }

    /**
     * Get shop's date and time
     *
     * @param  none
     * @return mixed
     */
    public function getCurrentDateTime()
    {
        return Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s');
    }

    /**
     * Check the order item is nominal or not
     *
     * @param  Varien_Object $order
     * @return boolean $nominalItem
     */
    public function checkNominalItem($order)
    {
        $orderItems = $order->getAllItems();
        $nominalItem = '';

        foreach ($orderItems as $orderItemsValue) {
            if ($orderItemsValue) {
                $nominalItem = $orderItemsValue->getIsNominal();
                break;
            }
        }

        return $nominalItem;
    }

    /**
     * Assign E-mail address
     *
     * @param  string $emailaddr
     * @param  mixed  $mail
     * @param  string $addr
     * @return string
     */
    public function assignEmailAddress($emailaddr, $mail, $addr)
    {
        $email = explode(',', $emailaddr);
        $emailValidator = new Zend_Validate_EmailAddress();

        foreach ($email as $emailAddrVal) {
            if ($emailValidator->isValid(trim($emailAddrVal))) {
                ($addr == 'To') ? $mail->addTo($emailAddrVal) : $mail->addBcc($emailAddrVal);
            }
        }

        return $mail;
    }

    /**
     * Get End customer company value
     *
     * @param  none
     * @return string
     */
    public function getEndUserCompany()
    {
        $billingInfo = $this->getCheckout()->getQuote()->getBillingAddress();
        $shipping = !$this->getCheckout()->getQuote()->getIsVirtual() ? $this->getCheckout()->getQuote()->getShippingAddress() : '';
        return $billingInfo->getCompany() ? $billingInfo->getCompany()
            : ($shipping && $shipping->getCompany() ? $shipping->getCompany() : '');
    }

    /**
     * Check whether string is serialized
     *
     * @param  mixed $data
     * @return boolean
     */
    public function is_serialized($data)
    {
        $data = trim($data);
        if ($data == 'N;') {
            return true;
        }

        $lastChar = substr($data, -1);
        if (!is_string($data) || strlen($data) < 4 || $data[1] !== ':'
            || ($lastChar !== ';' && $lastChar !== '}')) {
            return false;
        }

        $token = $data[0];
        switch ($token) {
            case 's' :
                if (substr($data, -2, 1) !== '"') {
                    return false;
                }

            case 'a' :
            case 'O' :
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
            case 'b' :
            case 'i' :
            case 'd' :
                return (bool) preg_match("/^{$token}:[0-9.E-]+;$/", $data);
        }

        return false;
    }

}
