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
class Novalnet_Payment_Model_Method_NovalnetPaypal extends Novalnet_Payment_Model_Method_Abstract
{

    protected $_code = Novalnet_Payment_Model_Config::NN_PAYPAL;
    protected $_canAuthorize = Novalnet_Payment_Model_Config::NN_PAYPAL_CAN_AUTHORIZE;
    protected $_canUseInternal = Novalnet_Payment_Model_Config::NN_PAYPAL_CAN_USE_INTERNAL;
    protected $_formBlockType = Novalnet_Payment_Model_Config::NN_PAYPAL_FORM_BLOCK;
    protected $_infoBlockType = Novalnet_Payment_Model_Config::NN_PAYPAL_INFO_BLOCK;

    /**
     * Assign data to info model instance
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if ($data) {
            if (!($data instanceof Varien_Object)) {
                $data = new Varien_Object($data);
            }

            $methodSession = $this->helper->getMethodSession($this->_code); // Get payment method session

            if ($this->getFormValues('paypal_oneclick_shopping') && $this->getFormValues('paypal_ref_trans')) {
                $transAccInfo = $this->getExistingTransInfo();
                $methodSession->setNnRefTid($transAccInfo['nn_tid']);
            } else {
                $methodSession->unsNnRefTid();
                $methodSession->unsNnPaypalSaveAccount();
                if ($this->getFormValues('nn_paypal_save_account')) {
                    $methodSession->setNnPaypalSaveAccount(true);
                }
            }
        }

        return $this;
    }

    /**
     * Get the paypal informations
     *
     * @param  string $param
     * @return string
     */
    public function getFormValues($param)
    {
        return Mage::app()->getRequest()->getPost($param);
    }

    /**
     * Get Novalnet payment redirect URL
     *
     * @param  none
     * @return string $actionUrl
     */
    public function getOrderPlaceRedirectUrl()
    {
        $methodSession = $this->helper->getMethodSession($this->_code); // Get payment method session

        if ($methodSession->hasPaymentReqData()) {
            return $this->helper->getUrl(Novalnet_Payment_Model_Config::GATEWAY_REDIRECT_URL);
        }
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
        $methodSession = $this->helper->getMethodSession($this->_code); // Get payment method session
        if ($this->canAuthorize() && $methodSession->getPaymentReqData()->hasPaymentRef()) {
            $request = $methodSession->getPaymentReqData(); // Get Novalnet payment request data
            $response = $this->postRequest($request); // Send payment request to Novalnet gateway
            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->validateResponse($payment, $request, $response); // Validate Novalnet payment response
        }

        return $this;
    }

}
