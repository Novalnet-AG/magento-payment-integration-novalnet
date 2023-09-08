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
class Novalnet_Payment_Model_Method_NovalnetSepaInstalment extends Novalnet_Payment_Model_Method_Abstract
{
    protected $_code = Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT;
    protected $_canAuthorize = Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT_CAN_AUTHORIZE;
    protected $_formBlockType = Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT_FORM_BLOCK;
    protected $_infoBlockType = Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT_INFO_BLOCK;

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        // Get Novalnet payment validation model
        $validateModel = $this->helper->getModel('Service_Validate_PaymentCheck');

        if (!empty($quote) && (!$validateModel->getPaymentGuaranteeStatus($quote, $this->_code) || $quote->hasNominalItems())) {
            return false;
        }

        return parent::isAvailable($quote);
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
     * Assign data to info model instance
     *
     * @param  mixed $data
     * @return Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if ($data) {
            $methodSession = $this->helper->getMethodSession($this->_code); // Get payment method session

            if (!($data instanceof Varien_Object)) {
                $data = new Varien_Object($data);
            }

            if ($data->getDobDate() && $data->getDobMonth() && $data->getDobYear()) {
                $birthDate = $data->getDobDate().'.'.$data->getDobMonth().'.'.$data->getDobYear();
                $birthDate = str_replace(str_split('\\/.,-_ '), '-', $birthDate);
                $birthDate = Mage::getModel('core/date')->date('Y-m-d', strtotime($birthDate));
                $methodSession->setSepaInstalmentCustomerDob($birthDate);
            }

            $request = Mage::app()->getRequest()->getPost();
            $methodSession->setSepaInstalmentHolder($this->getFormValues('novalnetSepaInstalment_account_holder'))
                ->setSepaInstalmentIban($this->getFormValues('novalnetSepaInstalment_account_number'))
                ->setSepaSaveAccount((int)isset($request['nn_sepa_save_account']))
                ->setSepaInstalment($this->getFormValues('novalnet_sepa_instalment_period'))
                ->setSepaNewForm(1);
            $this->helper->getCheckoutSession()->setNnPaymentCode($this->_code);
        }

        return $this;
    }

    /**
     * Get the bank account informations
     *
     * @param  string $param
     * @return string
     */
    public function getFormValues($param)
    {
        return Mage::app()->getRequest()->getPost($param);
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
