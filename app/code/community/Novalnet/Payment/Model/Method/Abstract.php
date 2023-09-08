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
class Novalnet_Payment_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract implements Mage_Payment_Model_Recurring_Profile_MethodInterface
{

    /**
     * Payment Method features
     * @var boolean
     */
    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canOrder = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded = false;
    protected $_canManageRecurringProfiles = true;

    /**
     * Whether a captured transaction may be voided by this gateway
     * This may happen when amount is captured, but not settled
     * @var boolean
     */
    protected $_canCancelInvoice = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!$this->helper) {
            $this->helper = Mage::helper('novalnet_payment');
        }
    }

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if (Mage::registry('payment_code')) {
            Mage::unregister('payment_code'); // Unregister existing payment method code
        }

        Mage::register('payment_code', $this->_code); // Register payment method code for payment process
        // Get Novalnet payment validation model
        $this->validateModel = $this->helper->getModel('Service_Validate_PaymentCheck');
        // Check whether payment method can be used
        if (!$this->validateModel->checkVisibility($quote, $this->_code)) {
            return false;
        }

        // verify Novalnet payment method session values
        $this->validateModel->checkMethodSession();
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
        $info = $this->getInfoInstance();  // Payment method instance

        if ($info instanceof Mage_Sales_Model_Quote_Payment) { // Sales quote payment instance
            // Validate the Novalnet basic params and billing informations
            $this->validateModel->validateNovalnetParams($info);
            $this->validateModel->checkMethodSession(); // verify Novalnet payment method session values
        } elseif ($info instanceof Mage_Sales_Model_Order_Payment) { // Sales order payment instance
            $this->buildRequest($info); // Build Novalnet payport request params
        }

        return $this;
    }

    /**
     * Prepare request to gateway
     *
     * @param  Varien_Object $info
     * @return none
     */
    public function buildRequest($info)
    {
        $requestModel = $this->helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
        $request = $requestModel->getPayportParams($info, $this->_code); // Get Novalnet payport request params
        $methodSession = $this->helper->getMethodSession($this->_code); // Get current payment method session
        $methodSession->setPaymentReqData($request); // Assign Novalnet payport request params to method session
    }

    /**
     * Send payment request to Novalnet gateway
     *
     * @param  Varien_Object $request
     * @return Varien_Object $result
     */
    public function postRequest(Varien_Object $request)
    {
        $result = new Varien_Object();
        // Get Novalnet payment validation model
        $validateModel = $this->helper->getModel('Service_Validate_PaymentCheck');

        $payportUrl = $this->helper->getPayportUrl('paygate'); // Get payport url
        $gatewayModel = $this->helper->getModel('Service_Api_Gateway'); // Get Novalnet gateway model
        $response = $gatewayModel->payportRequestCall($request->getData(), $payportUrl);
        parse_str($response->getBody(), $data);
        $result->addData($data);

        return $result;
    }

    /**
     * Capture the payment transaction
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @return Novalnet_Payment_Model_Method_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $paymentStatus = $this->getPaymentStatus($payment); // Get current payment transaction status
        $transStatus = $paymentStatus->getTransactionStatus();
        $redirectPayments = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('redirectPayments');
        if (($paycodeKey = array_search(Novalnet_Payment_Model_Config::NN_PAYPAL, $redirectPayments)) !== false) {
            unset($redirectPayments[$paycodeKey]);
        }

        $currentUrl = Mage::helper('core/url')->getCurrentUrl();

        if ($this->canCapture() && !preg_match("/callback/i", $currentUrl)
            && !in_array($this->_code, $redirectPayments) && $transStatus
            && $transStatus != Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
        ) {
            $requestModel = $this->helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
            $request = $requestModel->buildProcessRequest($payment, 'capture'); // Build capture process request
            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->postProcessRequest($request, $payment, 'capture'); // Send capture process request
        }

        return $this;
    }

    /**
     * Void the payment transaction
     *
     * @param  Varien_Object $payment
     * @return Novalnet_Payment_Model_Method_Abstract
     */
    public function void(Varien_Object $payment)
    {
        if ($this->canVoid($payment)) {
            $requestModel = $this->helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
            $request = $requestModel->buildProcessRequest($payment, 'void'); // Build void process request

            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->postProcessRequest($request, $payment, 'void'); // Send void process request
        }

        return $this;
    }

    /**
     * Refund the transaction amount
     *
     * @param  Varien_Object $payment
     * @param  float         $amount
     * @return Novalnet_Payment_Model_Method_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if ($this->canRefund()) {
            $requestModel = $this->helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
            $request = $requestModel->buildProcessRequest($payment, 'refund', $amount); // Build refund process request

            $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
            $responseModel->postProcessRequest($request, $payment, 'refund', $amount); // Send refund process request
        }

        return $this;
    }

    /**
     * Get original payment transaction status
     *
     * @param  Varien_Object $payment
     * @return Varien_Object $paymentStatus
     */
    public function getPaymentStatus(Varien_Object $payment)
    {
        // Get payment Novalnet transaction id
        $transactionId = $this->helper->makeValidNumber($payment->getLastTransId());
        $paymentStatus = $this->helper->getModel('Mysql4_TransactionStatus')
            ->loadByAttribute('transaction_no', $transactionId); // Get given payment transaction status
        return $paymentStatus;
    }

    /**
     * Validate recurring profile data
     *
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @return boolean
     */
    public function validateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return true;
    }

    /**
     * Fetch recurring profile details
     *
     * @param  string        $referenceId
     * @param  Varien_Object $result
     * @return boolean
     */
    public function getRecurringProfileDetails($referenceId, Varien_Object $result)
    {
        return true;
    }

    /**
     * Check whether can get recurring profile details
     *
     * @param  none
     * @return boolean
     */
    public function canGetRecurringProfileDetails()
    {
        return true;
    }

    /**
     * Update recurring profile data
     *
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @return boolean
     */
    public function updateRecurringProfile(Mage_Payment_Model_Recurring_Profile $profile)
    {
        return true;
    }

    /**
     * Submit recurring profile
     *
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @param  Mage_Payment_Model_Info              $paymentInfo
     * @return none
     */
    public function submitRecurringProfile(
        Mage_Payment_Model_Recurring_Profile $profile, Mage_Payment_Model_Info $paymentInfo
    ) {
        if ($profile->getTrialPeriodUnit() && $profile->getTrialPeriodFrequency()) {
            $this->helper->showException('Trial Billing Cycles are not support Novalnet payment');
        }

        // Submit recurring profile request to Novalnet gateway
        $this->helper->getModel('Recurring_Payment')->submitRecurringProfile($profile, $paymentInfo);
    }

    /**
     * Manage recurring profile status
     *
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @return none
     */
    public function updateRecurringProfileStatus(Mage_Payment_Model_Recurring_Profile $profile)
    {
        $recurringModel = $this->helper->getModel('Mysql4_Recurring'); // Get Novalnet recurring model
        // Get recurring profile order number (related orders)
        $orderNo = $recurringModel->getRecurringOrderNo($profile);
        $order = $recurringModel->getOrderByIncrementId($orderNo[0]); // Get order object

        $requestModel = $this->helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
        $request = $requestModel->buildRecurringApiRequest($order, $profile); // Get Novalnet recurring process request

        $responseModel = $this->helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
        // Send Novalnet recurring process request
        $responseModel->postRecurringApiRequest($order, $profile->getNewState(), $request);
    }

    /**
     * Get existing transaction details (card/account)
     *
     * @param  none
     * @return Varien_Object $paymentValues
     */
    public function getExistingTransInfo()
    {
        $paymentValues = '';
        $customerId = $this->helper->getCustomerId(); // Get customer id

        if ($customerId) {
            // Get transaction informations if available for the customer
            $collection = $this->helper->getModel('Mysql4_TransactionStatus')->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('payment_name', $this->_code)
                ->addFieldToFilter('reference_transaction', 0)
                ->addFieldToFilter('transaction_status', array('in' => array(100, 98, 99, 103)))
                ->addFieldToSelect('novalnet_acc_details');
            $paymentValues = $collection->getLastItem()->hasNovalnetAccDetails()
                    ? $collection->getLastItem()->getNovalnetAccDetails() : '';
            if ($paymentValues) {
                $isSerialized = $this->helper->is_serialized($paymentValues);
                $paymentValues = ($isSerialized === true) ? unserialize($paymentValues)
                                    : unserialize(base64_decode($paymentValues));
            }
        }

        return $paymentValues;
    }

}
