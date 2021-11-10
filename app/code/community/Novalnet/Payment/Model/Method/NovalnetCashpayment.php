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
class Novalnet_Payment_Model_Method_NovalnetCashpayment extends Novalnet_Payment_Model_Method_Abstract
{

    protected $_code = Novalnet_Payment_Model_Config::NN_CASHPAYMENT;
    protected $_canAuthorize = Novalnet_Payment_Model_Config::NN_CASHPAYMENT_CAN_AUTHORIZE;
    protected $_formBlockType = Novalnet_Payment_Model_Config::NN_CASHPAYMENT_FORM_BLOCK;
    protected $_infoBlockType = Novalnet_Payment_Model_Config::NN_CASHPAYMENT_INFO_BLOCK;

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if (!empty($quote) && $quote->hasNominalItems()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Authorize
     *
     * @param   Varien_Object $payment
     * @param float $amount
     * @return  Novalnet_Payment_Model_Method_Abstract
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        if ($this->canAuthorize()) {
            $methodSession = $this->helper->getMethodSession($this->_code); // Get payment method session
            $request = $methodSession->getPaymentReqData(); // Get Novalnet payment request data
            $response = $this->postRequest($request); // Send payment request to Novalnet gateway
            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->validateResponse($payment, $request, $response); // Validate Novalnet payment response
        }

        return $this;
    }

}
