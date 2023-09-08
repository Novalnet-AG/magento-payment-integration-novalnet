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
class Novalnet_Payment_Model_Method_NovalnetCc extends Novalnet_Payment_Model_Method_Abstract
{
    protected $_code = Novalnet_Payment_Model_Config::NN_CC;
    protected $_canAuthorize = Novalnet_Payment_Model_Config::NN_CC_CAN_AUTHORIZE;
    protected $_formBlockType = Novalnet_Payment_Model_Config::NN_CC_FORM_BLOCK;
    protected $_infoBlockType = Novalnet_Payment_Model_Config::NN_CC_INFO_BLOCK;

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if (!empty($quote) && $quote->hasNominalItems() && $this->helper->checkIsAdmin()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

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
            $request = Mage::app()->getRequest()->getPost();

            if ($this->getFormValues('cc_oneclick_shopping') && !$this->getFormValues('nn_pan_hash')) {
                $maskedCardInfo = $this->getExistingTransInfo();
                $methodSession->setNnCcTid($maskedCardInfo['nn_tid']);
            } else {
                $methodSession->setNnCcHash($this->getFormValues('nn_pan_hash'))
                    ->setNnCcUniqId($this->getFormValues('nn_cc_uniqueid'))
                    ->setNnCcSaveCard((int)isset($request['nn_cc_save_card']))
                    ->setNnCcDoRedirect($this->getFormValues('nn_cc_do_redirect'))
                    ->unsNnCcTid();
            }
        }

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @param  none
     * @return Novalnet_Payment_Model_Method_Abstract
     */
    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance(); // Current payment instance

        if ($info instanceof Mage_Sales_Model_Quote_Payment) {
            $this->validateModel->validateFormInfo(); // Validate the form values
        }

        return $this;
    }

    /**
     * Get the credit card informations
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
        if ($methodSession->getNnCcDoRedirect() == 1) {
            // Credit Card 3D Secure payment redirect url
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
        if ($this->canAuthorize() && ($methodSession->getNnCcDoRedirect() != 1)) {
            $request = $methodSession->getPaymentReqData(); // Get Novalnet payment request data
            $response = $this->postRequest($request); // Send payment request to Novalnet gateway
            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->validateResponse($payment, $request, $response); // Validate Novalnet payment response
        }

        return $this;
    }
}
