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
class Novalnet_Payment_Block_Method_Form_Cc extends Mage_Payment_Block_Form
{
    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('novalnet/method/form/Cc.phtml');
    }

    /**
     * Retrieve availables Credit Card types
     *
     * @param  none
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = array();
        $defaultTypes = array(
            'VI' => 'Visa',
            'MC' => 'MasterCard'
        );
        $availableTypes = Mage::getStoreConfig('payment/novalnetCc/cc_types');
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
            foreach ($availableTypes as $code => $name) {
                $types[$name] = $name;
            }
        }
        return array_merge($defaultTypes,$types);
    }

    /**
     * Get information to end user from config
     *
     * @param  none
     * @return string
     */
    public function getUserInfo()
    {
        return trim(strip_tags($this->getMethod()->getConfigData('booking_reference')));
    }

    /**
     * Get payment logo available status
     *
     * @param  none
     * @return boolean
     */
    public function logoAvailableStatus()
    {
        return Mage::getStoreConfig('novalnet_global/novalnet/enable_payment_logo');
    }

    /**
     * Get payment mode (test/live)
     *
     * @param  none
     * @return boolean
     */
    public function getPaymentMode()
    {
        $paymentMethod = Mage::getStoreConfig('novalnet_global/novalnet/live_mode');
        return (!preg_match('/' . $this->getMethodCode() . '/i', $paymentMethod)) ? false : true;
    }

    /**
     * Get form style configuration
     *
     * @param string $param
     * @return string
     */
    public function getStyleConfig($param)
    {
        $creditCardStyle = unserialize($this->getMethod()->getConfigData('cc_style'));
        if (isset($creditCardStyle[$param])) {
            return $creditCardStyle[$param];
        }

        return '';
    }

    /**
     * Get payment one click shopping
     *
     * @param  none
     * @return boolean
     */
    public function getOneClickShopping()
    {
        $isCustomerLoggedIn = Mage::helper('novalnet_payment')->getCustomerSession()->isLoggedIn();
        return $this->getMethod()->getConfigData('cc_shop_type') && $isCustomerLoggedIn;
    }

    /**
     * Get client key
     *
     * @param  none
     * @return string
     */    
    public function getClientKey()
    {
        $clientKey = Mage::getStoreConfig('novalnet_global/novalnet/client_key');
        return $clientKey;
    }
    
    /**
     * Get creditcard form layout
     *
     * @param  none
     * @return boolean
     */    
    public function getCcFormLayout()
    {
        return $this->getMethod()->getConfigData('inline_form');
    }

    /**
     * Get payment request object
     *
     * @param  none
     * @return array
     */
    public function getCcRequestObj()
    {
        $quote = Mage::helper('novalnet_payment')->checkIsAdmin() ?
            Mage::getSingleton('adminhtml/session_quote')->getQuote() :
            Mage::helper('checkout/cart')->getCart()->getQuote();

        $paymentMethod = Mage::getStoreConfig('novalnet_global/novalnet/live_mode');
        $testMode = (!preg_match('/' . $this->getMethodCode() . '/i', $paymentMethod)) ? 1 : 0;
        $recurringAmount = 0;
        foreach($quote->getAllItems() as $item) {
            if($item->isNominal()) {
                $recurringAmount = $item->getNominalRowTotal();
            }
        }
        //get Customer address from quote
        $customer['first_name'] = $quote->getBillingAddress()->getFirstname();
        $customer['last_name'] = $quote->getBillingAddress()->getLastname();
        $customer['email'] = $quote->getBillingAddress()->getEmail();
        $customer['billing']['street'] = (implode(',', $quote->getBillingAddress()->getStreet()));
        $customer['billing']['city'] = $quote->getBillingAddress()->getCity();
        $customer['billing']['zip'] = $quote->getBillingAddress()->getPostcode();
        $customer['billing']['country_code'] = $quote->getBillingAddress()->getCountryId();
        $customer = $this->getShippingAddress($quote, $customer);
        //get transction params
        $transaction['amount'] = ($recurringAmount) ? $this->getFormatedAmount($recurringAmount) : $this->getFormatedAmount($quote->getBaseGrandTotal());
        $transaction['test_mode'] = $testMode;
        $transaction['currency'] = $quote->getBaseCurrencyCode();
        $transaction['enforce_3d'] = ($this->getMethod()->getConfigData('enforce_3d')) ? 1 : 0;
        $data = ['customer' => $customer, 'transaction' => $transaction];
        
        return json_encode($data);
    }
    
    /**
     * Address validation for guarantee payments
     *
     * @param  Varien_Object $quote
     * @return boolean
     */
    public function getShippingAddress($quote, $customer)
    {
        $billing = $quote->getBillingAddress(); // Get end-customer billing address
        $shipping = !$quote->getIsVirtual() ? $quote->getShippingAddress() : ''; // Get end-customer shipping address
        
        if ($shipping == '') {
            $customer['shipping']['same_as_billing'] = 1;
        } elseif (($billing->getCity() != $shipping->getCity())
            || (implode(',', $billing->getStreet()) != implode(',', $shipping->getStreet()))
            || ($billing->getPostcode() != $shipping->getPostcode())
            || ($billing->getCountry() != $shipping->getCountry())
        ) {
            $customer['shipping']['street'] = (implode(',', $quote->getShippingAddress()->getStreet()));
            $customer['shipping']['city'] = $quote->getShippingAddress()->getCity();
            $customer['shipping']['zip'] = $quote->getShippingAddress()->getPostcode();
            $customer['shipping']['country_code'] = $quote->getShippingAddress()->getCountryId();
        } else {
            $customer['shipping']['same_as_billing'] = 1;
        }
        return $customer;
    }
    
    /**
     * get formated amount
     *
     * @param $amount, $type
     * @return int
     */
    public function getFormatedAmount($amount, $type = 'CENT')
    {
        return ($type == 'RAW') ? $amount / 100 : round($amount, 2) * 100;
    }
}
