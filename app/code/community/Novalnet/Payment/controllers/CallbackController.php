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
class Novalnet_Payment_CallbackController extends Mage_Core_Controller_Front_Action
{
    // Assign callback process payment types
    protected $allowedPayment = array(
        'novalnetcc' => array('CREDITCARD', 'CREDITCARD_BOOKBACK', 'CREDITCARD_CHARGEBACK',
            'CREDIT_ENTRY_CREDITCARD', 'SUBSCRIPTION_STOP', 'DEBT_COLLECTION_CREDITCARD', 'SUBSCRIPTION_REACTIVATE', 'TRANSACTION_CANCELLATION'),
        'novalnetinvoice' => array('INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP', 'GUARANTEED_INVOICE',
            'SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU', 'TRANSACTION_CANCELLATION',
            'GUARANTEED_INVOICE_BOOKBACK', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE'),
        'novalnetprepayment' => array('INVOICE_START', 'INVOICE_CREDIT', 'SUBSCRIPTION_STOP',
            'SUBSCRIPTION_REACTIVATE', 'REFUND_BY_BANK_TRANSFER_EU'),
        'novalnetideal' => array('IDEAL', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE',
            'ONLINE_TRANSFER_CREDIT', 'REFUND_BY_BANK_TRANSFER_EU'),
        'novalnetpaypal' => array('PAYPAL', 'PAYPAL_BOOKBACK', 'SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE', 'TRANSACTION_CANCELLATION'),
        'novalneteps' => array('EPS', 'CREDIT_ENTRY_DE', 'REVERSAL', 'DEBT_COLLECTION_DE', 'REFUND_BY_BANK_TRANSFER_EU',
            'ONLINE_TRANSFER_CREDIT'),
        'novalnetgiropay' => array('GIROPAY', 'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE',
            'REFUND_BY_BANK_TRANSFER_EU', 'ONLINE_TRANSFER_CREDIT'),
        'novalnetbanktransfer' => array('ONLINE_TRANSFER', 'REFUND_BY_BANK_TRANSFER_EU',
            'REVERSAL', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE', 'ONLINE_TRANSFER_CREDIT'),
        'novalnetsepa' => array('DIRECT_DEBIT_SEPA', 'RETURN_DEBIT_SEPA', 'SUBSCRIPTION_STOP',
            'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'REFUND_BY_BANK_TRANSFER_EU',
            'SUBSCRIPTION_REACTIVATE', 'TRANSACTION_CANCELLATION', 'GUARANTEED_SEPA_BOOKBACK'),
        'novalnetprzelewy' => array('PRZELEWY24', 'PRZELEWY24_REFUND'),
        'novalnetinvoiceinstalment'  => array('INSTALMENT_INVOICE', 'INSTALMENT_INVOICE_BOOKBACK',
            'TRANSACTION_CANCELLATION'),
        'novalnetsepainstalment'  => array('INSTALMENT_DIRECT_DEBIT_SEPA', 'INSTALMENT_SEPA_BOOKBACK',
            'TRANSACTION_CANCELLATION'),
        'novalnetcashpayment' => array('CASHPAYMENT', 'CASHPAYMENT_CREDIT', 'CASHPAYMENT_REFUND'));
    protected $invoiceAllowed = array('INVOICE_CREDIT', 'INVOICE_START', 'CASHPAYMENT_CREDIT');
    protected $recurringAllowed = array('INVOICE_START', 'CREDITCARD',
        'DIRECT_DEBIT_SEPA', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'PAYPAL', 'GUARANTEED_INVOICE');
    // Array Type of payment available - Level : 0
    protected $paymentTypes = array('CREDITCARD', 'INVOICE_START', 'DIRECT_DEBIT_SEPA',
        'PAYPAL', 'ONLINE_TRANSFER', 'IDEAL', 'PRZELEWY24',
        'EPS', 'GIROPAY', 'GUARANTEED_DIRECT_DEBIT_SEPA', 'GUARANTEED_INVOICE');
    // Array Type of Chargebacks available - Level : 1
    protected $chargebacks = array('REVERSAL', 'CREDITCARD_CHARGEBACK', 'RETURN_DEBIT_SEPA',
        'CREDITCARD_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'PAYPAL_BOOKBACK', 'PRZELEWY24_REFUND',
        'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK',
        'INSTALMENT_INVOICE_BOOKBACK', 'INSTALMENT_SEPA_BOOKBACK');
    // Array Type of CreditEntry payment and Collections available - Level : 2
    protected $aryCollection = array('INVOICE_CREDIT', 'CREDIT_ENTRY_CREDITCARD',
        'CREDIT_ENTRY_SEPA', 'DEBT_COLLECTION_SEPA', 'CREDIT_ENTRY_DE', 'DEBT_COLLECTION_DE',
        'DEBT_COLLECTION_CREDITCARD', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT');
    protected $arySubscription = array('SUBSCRIPTION_STOP', 'SUBSCRIPTION_REACTIVATE');

    protected $level; // Assign callback process level
    protected $orderNo; // Assign order number
    protected $order; // Assign order object
    protected $storeId; // Assign store id
    protected $payment; // Assign payment object
    protected $code; // Assign payment method code
    protected $paymentTxnId; // Assign payment last transaction id
    protected $currency; // Assign order currency
    protected $responseModel; // Assign Api response model
    protected $test; // Assign callback test mode
    protected $helper; // Assign Novalnet payment helper
    protected $response; // Assign response params to object data
    protected $currentTime; // Assign current time for callback process
    protected $lineBreak; // Assign line break for callback comments
    protected $callback; // Assign callback process flag
    protected $recurring; // Assign recurring process flag
    protected $emailFromAddr; // Assign callback email from address
    protected $emailFromName; // Assign callback email from name
    protected $emailToAddr; // Assign callback email To address
    protected $emailToName; // Assign callback email To name
    protected $emailSubject; // Assign callback email subject
    protected $shopInfo; // Assign shop information
    protected $emailBody; // Assign email body
    protected $endTime; // Assign recurring end time
    protected $parentOrder; // Assign parent order object
    protected $parentPayment; // Assign parent payment
    protected $parentPaymentObj; // Assign parent payment object

    /**
     * Get Novalnet vendor script response
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        $this->loadLayout();
        $block = $this->getLayout()->createBlock(
            'Mage_Core_Block_Template', 'Callback', array('template' => 'novalnet/method/form/Callback.phtml')
        );
        $this->getLayout()->getBlock('content')->append($block);
        if ($this->_assignGlobalParams()) { // Assign global params for callback process
            // Make affiliate process
            if ($this->response->getVendorActivation()) {
                $this->affiliateProcess();
                return false;
            }

            if (($this->level = $this->getCallbackProcessLevel()) === false) { // Assign callback process level
                return false;
            }

            if (($this->orderNo = $this->getOrderIncrementId()) === false) { // Assign order number
                $this->sendCriticalErrorMail();
                return false;
            }

            $this->order = Mage::getModel('sales/order')->loadByIncrementId($this->orderNo); // Get order object
            if (!$this->order->getIncrementId()) {
                $this->sendCriticalErrorMail();
                $this->showDebug('Transaction mapping failed');
                return false;
            }

            $this->storeId = $this->order->getStoreId(); // Get order store id
            $this->payment = $this->order->getPayment(); // Get payment object
            $this->code = $this->payment->getMethodInstance()->getCode(); // Get payment method code
            $additionalData = unserialize($this->payment->getAdditionalData());
            $orderLanguage = !empty($additionalData['nnLang']) ? $additionalData['nnLang'] : 'en_US';

            if ($orderLanguage) {
                Mage::app()->getLocale()->setLocaleCode($orderLanguage);
                Mage::getSingleton('core/translate')->setLocale($orderLanguage)->init('frontend', true);
            }

            $this->paymentTxnId = $this->payment->getLastTransId(); // Get payment last transaction id
            $this->currency = $this->order->getOrderCurrencyCode(); // Get order currency
            $this->responseModel = $this->helper->getModel('Service_Api_Response');

            // Check the change payment method
            if ($this->changePaymentMethod()) {
                return false;
            }

            // Complete the order in-case response failure from Novalnet server
            if ($this->_handleCommunicationFailure()) {
                return false;
            }

            // Check transaction cancellation
            if ($this->transactionCancellation()) {
                return false;
            }

            // Perform callback process for recurring and payment credit related process
            if ($this->_checkParams() === true
                && $this->_callbackProcess() === true) {
                $this->sendCallbackMail(); // Send callback notification E-mail
            } else {
                return false;
            }
        }

        if (!$this->getLayout()->getBlock('Callback')->hasExitProcess()) {
            $this->renderLayout();
        }
    }

    /**
     * Assign global params for callback process
     *
     * @param  none
     * @return none
     */
    protected function _assignGlobalParams()
    {
        $this->test = Mage::getStoreConfig('novalnet_global/merchant_script/test_mode');
        $this->helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
        $this->technicNotifyMail = 'technic@novalnet.de';

        if (!$this->checkIP()) { // Check whether the IP address is authorized
            return false;
        }

        // Get Novalnet callback response values
        $params = Mage::app()->getRequest()->getPost()
            ? Mage::app()->getRequest()->getPost() : Mage::app()->getRequest()->getQuery();
        $checkParams = array_filter($params);

        if (empty($checkParams)) {
            $this->showDebug('Novalnet callback received. No params passed over!');
            return false;
        }

        $this->response = new Varien_Object();
        $this->response->setData($params); // Assign response params to object data
        // Get current time for callback process
        $this->currentTime = Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s');
        $httpHost = Mage::helper('core/http')->getHttpHost();
        $this->lineBreak = empty($httpHost) ? PHP_EOL : '<br />';
        $this->callback = $this->recurring = false;

        // Make reference log (Novalnet callback response) based on configuration settings
        if (Mage::getStoreConfig('novalnet_global/merchant_script/vendor_script_log')) {
            $fileName = 'novalnet_callback_script_' . $this->currentTime . '.log';
            Mage::log($this->response->getData(), null, $fileName, true);
        }

        return true;
    }

    /**
     * Get email config
     *
     * @param  none
     * @return none
     */
    public function getEmailConfig()
    {
        $this->emailFromAddr = Mage::getStoreConfig('trans_email/ident_general/email');
        $this->emailFromName = Mage::getStoreConfig('trans_email/ident_general/name');
        $this->emailToAddr = Mage::getStoreConfig('novalnet_global/merchant_script/mail_to_addr', $this->storeId);
        $this->emailToName = 'store admin'; // Adapt for your need
        $this->emailSubject = 'Novalnet Callback Script Access Report - ' . Mage::app()->getStore()->getFrontendName();
        //Reporting Email Addresses Settings
        $this->shopInfo = 'Magento ' . $this->lineBreak; //mandatory;adapt for your need
    }

    /**
     * Log Affiliate account details
     *
     * @param  none
     * @return none
     */
    public function affiliateProcess()
    {
        $paramsRequired = $this->getRequiredParams(); // Get required params for callback process
        // Check the necessary params for callback script process
        foreach ($paramsRequired as $param) {
            if (!$this->response->getData($param)) {
                $this->showDebug('Required param (' . $param . ') missing!');
                return false;
            }
        }

        $affiliateModel = $this->helper->getModel('Mysql4_AffiliateInfo');
        $affiliateModel->setVendorId($this->response->getVendorId())
            ->setVendorAuthcode($this->response->getVendorAuthcode())
            ->setProductId($this->response->getProductId())
            ->setProductUrl($this->response->getProductUrl())
            ->setActivationDate(
                $this->response->hasActivationDate() ? $this->response->getActivationDate() : $this->currentTime
            )
            ->setAffId($this->response->getAffId())
            ->setAffAuthcode($this->response->getAffAuthcode())
            ->setAffAccesskey($this->response->getAffAccesskey())
            ->save();
        // Send notification mail to Merchant
        $message = 'Novalnet callback script executed successfully with Novalnet account activation information.';
        $this->emailBody = $message;
        $this->sendCallbackMail(); // Send callback notification E-mail
    }

    /**
     * Complete the order in-case response failure from Novalnet server
     *
     * @param  none
     * @return none
     */
    protected function _handleCommunicationFailure()
    {
        $successActionFlag = $this->code . '_successAction';

        if (empty($this->paymentTxnId) && $this->payment->getAdditionalInformation($successActionFlag) != 1
        ) {
            $this->payment->setAdditionalInformation($successActionFlag, 1)->save();
            // Unhold an order
            if ($this->order->canUnhold()) {
                $this->order->unhold()->save();
            }

            // Save transaction additional information
            $transactionId = $this->response->getTid();
            // Get payment mode from Novalnet global configuration
            $responsePaymentMode = $this->response->getTestMode();
            $shopMode = $this->_getConfig('live_mode'); // Get payment mode from callback response
            $testMode = (int)($responsePaymentMode == 1 || $shopMode == 0); // Get payment process mode
            $paymentId = $this->helper->getPaymentId($this->code); // Get payment key
            $confirmText = $this->helper->__(
                'Novalnet Callback Script executed successfully on %s', $this->currentTime
            );
            $data = array('NnTestOrder' => $testMode, 'NnTid' => $transactionId,
                'vendor' => $this->_getConfig('merchant_id'),
                'auth_code' => $this->_getConfig('auth_code'),
                'product' => $this->_getConfig('product_id'),
                'tariff' => $this->_getConfig('tariff_id'),
                'payment_id' => $paymentId
            );
            $paidUntil = $this->response->hasNextSubsCycle()
                ? $this->response->getNextSubsCycle()
                : ($this->response->hasPaidUntil() ? $this->response->getPaidUntil() : '');
            $data['paidUntil'] = $paidUntil ? Mage::helper('core')->formatDate($paidUntil) : '';
            // Get payment additional information
            $additionalData = unserialize($this->payment->getAdditionalData());
            // Merge payment additional information if exist
            $data = $additionalData ? array_merge($additionalData, $data) : $data;

            // Save the payment transaction information
            $this->payment->setTransactionId($transactionId)
                ->setLastTransId($transactionId)
                ->setParentTransactionId(null)
                ->setAdditionalData(serialize($data));

            // Capture the payment
            if ($this->order->canInvoice()
                && $this->response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                && $this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            ) {
                // Save payment information with invoice for Novalnet successful transaction
                $this->payment->setIsTransactionClosed(true)
                    ->capture(null);
            }

            $this->payment->save();

            // Get payment request params
            $request = new Varien_Object();
            $traces = $this->helper->getModel('Mysql4_TransactionTraces')
                ->loadByAttribute('order_id', $this->orderNo);
            $isSerialized = $this->helper->is_serialized($traces->getRequestData());
            $getrequestData = ($isSerialized === true)
                ? unserialize($traces->getRequestData()) : unserialize(base64_decode($traces->getRequestData()));
            $request->setData($getrequestData);
            // Log Novalnet payment transaction informations
            $this->responseModel->logTransactionStatus($this->response, $this->order);
            $this->savePayportResponse(); // Log Novalnet payment transaction traces informations
            // Set order status based on Novalnet transaction status
            if (in_array($this->response->getStatus(), array(Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED,
            Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS)) &&
            in_array($this->response->getTidStatus(), array(100, 90, 85, 86, 91, 98, 99, 75))
            ) {
                $this->setRecurringProfileState(); // Assign recurring profile state if profile exist
                $orderStatus = $this->getOrderStatus(); // Get order status
                $this->order->setState(
                    Mage_Sales_Model_Order::STATE_PROCESSING,
                    $orderStatus,
                    $this->helper->__('Customer successfully returned from Novalnet'),
                    true
                )->save();
                $this->paymentTxnId = $transactionId; // Get payment last transaction id
                // Send order email for successful Novalnet transaction
                Mage::dispatchEvent('novalnet_sales_order_email', array('order' => $this->order));
            } else {
                $this->setRecurringProfileState('canceled'); // Assign recurring profile state if profile exist
                // Cancel the order based on Novalnet transaction status
                $this->responseModel->saveCanceledOrder($this->response, $this->order, $testMode);
            }

            $this->order->save(); // Save the current order
            $this->showDebug($confirmText);
            return true;
        }

        return false;
    }

    /**
     * Check the callback mandatory parameters.
     *
     * @param  none
     * @return boolean
     */
    protected function _checkParams()
    {
        $paramsRequired = $this->getRequiredParams(); // Get required params for callback process
        // Check the necessary params for callback script process
        foreach ($paramsRequired as $param) {
            if (!$this->response->getData($param)) {
                $this->showDebug('Required param (' . $param . ') missing! <br />');
                return false;
            }
        }

        // Check whether Novalnet TID is valid
        $transactionId = $this->getParentTid(); // Get the original/parent transaction id

        if (!preg_match('/^\d{17}$/', $transactionId)) {
            $this->showDebug(
                'Novalnet callback received. Invalid TID [' . $transactionId . '] for Order :' . $this->orderNo
            );
            return false;
        }

        $referenceTid = ($transactionId != $this->response->getTid()) ? $this->response->getTid() : '';

        if ($referenceTid && !preg_match('/^\d{17}$/', $referenceTid)) {
            $this->showDebug(
                'Novalnet callback received. Invalid TID [' . $referenceTid . '] for Order :' . $this->orderNo
            );
            return false;
        }

        if ($this->recurring && $this->response->getSubsBilling() && $this->response->getSignupTid()) {
            $profile = $this->getProfileInformation(); // Get the Recurring Profile Information
            if ($profile->getState() == 'canceled') {
                // Get parent order object for given recurring profile
                $profileOrders = $this->helper->getModel('Mysql4_Recurring')->getRecurringOrderNo($profile);
                $parentOrder = Mage::getModel('sales/order')->loadByIncrementId($profileOrders[0]);
                $this->showDebug('Subscription already Cancelled. Refer Order : ' . $parentOrder->getIncrementId());
                return false;
            }
        } else {
            $additionalData = unserialize($this->payment->getAdditionalData());
            $orderTid = (in_array($this->response->getPaymentType(), $this->chargebacks) && $additionalData['NnTid'])
                ? $additionalData['NnTid'] : $this->paymentTxnId;
            if (!preg_match('/^' . $transactionId . '/i', $orderTid)
                && !in_array($this->response->getPaymentType(), array('INSTALMENT_SEPA_BOOKBACK', 'INSTALMENT_INVOICE_BOOKBACK'))) {
                $this->showDebug('Novalnet callback received. Order no is not valid');
                return false;
            }
        }

        if ((in_array($this->response->getPaymentType(), array_merge($this->aryCollection, $this->chargebacks)))
                && ($this->response->getStatus() != Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                || $this->response->getTidStatus() != Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED)
        ) {
            $this->showDebug('Novalnet callback received. Status is not valid. Refer Order :' . $this->orderNo);
            return false;
        }

        return true;
    }

    /**
     * Get required params for callback process
     *
     * @param  none
     * @return array $paramsRequired
     */
    public function getRequiredParams()
    {
        $paramsRequired = array('payment_type', 'status', 'tid_status', 'tid', 'vendor_id');

        if ($this->response->getVendorActivation()) {
            $paramsRequired = array('vendor_id', 'vendor_authcode', 'product_id',
                'product_url', 'activation_date', 'aff_id', 'aff_authcode', 'aff_accesskey'
            );
        } elseif ($this->callback) {
            $invoicePayments = array_merge($this->aryCollection, $this->chargebacks);
            if (in_array($this->response->getPaymentType(), $invoicePayments)) {
                array_push($paramsRequired, 'tid_payment');
            }
        } elseif ($this->recurring) {
            array_push($paramsRequired, 'signup_tid');
        }

        return $paramsRequired;
    }

    /**
     * Perform callback process
     *
     * @param  none
     * @return boolean
     */
    protected function _callbackProcess()
    {
        if ($this->order) {
            if ($this->level == 1
                && $this->response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                && $this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            ) { // Level 1 payments - Type of Chargebacks
                $this->_refundProcess();
                return true;
            }

            if ($this->response->getPaymentType() == 'SUBSCRIPTION_STOP') { // Cancellation of a subscription
                $this->_subscriptionCancel();
                return true;
            }

            if ($this->response->getPaymentType() == 'SUBSCRIPTION_REACTIVATE') { // Reactivate of a subscription
                $profile = $this->getProfileInformation();
                if ($profile->getState() == 'canceled') {
                    $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE)
                            ->save();
                    $script = $this->helper->__('Novalnet callback script received. Subscription has been reactivated
                    for the TID: %s on %s.', $this->getParentTid(), $this->currentTime) . $this->lineBreak;
                    $this->emailBody = $script;
                    $data = unserialize($this->payment->getAdditionalData());
                    $data['NnComments'] = empty($data['NnComments'])
                        ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
                    $subsCycleDate = $this->response->getNextSubsCycle()
                        ? $this->response->getNextSubsCycle() : $this->response->getPaidUntil();
                    $this->payment->setAdditionalData(serialize($data))->save();
                    $orderNo = $this->getRecurringOrderId();
                    $lastOrder = Mage::getModel('sales/order')->loadByIncrementId($orderNo);
                    $payment = $lastOrder->getPayment();
                    $lastOrderData = unserialize($payment->getAdditionalData());
                    if ($subsCycleDate) {
                        $lastOrderData['paidUntil'] = Mage::helper('core')->formatDate($subsCycleDate);
                        $payment->setAdditionalData(serialize($lastOrderData))
                                ->save();
                    }
                }

                return true;
            }

            if ($this->level == 0) {
                if ($this->recurring) {  // Handle subscription process
                    $this->_recurringProcess();
                    return true;
                }

                $this->_initialPaymentProcess();
                return true;
            }

            if ($this->level == 2) {
                $saveInvoice = '';
                if ($this->response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                    && $this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                ) {
                    $saveInvoice = $this->_saveInvoice(); // Handle payment credit process
                }

                $invoice = $this->order->getInvoiceCollection()->getFirstItem(); // Get order invoice items
                if ($invoice && $saveInvoice) { // Handle payment credit process
                    $this->_updateOrderStatus(); // Update order status for payment credit process
                }

                return true;
            }
        } else {
            $this->showDebug("Novalnet Callback: No order for Increment-ID $this->orderNo found.");
            return false;
        }
    }

    /**
     * Handle payments initialiion process
     *
     * @param  none
     * @return none
     */
    protected function _initialPaymentProcess()
    {
        $transactionId = $this->getParentTid();
        $transactionStatus = $this->helper->getModel('Mysql4_TransactionStatus')
                ->loadByAttribute('transaction_no', $transactionId);
        $paymentStatus = $transactionStatus->getTransactionStatus(); // Get payment original transaction status

        if ($this->response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            && $this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            && in_array($this->response->getPaymentType(), array('PAYPAL', 'PRZELEWY24'))) {
            if ($this->order->canInvoice()) {
                $transactionId = $this->getParentTid(); // Get the original/parent transaction id
                // Create order invoice
                $this->createInvoice($this->order, $transactionId);
                $amount = $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
                $this->emailBody = $this->helper->__('Novalnet callback received. The transaction has been confirmed on %s', $this->currentTime);
                $transactionStatus->setTransactionStatus($this->response->getTidStatus())->save();
                if ($this->order->getInvoiceCollection()->getFirstItem()) { // Handle payment credit process
                    $this->_updateOrderStatus(); // Update order status for payment credit process
                }
            } else {
                $this->showDebug('Novalnet callback received. Order already paid.');
            }
        } elseif ($this->code == Novalnet_Payment_Model_Config::NN_PRZELEWY
            && $this->response->getTidStatus() != Novalnet_Payment_Model_Config::PRZELEWY_PENDING_STATUS) {
                // Get payment transaction status message
                $statusMessage = $this->responseModel->getUnSuccessPaymentText($this->response);
                $script = $this->emailBody = $this->helper->__(
                    'The transaction has been canceled due to: %s', $statusMessage
                );
                $data = unserialize($this->payment->getAdditionalData());
                $data['NnComments'] = empty($data['NnComments'])
                    ? $script : $data['NnComments'] . '<br />' . $script;
                $this->payment->setAdditionalData(serialize($data))->save();
                $this->order->registerCancellation($statusMessage)->save();
        } elseif (in_array($this->response->getPaymentType(), array('INVOICE_START', 'GUARANTEED_INVOICE', 'DIRECT_DEBIT_SEPA',
            'GUARANTEED_DIRECT_DEBIT_SEPA', 'INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA', 'CREDITCARD'))
            && in_array($paymentStatus, array(75, 91, 99, 98)) && in_array($this->response->getTidStatus(), array(91, 99, 100))) {
            $statusApproved = (bool)($this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                                && $this->response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED);
            $paymentObj = $this->payment->getMethodInstance(); // Payment method instance
            $data = unserialize($this->payment->getAdditionalData());
            $orderStatus = '';
            $instalment = '';


            if ($statusApproved && in_array($paymentStatus, array(91, 99, 98))) {
                $instalment = true;
                $orderStatus = $paymentObj->getConfigData('order_status', $this->storeId)
                                    ? $paymentObj->getConfigData('order_status', $this->storeId)
                                    : Mage_Sales_Model_Order::STATE_COMPLETE;
                if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE && $this->response->getPaymentType() == 'GUARANTEED_INVOICE') {
                    $orderStatus = $paymentObj->getConfigData('order_status_after_payment', $this->storeId)
                                        ? $paymentObj->getConfigData('order_status_after_payment', $this->storeId)
                                        : Mage_Sales_Model_Order::STATE_COMPLETE;
                }
            } elseif (in_array($this->code, array('novalnetInvoice', 'novalnetSepa')) && $paymentStatus == 75
                && in_array($this->response->getTidStatus(), array(91, 99, 100))
            ) {
                $orderStatus = Mage::getStoreConfig('novalnet_global/order_status_mapping/order_status')
                    ? Mage::getStoreConfig('novalnet_global/order_status_mapping/order_status')
                    : Mage_Sales_Model_Order::STATE_PROCESSING;

                if ($this->code == 'novalnetInvoice' && $this->response->getTidStatus() == 100) {
                    $orderStatus = $paymentObj->getConfigData('order_status_after_payment', $this->storeId)
                        ? $paymentObj->getConfigData('order_status_after_payment', $this->storeId)
                        : Mage_Sales_Model_Order::STATE_COMPLETE;
                } elseif ($this->code == 'novalnetSepa' && $this->response->getTidStatus() == 100) {
                    $orderStatus = $paymentObj->getConfigData('order_status', $this->storeId)
                        ? $paymentObj->getConfigData('order_status', $this->storeId)
                        : Mage_Sales_Model_Order::STATE_COMPLETE;
                }
            } elseif (in_array($this->code, array('novalnetInvoiceInstalment', 'novalnetSepaInstalment'))
                && $paymentStatus == 75 && in_array($this->response->getTidStatus(), array(99, 91, 100))
            ) {
                $instalment = true;
                $orderStatus = Mage::getStoreConfig('novalnet_global/order_status_mapping/order_status')
                    ? Mage::getStoreConfig('novalnet_global/order_status_mapping/order_status')
                    : Mage_Sales_Model_Order::STATE_PROCESSING;

                if ($this->response->getTidStatus() == 100) {
                    $orderStatus = $paymentObj->getConfigData('order_status', $this->storeId)
                        ? $paymentObj->getConfigData('order_status', $this->storeId)
                        : Mage_Sales_Model_Order::STATE_COMPLETE;
                }
            }

            if ($instalment && in_array($this->code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT,
                Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))) {
                $data = unserialize($this->payment->getAdditionalData());
                $data['PaidInstall'] = $this->response->getInstalmentCyclesExecuted();
                $data['DueInstall'] = $this->response->getDueInstalmentCycles();
                $data['NextCycle'] = $this->response->getNextInstalmentDate();
                if ($futureInstalment = $this->response->getFutureInstalmentDates()) {
                    $futureInstalments = explode('|', $futureInstalment);
                    foreach ($futureInstalments as $futureInstalment) {
                        $cycle = strtok($futureInstalment, "-");
                        $cycleDate = explode('-', $futureInstalment, 2);
                        $data['InstalmentDetails'][$cycle] = array('amount' => $data['InstallPaidAmount'],
                            'nextCycle' => $cycleDate[1],
                            'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                            'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                            'reference' => ($cycle == 1) ? $this->response->getTid() : '');
                    }
                }
                $this->payment->setAdditionalData(serialize($data))->save();
            }

            if ($orderStatus) {
                $this->saveConfirmStatus($transactionStatus);
                // Create order invoice
                if ($statusApproved && in_array($this->code, array(Novalnet_Payment_Model_Config::NN_INVOICE,
                Novalnet_Payment_Model_Config::NN_SEPA, Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT,
                Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT, Novalnet_Payment_Model_Config::NN_CC))) {
                    $invoiceStatus = (bool)($this->response->getPaymentType() == 'GUARANTEED_INVOICE'
                                            || $this->code == Novalnet_Payment_Model_Config::NN_SEPA);
                    $this->createInvoice($this->order, $transactionId, $invoiceStatus);
                }
                $message = $this->helper->__('The transaction has been confirmed');
                $this->order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatus, $message, true)
                     ->save();
            } else {
                $this->showDebug('Novalnet Callbackscript received. Payment type ( ' . $this->response->getPaymentType() . ' ) is not
                applicable for this process!');
            }
        } elseif (in_array($this->response->getPaymentType(), array('INSTALMENT_INVOICE', 'INSTALMENT_DIRECT_DEBIT_SEPA'))
          && $this->response->getInstalmentBilling() == 1 && $this->response->getInstalmentTid()
          && $this->response->getStatus() == 100 && $this->response->getTidStatus() == 100) {
            $data = unserialize($this->payment->getAdditionalData());
            $data['NnTid'] = $this->response->getTid();
            $paidAmount = $data['InstallPaidAmount'] + $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
            $dueAmount = $data['InstallDueAmount'] - $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
            $data['InstallPaidAmount'] = $paidAmount;
            $data['InstallDueAmount'] = $dueAmount;
            $data['PaidInstall'] = $this->response->getInstalmentCyclesExecuted();
            $data['DueInstall'] = $this->response->getDueInstalmentCycles();
            $data['NextCycle'] = $this->response->getNextInstalmentDate();
            $data['InstallCycleAmount'] = $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
            if ($this->response->getPaymentType() == 'INSTALMENT_INVOICE') {
                $note = explode('|', $data['NnNote']);
                if ($this->response->getDueDate()) {
                    $formatDate = Mage::helper('core')->formatDate($this->response->getDueDate());
                    $note[0] = 'Due Date: ' . $formatDate;
                    $data['NnDueDate'] = $this->response->getDueDate();
                }

                $data['NnNote'] = implode('|', $note);
                $referenceNote = explode('|', $data['NnNoteTID']);
                $referenceNote[1] = 'NN_Reference1:TID ' . $this->response->getTid();
                $data['NnNoteTID'] = implode('|', $referenceNote);
            }

            $data['InstalmentDetails'][$this->response->getInstalmentCyclesExecuted()] = array('amount' => $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW'),
                'nextCycle' => $this->response->getNextInstalmentDate(),
                'paidDate' => date('Y-m-d'),
                'status' => 'Paid',
                'reference' => $this->response->getTid());
            $this->payment->setAdditionalData(serialize($data))->save();
            // Send instalment email to end customer
            $this->sendInstalmentmail();
            $this->showDebug('Novalnet Callbackscript received. Instalment payment executed properly');
        } else {
            $this->showDebug('Novalnet Callbackscript received. Payment type ( ' . $this->response->getPaymentType() . ' ) is
            not applicable for this process!');
        }
    }

    /**
     * Save Guaranteed comments
     *
     * @param  none
     * @return none
     */
    private function sendInstalmentmail()
    {
        $this->getEmailConfig();
        $emailTemplate = Mage::getModel('core/email_template')
            ->loadDefault('novalnet_callback_instalment_email_template');
        $storeName = Mage::app()->getStore()->getFrontendName();
        $emailSubject = __('Instalment confirmation %s Order no: %s', $storeName, $this->orderNo);

        // Define some variables to assign to template
        $templateParams = array();
        $templateParams['order'] = $this->order;
        $templateParams['store'] = Mage::app()->getStore();
        $templateParams['payment_html'] = Mage::helper('payment')->getInfoBlock($this->order->getPayment(), Mage::app()->getStore()->getId())->toHtml();
        $templateParams['sepaPayment'] = ($this->response->getPaymentType() == 'INSTALMENT_DIRECT_DEBIT_SEPA')
                ? __('The instalment amount for this cycle %s %s will be debited from your account in one - three business days.', $this->response->getAmount()/ 100, $this->currency) : '';
        $template = $emailTemplate->getProcessedTemplate($templateParams);

        $mail = new Zend_Mail('UTF-8');
        $mail->setBodyHtml($template);
        $mail->setFrom($this->emailFromAddr, $this->emailFromName);
        $this->helper->assignEmailAddress($this->order->getCustomerEmail(), $mail, 'To');
        $mail->setSubject($emailSubject);

        try {
            $mail->send();
            $this->showDebug(__FUNCTION__ . ': Sending Email succeeded!' . $this->lineBreak, false);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->helper->__('Unable to send email'));
            $this->showDebug('Email sending failed: ', false);
            return false;
        }
    }

    /**
     * Change Invoice/Guaranteed Invoice due date
     *
     * @param  Varien_Object $transactionStatus
     * @param  string        $orderStatus
     * @return none
     */
    protected function saveConfirmStatus($transactionStatus)
    {
        $data = unserialize($this->payment->getAdditionalData());
        $this->emailBody = $script = $this->helper->__('Novalnet callback received. The transaction has been confirmed on %s', $this->currentTime);
        if ($this->response->getDueDate()) {
            $formatDate = Mage::helper('core')->formatDate($this->response->getDueDate());
            $note = explode('|', $data['NnNote']);
            $note[0] = 'Due Date: ' . $formatDate;
            $data['NnNote'] = implode('|', $note);
            $data['NnDueDate'] = $formatDate;
        }

        if ($transactionStatus->getTransactionStatus() == 75 && in_array($this->response->getTidStatus(), array(91, 99))) {
            $this->emailBody = $script = $this->helper->__(
                'Novalnet callback received. The transaction status has been changed from pending to on hold for the TID: %s on %s.',
                $this->getParentTid(),
                $this->currentTime
            );
        }

        $transactionStatus->setTransactionStatus($this->response->getTidStatus())->save();
        $message = $this->helper->__('The transaction has been confirmed');
        $data['NnComments'] = empty($data['NnComments'])
                    ? '<br />' . $script . '<br />' : $data['NnComments'] . '<br />' . $script . '<br />';
        $this->payment->setAdditionalData(serialize($data))->save();
    }

    /**
     * Handle payment chargeback and bookback process
     *
     * @param  none
     * @return none
     */
    protected function _refundProcess()
    {
        // Update callback comments for Chargebacks
        $bookBack = array('CREDITCARD_BOOKBACK', 'PAYPAL_BOOKBACK', 'REFUND_BY_BANK_TRANSFER_EU', 'PRZELEWY24_REFUND',
            'CASHPAYMENT_REFUND', 'GUARANTEED_INVOICE_BOOKBACK', 'GUARANTEED_SEPA_BOOKBACK', 'INSTALMENT_INVOICE_BOOKBACK', 'INSTALMENT_SEPA_BOOKBACK');
        $transactionId = !$this->recurring ? $this->response->getTidPayment() : $this->response->getSignupTid();
        $this->emailBody = $script = (in_array($this->response->getPaymentType(), $bookBack)) ? $this->helper->__('Novalnet callback received. Refund/Bookback executed successfully for the TID: %s amount: %s on %s. The subsequent TID: %s', $transactionId, ($this->response->getAmount()) / 100 . ' ' . $this->currency, $this->currentTime, $this->response->getTid()) : $this->helper->__('Novalnet callback received. Chargeback executed successfully for the TID: %s amount: %s on %s. The subsequent TID: %s', $transactionId, ($this->response->getAmount()) / 100 . ' ' . $this->currency, $this->currentTime, $this->response->getTid());
        $data = unserialize($this->payment->getAdditionalData());
        $data['NnComments'] = empty($data['NnComments'])
            ? '<br />' . $script . '<br />' : $data['NnComments'] . '<br />' . $script . '<br />';
        $this->payment->setAdditionalData(serialize($data))->save();
    }

    /**
     * Handle subscription cancel process
     *
     * @param  none
     * @return none
     */
    protected function _subscriptionCancel()
    {
        // Update the status of the user subscription
        $statusText = $this->response->getTerminationReason()
            ? $this->response->getTerminationReason() : $this->responseModel->getStatusText($this->response);
        $script = $this->helper->__(
            'Novalnet callback script received. Subscription has been stopped for the TID: %s on %s',
            $this->response->getSignupTid(),
            $this->currentTime
        );
        $script .= $this->lineBreak . $this->helper->__('Subscription has been canceled due to: ') . $statusText;
        $this->emailBody = $script;
        $profile = $this->getProfileInformation(); // Get the Recurring Profile Information
        $profile->setState('canceled')->save(); // Set profile status as canceled
        // Get parent order object for given recurring profile
        $profileOrders = $this->helper->getModel('Mysql4_Recurring')->getRecurringOrderNo($profile);
        $parentOrder = Mage::getModel('sales/order')->loadByIncrementId($profileOrders[0]);
        $parentPayment = $parentOrder->getPayment();

        // Save additional transaction information
        $data = unserialize($parentPayment->getAdditionalData());
        $data['NnComments'] = empty($data['NnComments']) ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
        $parentPayment->setAdditionalData(serialize($data))->save();
    }

    /**
     * Handle recurring payment process
     *
     * @param  none
     * @return boolean
     */
    protected function _recurringProcess()
    {
        $profile = $this->getProfileInformation(); // Get the Recurring Profile Information

        if (in_array($this->response->getStatus(), array(Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED, Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS))) {
            $script = $this->helper->__(
                'Novalnet Callback Script executed successfully for the subscription TID: %s with amount %s on %s',
                $this->response->getSignupTid(),
                ($this->response->getAmount()) / 100 . ' ' . $this->currency,
                $this->currentTime
            );
            // Save subscription callback values
            $callbackCycle = $this->saveRecurringProcess($profile->getPeriodMaxCycles(), $profile->getId());
            // Verify subscription period (cycles)
            $this->verifyRecurringCycle($profile, $callbackCycle);
            // Create recurring payment order
            $this->_createOrder($script, $profile->getId());
        } else {
            if ($profile->getState() != 'canceled') {  // Cancellation of a subscription
                $this->_subscriptionCancel();
            }
        }

        return true;
    }

    /**
     * Save subscription callback informations
     *
     * @param  int $periodMaxCycles
     * @param  int $profileId
     * @return int $callbackCycle
     */
    public function saveRecurringProcess($periodMaxCycles, $profileId)
    {
        $recurringModel = $this->helper->getModel('Mysql4_Recurring');
        $recurringCollection = $recurringModel->getCollection();
        $recurringCollection->addFieldToFilter('profile_id', $profileId);
        $recurringCollection->addFieldToSelect('callbackcycle');
        $countRecurring = count($recurringCollection);
        if ($countRecurring == 0) {
            $callbackCycle = 1;
            $recurringModel->setProfileId($profileId)
                ->setSignupTid($this->response->getSignupTid())
                ->setBillingcycle($periodMaxCycles)
                ->setCallbackcycle($callbackCycle)
                ->setCycleDatetime($this->currentTime)
                ->save();
        } else {
            foreach ($recurringCollection as $profile) {
                $callbackCycle = $profile->getCallbackcycle();
            }

            $callbackCycle = ++$callbackCycle;
            $recurring = $recurringModel->load($profileId, 'profile_id');
            $recurring->setCallbackcycle($callbackCycle)
                ->setCycleDatetime($this->currentTime)
                ->save();
        }

        return $callbackCycle;
    }

    /**
     * Verify subscription period (cycles)
     *
     * @param  int $profile
     * @param  int $callbackCycle
     * @return none
     */
    public function verifyRecurringCycle($profile, $callbackCycle)
    {
        $periodMaxCycles = $profile->getPeriodMaxCycles();
        $this->endTime = 0;

        if ($callbackCycle == $periodMaxCycles) {
            // Get parent order object for given recurring profile
            $profileOrders = $this->helper->getModel('Mysql4_Recurring')->getRecurringOrderNo($profile);
            $this->parentOrder = Mage::getModel('sales/order')->loadByIncrementId($profileOrders[0]);
            $this->parentPayment = $this->parentOrder->getPayment();
            $this->parentPaymentObj = $this->parentPayment->getMethodInstance();

            // Send subscription cancel request to Novalnet
            $response = $this->initiateSubscriptionCancel();

            // Save additional transaction information
            $data = unserialize($this->parentPayment->getAdditionalData());
            $data['subsCancelReason'] = 'other';
            $this->parentPayment->setAdditionalData(serialize($data))->save();

            if ($response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
                $profile->setState('canceled')->save();
                $this->endTime = 1;
            } else {
                $this->endTime = 0;
            }
        }
    }

    /**
     * Send subscription cancel request to Novalnet
     *
     * @param  none
     * @return Varien_Object $response
     */
    public function initiateSubscriptionCancel()
    {
        $traces = $this->helper->getModel('Mysql4_TransactionTraces')
            ->loadByAttribute('order_id', $this->parentOrder->getIncrementId());
        $isSerialized = $this->helper->is_serialized($traces->getRequestData());
        $paymentRequest = ($isSerialized === true)
            ? unserialize($traces->getRequestData()) : unserialize(base64_decode($traces->getRequestData()));
        $additionalData = unserialize($this->parentPayment->getAdditionalData());
        // Build subscription cancel request
        $request = new Varien_Object();
        $request->setVendor($additionalData['vendor'])
            ->setAuthCode($additionalData['auth_code'])
            ->setProduct($additionalData['product'])
            ->setTariff($additionalData['tariff'])
            ->setKey($paymentRequest['key'])
            ->setNnLang($paymentRequest['lang'])
            ->setCancelSub(1)
            ->setCancelReason('other')
            ->setTid($this->response->getSignupTid())
            ->setRemoteIp($this->helper->getRealIpAddr());
        // Send recurring cancel request to Novalnet gateway
        $response = $this->parentPaymentObj->postRequest($request);
        // Log Novalnet payment transaction informations
        $this->responseModel->logTransactionTraces($request, $response, $this->parentOrder, $request->getTid());
        return $response;
    }

    /**
     * New order create process
     *
     * @param  string $script
     * @param  int    $profileId
     * @return none
     */
    protected function _createOrder($script, $profileId)
    {
        $this->setLanguageStore(); // Set the language by store id
        $orderNew = Mage::getModel('sales/order')
            ->setState('new');

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($this->storeId)
            ->setMethod($this->code)
            ->setPo_number('-');
        $orderNew->setPayment($orderPayment);
        $orderNew = $this->setOrderDetails($this->order, $orderNew);
        $billingAddress = Mage::getModel('sales/order_address');
        $getBillingAddress = Mage::getModel('sales/order_address')->load($this->order->getBillingAddress()->getId());
        if ($customerId = $this->order->getCustomerId()) {
            $this->getCustomerAddress($customerId, $getBillingAddress, 'billing');
        }

        $orderNew = $this->setBillingShippingAddress($getBillingAddress, $billingAddress, $orderNew, $this->order);
        if ($this->order->getIsVirtual() == 0) {
            $shippingAddress = Mage::getModel('sales/order_address');
            $getShipping = Mage::getModel('sales/order_address')->load($this->order->getShippingAddress()->getId());
            if ($customerId) {
                $this->getCustomerAddress($customerId, $getShipping, 'shipping');
            }
            $orderNew = $this->setBillingShippingAddress($getShipping, $shippingAddress, $orderNew, $this->order);
        }

        $orderNew = $this->setOrderItemsDetails($this->order, $orderNew);
        $paymentNew = $orderNew->getPayment();
        $orderStatus = $this->getOrderStatus(); // Get order status
        $message = $this->helper->__('Novalnet Recurring Callback script Executed Successfully');
        $orderNew->addStatusToHistory($orderStatus, $message, false)->save();
        $this->getLayout()->getBlock('Callback')->setNewOrderNumber($orderNew->getIncrementId());

        $transactionId = trim($this->response->getTid());
        $data = $this->getPaymentAddtionaldata($orderNew, $script);
        $additionalInfo = $this->payment->getAdditionalInformation();
        // Save payment transaction informations
        $paymentNew->setTransactionId($transactionId)
            ->setAdditionalData(serialize($data))
            ->setAdditionalInformation($additionalInfo)
            ->setLastTransId($transactionId)
            ->setParentTransactionId(null)
            ->setIsTransactionClosed(false)
            ->save();
        // Send new order email
        $orderNew->sendNewOrderEmail()
            ->setEmailSent(true)
            ->setPayment($paymentNew)
            ->save();

        $this->insertOrderId($orderNew->getId(), $profileId); // Insert the order id in recurring order table
        $this->updateInventory($this->order); // Update the product inventory (stock)
        // Log Novalnet payment transaction informations
        $this->responseModel->logTransactionStatus($this->response, $orderNew);
        // Log Novalnet payment transaction traces informations
        $transactionTraces = $this->helper->getModel('Mysql4_TransactionTraces');
        $transactionTraces->setOrderId($orderNew->getIncrementId())
                          ->setTransactionId($this->response->getTid())
                          ->setRequestData(null)
                          ->setCustomerId($orderNew->getCustomerId())
                          ->setStatus($this->response->getStatus())
                          ->setResponseData(base64_encode(serialize($this->response->getData())))
                          ->setStoreId($orderNew->getStoreId())
                          ->setShopUrl($this->helper->getBaseUrl())
                          ->setCreatedDate($this->helper->getCurrentDateTime())
                          ->save();

        if ($orderNew->canInvoice()
            && $this->code != Novalnet_Payment_Model_Config::NN_PREPAYMENT
            && $this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
        ) {
            $this->createInvoice($orderNew, $transactionId);
        } else {
            $transaction = $paymentNew->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false);
            $transaction->setParentTxnId(null)
                        ->save();
        }
    }

    /**
     * Get customer details (default billing/shipping address)
     *
     * @param int           $customerId
     * @param Varien_Object $orderAddress
     * @param string        $addressType
     */
    public function getCustomerAddress($customerId, $orderAddress, $addressType)
    {
        // Load customer details (default billing/shipping address) by customer id (if exist).
        $customerData = Mage::getModel('customer/customer')->load($customerId);
        $defaultCustomerAddress = ($addressType == 'billing')
            ? $customerData->getPrimaryBillingAddress() : $customerData->getPrimaryShippingAddress();
        if ($defaultCustomerAddress) {
            $orderAddress->setFirstname($defaultCustomerAddress->getFirstname())
                         ->setMiddlename($defaultCustomerAddress->getMiddlename())
                         ->setLastname($defaultCustomerAddress->getLastname())
                         ->setPrefix($defaultCustomerAddress->getPrefix())
                         ->setSuffix($defaultCustomerAddress->getSuffix())
                         ->setCompany($defaultCustomerAddress->getCompany())
                         ->setCity($defaultCustomerAddress->getCity())
                         ->setCountryId($defaultCustomerAddress->getCountryId())
                         ->setRegion($defaultCustomerAddress->getRegion())
                         ->setPostcode($defaultCustomerAddress->getPostcode())
                         ->setTelephone($defaultCustomerAddress->getTelephone())
                         ->setFax($defaultCustomerAddress->getFax())
                         ->setRegionId($defaultCustomerAddress->getRegionId())
                         ->setStreet($defaultCustomerAddress->getStreet());
        }
    }

    /**
     * Get payment method additional informations
     *
     * @param Varien_Object $orderNew
     * @param mixed         $script
     * @param array         $data
     */
    public function getPaymentAddtionaldata($orderNew, $script)
    {
        $parentOrderNo = $this->getOrderIdByTransId() ? $this->getOrderIdByTransId() : $orderNew->getIncrementId();
        $subsCycleDate = $this->response->getNextSubsCycle()
            ? $this->response->getNextSubsCycle() : $this->response->getPaidUntil();
        $script .= $comments = '<br />' . $this->helper->__('Reference order id: %s', $parentOrderNo) . '<br />';
        $this->emailBody = $script;

        $parentAdditionalData = unserialize($this->payment->getAdditionalData());
        $language = !empty($parentAdditionalData['nnLang']) ? $parentAdditionalData['nnLang'] : 'en_US';
        $data = array('NnTestOrder' => $this->response->getTestMode(),
            'NnTid' => trim($this->response->getTid()),
            'NnComments' => ($comments),
            'vendor' => $parentAdditionalData['vendor'],
            'auth_code' => $parentAdditionalData['auth_code'],
            'product' => $parentAdditionalData['product'],
            'tariff' => $parentAdditionalData['tariff'],
            'payment_id' => $parentAdditionalData['payment_id'],
            'nnLang' => $language,
            'paidUntil' => $subsCycleDate ? Mage::helper('core')->formatDate($subsCycleDate) : ''
        );

        if (in_array($this->response->getPaymentType(), array('GUARANTEED_INVOICE', 'GUARANTEED_DIRECT_DEBIT_SEPA'))) {
            $data['NnGuarantee'] = 1;
        }

        if (in_array($this->code, array('novalnetInvoice', 'novalnetPrepayment'))) {
            $amount = Mage::helper('core')->currency($this->response->getAmount() / 100, true, false);
            $data['NnNote'] = $this->responseModel->getInvoicePaymentNote($this->response);
            $data['NnDueDate'] = $this->response->getDueDate();
            $data['NnNoteAmount'] = 'NN_Amount: ' . $amount;
        }

        return $data;
    }

    /**
     * Create order invoice
     *
     * @param Varien_Object $order
     * @param int           $transactionId
     * @param boolean       $pendingState
     * @param none
     */
    public function createInvoice($order, $transactionId, $pendingState = false)
    {
        $invoice = $order->prepareInvoice();
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->setState(
            ($pendingState == true)
                ? Mage_Sales_Model_Order_Invoice::STATE_OPEN : Mage_Sales_Model_Order_Invoice::STATE_PAID
        );
        $invoice->register();
        $invoice->save();

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        if ($pendingState == false) {
            $transMode = (version_compare($this->helper->getMagentoVersion(), '1.6', '<')) ? false : true;
            $payment = $order->getPayment();
            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed($transMode);
            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE, null, false);
            $transaction->setParentTxnId(null)
                ->save();
        }
    }

    /**
     * Set the language based on store id
     *
     * @param  none
     * @return none
     */
    public function setLanguageStore()
    {
        $app = Mage::app();
        $app->setCurrentStore($this->storeId);
        $locale = Mage::getStoreConfig('general/locale/code', $this->storeId);
        $app->getLocale()->setLocaleCode($locale);
        Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
    }

    /**
     * Set order item and customer informations
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $orderNew
     * @return Varien_Object $orderNew
     */
    public function setOrderDetails($order, $orderNew)
    {
        if ($customerId = $order->getCustomerId()) {
            // Get the latest customer details (Email, First name, Last name) from customer account information.
            $customerData = Mage::getModel('customer/customer')->load($customerId);
            $customerGroupId = $customerData->getGroupId();
            $email = $customerData->getEmail();
            $firstName = $customerData->getFirstname();
            $lastName = $customerData->getLastname();
        }

        $orderNew->setStoreId($order->getStoreId())
            ->setCustomerGroupId(!empty($customerGroupId) ? $customerGroupId : $order->getCustomerGroupId())
            ->setQuoteId(0)
            ->setIsVirtual($order->getIsVirtual())
            ->setGlobalCurrencyCode($order->getGlobalCurrencyCode())
            ->setBaseCurrencyCode($order->getBaseCurrencyCode())
            ->setStoreCurrencyCode($order->getStoreCurrencyCode())
            ->setOrderCurrencyCode($order->getOrderCurrencyCode())
            ->setStoreName($order->getStoreName())
            ->setCustomerEmail(!empty($email) ? $email : $order->getCustomerEmail())
            ->setCustomerFirstname(!empty($firstName) ? $firstName : $order->getCustomerFirstname())
            ->setCustomerLastname(!empty($lastName) ? $lastName : $order->getCustomerLastname())
            ->setCustomerId($order->getCustomerId())
            ->setCustomerIsGuest($order->getCustomerIsGuest())
            ->setState('processing')
            ->setStatus($order->getStatus())
            ->setSubtotal($order->getSubtotal())
            ->setBaseSubtotal($order->getBaseSubtotal())
            ->setSubtotalInclTax($order->getSubtotalInclTax())
            ->setBaseSubtotalInclTax($order->getBaseSubtotalInclTax())
            ->setShippingAmount($order->getShippingAmount())
            ->setBaseShippingAmount($order->getBaseShippingAmount())
            ->setGrandTotal($order->getGrandTotal())
            ->setBaseGrandTotal($order->getBaseGrandTotal())
            ->setTaxAmount($order->getTaxAmount())
            ->setTotalQtyOrdered($order->getTotalQtyOrdered())
            ->setBaseTaxAmount($order->getBaseTaxAmount())
            ->setBaseToGlobalRate($order->getBaseToGlobalRate())
            ->setBaseToOrderRate($order->getBaseToOrderRate())
            ->setStoreToBaseRate($order->getStoreToBaseRate())
            ->setStoreToOrderRate($order->getStoreToOrderRate())
            ->setWeight($order->getWeight())
            ->setCustomerNoteNotify($order->getCustomerNoteNotify());
        return $orderNew;
    }

    /**
     * Set billing and shipping address informations
     *
     * @param  Varien_Object $getBillingAddress
     * @param  Varien_Object $billingAddress
     * @param  Varien_Object $orderNew
     * @param  Varien_Object $order
     * @return mixed
     */
    public function setBillingShippingAddress($getBillingAddress, $billingAddress, $orderNew, $order)
    {
        $addressType = $getBillingAddress->getAddressType();
        $billingStreet = $getBillingAddress->getStreet();
        $street = !empty($billingStreet[1]) ? array($billingStreet[0], $billingStreet[1]) : array($billingStreet[0]);
        $billingAddress->setStoreId($order->getStoreId())
            ->setAddressType($addressType)
            ->setPrefix($getBillingAddress->getPrefix())
            ->setFirstname($getBillingAddress->getFirstname())
            ->setLastname($getBillingAddress->getLastname())
            ->setMiddlename($getBillingAddress->getMiddlename())
            ->setSuffix($getBillingAddress->getSuffix())
            ->setCompany($getBillingAddress->getCompany())
            ->setStreet($street)
            ->setCity($getBillingAddress->getCity())
            ->setCountryId($getBillingAddress->getCountryId())
            ->setRegionId($getBillingAddress->getRegionId())
            ->setTelephone($getBillingAddress->getTelephone())
            ->setFax($getBillingAddress->getFax())
            ->setVatId($getBillingAddress->getVatId())
            ->setPostcode($getBillingAddress->getPostcode());

        if ($addressType == Mage_Sales_Model_Quote_Address::TYPE_BILLING) {
            $orderNew->setBillingAddress($billingAddress);
        } else {
            $shippingMethod = $order->getShippingMethod();
            $shippingDescription = $order->getShippingDescription();
            $orderNew->setShippingAddress($billingAddress)
                ->setShippingMethod($shippingMethod)
                ->setShippingDescription($shippingDescription);
        }

        return $orderNew;
    }

    /**
     * Set product informations (product, discount, tax, etc.,)
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $orderNew
     * @return mixed
     */
    public function setOrderItemsDetails($order, $orderNew)
    {
        foreach ($order->getAllItems() as $orderValue) {
            $orderItem = Mage::getModel('sales/order_item')
                ->setStoreId($orderValue->getStoreId())
                ->setQuoteItemId(0)
                ->setQuoteParentItemId(null)
                ->setQtyBackordered(null)
                ->setQtyOrdered($orderValue->getQtyOrdered())
                ->setName($orderValue->getName())
                ->setIsVirtual($orderValue->getIsVirtual())
                ->setProductId($orderValue->getProductId())
                ->setProductType($orderValue->getProductType())
                ->setSku($orderValue->getSku())
                ->setWeight($orderValue->getWeight())
                ->setPrice($orderValue->getPrice())
                ->setBasePrice($orderValue->getBasePrice())
                ->setOriginalPrice($orderValue->getOriginalPrice())
                ->setTaxAmount($orderValue->getTaxAmount())
                ->setTaxPercent($orderValue->getTaxPercent())
                ->setIsNominal($orderValue->getIsNominal())
                ->setRowTotal($orderValue->getRowTotal())
                ->setBaseRowTotal($orderValue->getBaseRowTotal())
                ->setBaseWeeeTaxAppliedAmount($orderValue->getBaseWeeeTaxAppliedAmount())
                ->setWeeeTaxAppliedAmount($orderValue->getWeeeTaxAppliedAmount())
                ->setWeeeTaxAppliedRowAmount($orderValue->getWeeeTaxAppliedRowAmount())
                ->setWeeeTaxApplied($orderValue->getWeeeTaxApplied())
                ->setWeeeTaxDisposition($orderValue->getWeeeTaxDisposition())
                ->setWeeeTaxRowDisposition($orderValue->getWeeeTaxRowDisposition())
                ->setBaseWeeeTaxDisposition($orderValue->getBaseWeeeTaxDisposition())
                ->setBaseWeeeTaxRowDisposition($orderValue->getBaseWeeeTaxRowDisposition());
            $orderNew->addItem($orderItem);
        }

        return $orderNew;
    }

    /**
     * Insert the order id in recurring order table
     *
     * @param  int $newOrderId
     * @param  int $profileId
     * @return none
     */
    public function insertOrderId($newOrderId, $profileId)
    {
        if ($newOrderId && $profileId) {
            $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $connection->beginTransaction();
            $tablePrefix = Mage::getConfig()->getTablePrefix();
            $orderTable = $tablePrefix . 'sales_recurring_profile_order';
            $fields = array();
            $fields['profile_id'] = $profileId;
            $fields['order_id'] = $newOrderId;
            $connection->insert($orderTable, $fields);
            $connection->commit();
        }
    }

    /**
     * Update the product inventory (stock)
     *
     * @param  Varien_Object $order
     * @return none
     */
    public function updateInventory($order)
    {
        foreach ($order->getAllItems() as $orderValue) {
            $itemsQtyOrdered = floor($orderValue->getQtyOrdered());
            $productId = $orderValue->getProductId();
            break;
        }

        if ($productId) {
            $stockObj = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            $productQtyBefore = (int) $stockObj->getQty();
        }

        if (isset($productQtyBefore) && $productQtyBefore > 0) {
            $productQtyAfter = (int) ($productQtyBefore - $itemsQtyOrdered);
            $stockObj->setQty($productQtyAfter);
            $stockObj->save();
        }
    }

    /**
     * Create invoice to order payment
     *
     * @param  none
     * @return boolean
     */
    protected function _saveInvoice()
    {
        $amount = $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
        if (in_array($this->response->getPaymentType(), array('INVOICE_CREDIT', 'ONLINE_TRANSFER_CREDIT', 'CASHPAYMENT_CREDIT'))) {
            $data = unserialize($this->payment->getAdditionalData());
            $callbackModel = $this->helper->getModel('Mysql4_Callback')->loadLogByOrderId($this->orderNo);
            $tidPayment = (!$this->recurring) ? $this->response->getTidPayment() : $this->response->getSignupTid();
            $totalAmount = sprintf(($this->response->getAmount() + $callbackModel->getCallbackAmount()), 0.2);
            $grandTotal = round(sprintf(($this->order->getGrandTotal() * 100), 0.2));

            $script = $this->helper->__('Novalnet Callback Script executed successfully for the TID: %s with amount %s on %s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %s <br />', $tidPayment, $amount . ' ' . $this->currency, $this->currentTime, $this->response->getTid()) . $this->lineBreak;

            if ($this->response->getPaymentType() == 'INVOICE_CREDIT' && $totalAmount < $grandTotal) {
                $this->logCallbackInfo($callbackModel, $totalAmount, $this->orderNo); // Log callback data
                $this->emailBody = $script;
                $this->emailBody = $this->emailBody . $this->lineBreak;
                $data['NnComments'] = empty($data['NnComments'])
                    ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
                $this->payment->setAdditionalData(serialize($data))->save();
                return false;
            } else {
                $this->logCallbackInfo($callbackModel, $totalAmount, $this->orderNo); // Log callback data
                if ($this->order->canInvoice()) {
                    $transactionId = $this->getParentTid(); // Get the original/parent transaction id
                    // Create order invoice
                    $this->createInvoice($this->order, $transactionId);
                    $this->emailBody = $script;
                    $this->emailBody = $this->emailBody . $this->lineBreak;
                } else {
                    // Get order invoice collection
                    $invoice = $this->order->getInvoiceCollection()->getFirstItem();

                    if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE && $invoice->getState() == 1) {
                        $this->emailBody = $script;
                        $data['NnComments'] = empty($data['NnComments'])
                            ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
                        $this->payment->setAdditionalData(serialize($data))->save();
                        $this->saveOrderStatus();
                        return false;
                    }

                    if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE && $this->response->getPaymentType() == 'INVOICE_CREDIT') {
                        $this->logCallbackInfo($callbackModel, $totalAmount, $this->orderNo); // Log callback data
                        $this->emailBody = $script;
                        $this->emailBody = $this->emailBody . $this->lineBreak;
                        $data['NnComments'] = empty($data['NnComments'])
                            ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
                        $this->payment->setAdditionalData(serialize($data))->save();
                        return false;
                    }

                    if ($this->response->getPaymentType() == 'ONLINE_TRANSFER_CREDIT') {
                        if ($totalAmount >= $grandTotal) {
                            $this->emailBody = $script . $this->lineBreak . $this->helper->__('The amount of %s for the order %s has been paid. Please verify received amount and TID details, and update the order status accordingly.', $amount . ' ' . $this->currency, $this->orderNo) . $this->lineBreak;
                            $script = $script . '<br />' . $this->helper->__('The amount of %s for the order %s has been paid. Please verify received amount and TID details, and update the order status accordingly.', $amount . ' ' . $this->currency, $this->orderNo) . '<br />';
                        }else {
                            $this->emailBody = $script;
                        }

                        $data['NnComments'] = empty($data['NnComments'])
                            ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
                        $this->payment->setAdditionalData(serialize($data))->save();
                        return false;
                    }

                    if (in_array($this->response->getPaymentType(), array('CASHPAYMENT_CREDIT'))) {
                        $this->showDebug(
                            'Novalnet callback received. Callback Script executed already. Refer Order :' . $this->orderNo
                        );
                        return false;
                    } else {
                        $this->showDebug('Novalnet Callbackscript received. Payment type ( ' . $this->response->getPaymentType() . ' ) is not applicable for this process!');
                        return false;
                    }
                }
            }
        } else {
            $data = unserialize($this->payment->getAdditionalData());
            $tidPayment = (!$this->recurring) ? $this->response->getTidPayment() : $this->response->getSignupTid();

            $script = $this->helper->__('Novalnet Callback Script executed successfully for the TID: %s with amount %s on %s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %s', $tidPayment, $amount . ' ' . $this->currency, $this->currentTime, $this->response->getTid()) . $this->lineBreak;
            $this->emailBody = $script;
            $this->emailBody = $this->emailBody . $this->lineBreak;
            $data['NnComments'] = empty($data['NnComments'])
                ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
            $this->payment->setAdditionalData(serialize($data))->save();
            return false;
        }

        return true;
    }

    /**
     * Update order and invoice status (Invoice & PayPal)
     *
     * @param  none
     * @return none
     */
    public function saveOrderStatus()
    {
        $orderStatus = $this->getOrderStatus(); // Get order status
        $orderState = Mage_Sales_Model_Order::STATE_PROCESSING;
        $message = 'Novalnet callback set state ' . $orderState . ' for Order-ID = ' . $this->orderNo;
        $this->order->setState($orderState, true, $message);
        $this->order->addStatusToHistory($orderStatus, 'Novalnet callback added order status ' . $orderStatus);
        $this->order->save();

        $invoice = $this->order->getInvoiceCollection()->getFirstItem();
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_PAID);
        $invoice->save();
    }

    /**
     * Update order status and callback transaction details
     *
     * @param  none
     * @return none
     */
    protected function _updateOrderStatus()
    {
        $orderStatus = $this->getOrderStatus(); // Get order status
        $state = Mage_Sales_Model_Order::STATE_PROCESSING;
        $message = 'Novalnet callback set state ' . $state . ' for Order-ID = ' . $this->orderNo;
        $amount = $this->helper->getFormatedAmount($this->response->getAmount(), 'RAW');
        $this->order->setState($state, true, $message);
        $this->order->addStatusToHistory($orderStatus, 'Novalnet callback added order status ' . $orderStatus);
        $this->order->save();

        if (in_array($this->response->getPaymentType(), $this->invoiceAllowed)) {
            $script = $this->helper->__('Novalnet Callback Script executed successfully for the TID: %s with amount %s on %s. Please refer PAID transaction in our Novalnet Merchant Administration with the TID: %s', $this->response->getTidPayment(), $amount . ' ' . $this->currency, $this->currentTime, $this->response->getTid());
        } else {
            $script = $this->helper->__('Novalnet callback received. The transaction has been confirmed on %s', $this->currentTime);
        }

        $data = unserialize($this->payment->getAdditionalData()); // Get payment additional information
        $data['NnComments'] = empty($data['NnComments']) ? '<br />' . $script : $data['NnComments'] . '<br />' . $script;
        $this->payment->setAdditionalData(serialize($data));
        $this->order->setPayment($this->payment)->save();
    }

    /**
     * Log callback transaction information
     *
     * @param  Novalnet_Payment_Model_Mysql4_Callback $callbackModel
     * @param  float                                  $amount
     * @param  int                                    $orderNo
     * @return none
     */
    public function logCallbackInfo($callbackModel, $amount, $orderNo)
    {
        $transactionId = $this->getParentTid(); // Get the original/parent transaction id
        $reqUrl = Mage::helper('core/http')->getRequestUri();
        $callbackModel->setOrderId($orderNo)
            ->setCallbackAmount($amount)
            ->setReferenceTid($this->response->getTid())
            ->setCallbackTid($transactionId)
            ->setCallbackDatetime($this->currentTime)
            ->setCallbackLog($reqUrl)
            ->save();
    }

    /**
     * Get payment order status
     *
     * @param  none
     * @return string
     */
    public function getOrderStatus()
    {
        $paymentObj = $this->payment->getMethodInstance(); // Payment method instance
        $status = $paymentObj->getConfigData('order_status', $this->storeId);
        $redirectPayments = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('redirectPayments');
        array_push($redirectPayments, Novalnet_Payment_Model_Config::NN_PREPAYMENT, Novalnet_Payment_Model_Config::NN_INVOICE, Novalnet_Payment_Model_Config::NN_CASHPAYMENT);

        // Redirect payment method order status
        if ($this->response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            && $this->response->getPaymentType() != 'INVOICE_START'
            && in_array($this->code, $redirectPayments)
        ) {
            $status = $paymentObj->getConfigData('order_status_after_payment', $this->storeId);
        }

        // PayPal payment pending order status
        if (($this->code == Novalnet_Payment_Model_Config::NN_PAYPAL && in_array($this->response->getTidStatus(), array(Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS, Novalnet_Payment_Model_Config::PRZELEWY_PENDING_STATUS)))
            || ($this->code == Novalnet_Payment_Model_Config::NN_CC)
        ) {
            $status = $paymentObj->getConfigData('order_status', $this->storeId)
                ? $paymentObj->getConfigData('order_status', $this->storeId)
                : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        }

        return !empty($status) ? $status : Mage_Sales_Model_Order::STATE_PROCESSING;
    }

    /**
     * Log Novalnet callback response data
     *
     * @param  none
     * @return none
     */
    public function savePayportResponse()
    {
        $transactionTraces = $this->helper->getModel('Mysql4_TransactionTraces')
            ->loadByAttribute('order_id', $this->orderNo); // Get Novalnet transaction traces model
        $transactionTraces->setTransactionId($this->response->getTid())
            ->setResponseData(base64_encode(serialize($this->response->getData())))
            ->setCustomerId($this->order->getCustomerId())
            ->setStatus($this->response->getTidStatus())
            ->setStoreId($this->storeId)
            ->setShopUrl($this->response->getSystemUrl() ? $this->response->getSystemUrl() : '')
            ->save();
    }

    /**
     * Get Novalnet global configuration values
     *
     * @param  string $field
     * @return mixed $config
     */
    protected function _getConfig($field)
    {
        $path = 'novalnet_global/novalnet/' . $field; // Global config value path
        $config = null;

        if ($field == 'live_mode') { // Novalnet payment mode
            $paymentMethod = Mage::getStoreConfig($path, $this->storeId);
            $config = (bool)preg_match('/' . $this->code . '/i', $paymentMethod);
        } elseif ($field !== null) {  // Get Novalnet payment/global configuration
            $config = Mage::getStoreConfig($path, $this->storeId);
        }

        return $config;
    }

    /*
     * Assign callback process level
     *
     * @param none
     * @return int
     */

    public function getCallbackProcessLevel()
    {
        if ($this->response->getPaymentType()) {
            // Assign callback process flag
            if (in_array($this->response->getPaymentType(), $this->arySubscription)
                || (in_array($this->response->getPaymentType(), $this->recurringAllowed)
                && ($this->response->hasSubsBilling()
                && $this->response->getSubsBilling() == 1))
            ) {
                $this->recurring = true;
            } else {
                $this->callback = true;
            }

            // Assign callback process level
            if (in_array($this->response->getPaymentType(), $this->paymentTypes)) {
                return 0;
            } else if (in_array($this->response->getPaymentType(), $this->chargebacks)) {
                return 1;
            } else if (in_array($this->response->getPaymentType(), $this->aryCollection)) {
                return 2;
            }
        } else {
            $this->showDebug('Required param (payment_type) missing!');
            return false;
        }
    }

    /**
     * Get order increment id
     *
     * @param  none
     * @return int $orderNo
     */
    public function getOrderIncrementId()
    {
        if ($this->recurring && !in_array($this->response->getPaymentType(), $this->arySubscription)) { // Get recurring profile increment id
            $orderNo = $this->getRecurringOrderId();
        } else {  // Get order increment id
            $orderNo = $this->response->getOrderNo() ? $this->response->getOrderNo() : '';
            $orderNo = $orderNo ? $orderNo : $this->getOrderIdByTransId();
        }

        if (!empty($orderNo)) {
            return $orderNo;
        } else {
            $this->showDebug('Required (Transaction ID) not Found!');
            return false;
        }
    }

    /**
     * Get increment id based on payment last transaction id
     *
     * @param  none
     * @return int
     */
    public function getOrderIdByTransId()
    {
        $parentTid = $this->getParentTid(); // Get the original/parent transaction id
        $tablePrefix = Mage::getConfig()->getTablePrefix();

        if (in_array($this->response->getPaymentType(), $this->chargebacks)) {
            $orderPayment = $tablePrefix . 'sales_payment_transaction';
            $onCondition = "main_table.entity_id = $orderPayment.order_id";
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('txn_id', array('like' => "%$parentTid%"))
                ->addFieldToSelect('increment_id');
        } else {
            $orderPayment = $tablePrefix . 'sales_flat_order_payment';
            $onCondition = "main_table.entity_id = $orderPayment.parent_id";
            $orderCollection = Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('last_trans_id', array('like' => "%$parentTid%"))
                ->addFieldToSelect('increment_id');
        }

        // Get order collection
        $orderCollection->getSelect()->join($orderPayment, $onCondition);
        $getSize = $orderCollection->getSize();
        $orderId = '';

        if ($getSize > 0) {
            foreach ($orderCollection as $order) {
                $orderId = $order->getIncrementId();
            }
        }

        return $orderId;
    }

    /**
     * Get subscription payment increment id
     *
     * @param  none
     * @return int $orderNo
     */
    public function getRecurringOrderId()
    {
        $orderNo = '';
        $profile = $this->getProfileInformation(); // Get the Recurring Profile Information
        $profileCollection = Mage::getResourceModel('sales/order_grid_collection')
            ->addRecurringProfilesFilter($profile->getId());
        foreach ($profileCollection as $profileValue) {
            $orderNo = $profileValue->getIncrementId();
        }

        return $orderNo;
    }

    /**
     * Get the Recurring Profile Information
     *
     * @param  none
     * @return Varien_Object $profile
     */
    public function getProfileInformation()
    {
        // Get the original/parent transaction id
        $tid = ($this->response->getSignupTid()) ? $this->response->getSignupTid() : $this->response->getTid();
        return Mage::getModel('sales/recurring_profile')->load($tid, 'reference_id');
    }

    /**
     * Get transaction id based on payment type
     *
     * @param  none
     * @return int $tid
     */
    public function getParentTid()
    {
        // Get the original/parent transaction id
        $tid = $this->response->getTid();

        if (in_array($this->response->getPaymentType(), array_merge($this->chargebacks, $this->aryCollection))) {
            $tid = $this->response->getTidPayment();
        }

        if (in_array($this->response->getPaymentType(), $this->arySubscription)
            || ($this->response->getSignupTid() && in_array($this->response->getPaymentType(), $this->recurringAllowed))) {
            $tid = trim($this->response->getSignupTid());
        }

        if ($this->response->getInstalmentTid()) {
            $tid = $this->response->getInstalmentTid();
        }

        return $tid;
    }

    /**
     * Check whether the ip address is authorised
     *
     * @param  none
     * @return boolean
     */
    public function checkIP()
    {
        $allowedIp = gethostbyname('pay-nn.de');

        if (empty($allowedIp)) {
            $this->showDebug('Novalnet HOST IP missing');
            return false;
        }

        $callerIp = $this->helper->getRealIpAddr();

        if ($callerIp != $allowedIp && !$this->test) {
            $this->showDebug('Novalnet callback received. Unauthorised access from the IP [' . $callerIp . ']');
            return false;
        }

        return true;
    }

    /**
     * Show callback process transaction comments
     *
     * @param  string  $text
     * @param  boolean $die
     * @return none
     */
    public function showDebug($text, $die = true)
    {
        $displayMessage = $this->getLayout()->getBlock('Callback')->hasAdditionMessage()
            ? $this->getLayout()->getBlock('Callback')->getAdditionMessage() . $text : $text;
        if ($die === false) {
            $this->getLayout()->getBlock('Callback')->setAdditionMessage($text);
        }

        $this->getLayout()->getBlock('Callback')->setDisplayError($displayMessage);
        if ($die) {
            $this->getLayout()->getBlock('Callback')->setExitProcess(true);
            $this->renderLayout();
        }
    }

    /**
     * Send callback notification E-mail
     *
     * @param  none
     * @return none
     */
    public function sendCallbackMail()
    {
        $this->getEmailConfig(); // Get email configuration settings

        if (!empty($this->emailBody) && $this->emailFromAddr && $this->emailToAddr) {
            if (!$this->sendEmailMagento()) {
                $this->showDebug('Mailing failed!' . $this->lineBreak, false);
                $this->showDebug('This mail text should be sent: ', false);
            }
        }

        if (!empty($this->emailBody)) {
            $this->showDebug($this->emailBody);
        }
    }

    /**
     * Send callback notification E-mail (with callback template)
     *
     * @param  none
     * @return boolean
     */
    public function sendEmailMagento()
    {
        /*
         * Loads the html file named 'novalnet_callback_email.html' from
         * E.G: app/locale/en_US/template/email/novalnet/novalnet_callback_email.html
         * OR:  app/locale/YourLanguage/template/email/novalnet/novalnet_callback_email.html
         * Adapt the corresponding template if necessary
         */
        $emailTemplate = Mage::getModel('core/email_template')
            ->loadDefault('novalnet_callback_email_template');

        // Define some variables to assign to template
        $templateParams = array();
        $templateParams['fromName'] = $this->emailFromName;
        $templateParams['fromEmail'] = $this->emailFromAddr;
        $templateParams['toName'] = $this->emailToName;
        $templateParams['toEmail'] = $this->emailToAddr;
        $templateParams['subject'] = $this->emailSubject;
        $templateParams['body'] = $this->emailBody;
        $template = $emailTemplate->getProcessedTemplate($templateParams);

        $mail = new Zend_Mail('UTF-8');
        $mail->setBodyHtml($template);
        $mail->setFrom($this->emailFromAddr, $this->emailFromName);
        $this->helper->assignEmailAddress($this->emailToAddr, $mail, 'To');
        $mail->setSubject($this->emailSubject);

        try {
            $mail->send();
            $this->showDebug(__FUNCTION__ . ': Sending Email succeeded!' . $this->lineBreak, false);
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError($this->helper->__('Unable to send email'));
            $this->showDebug('Email sending failed: ', false);
            return false;
        }

        return true;
    }

    /**
     * Set recurring profile state
     *
     * @param  string|null $state
     * @return none
     */
    public function setRecurringProfileState($state = 'Active')
    {
        $profileId = $this->response->hasProfileId()
            ? $this->response->getProfileId()
            : ($this->response->hasInput4() && $this->response->getInput4() == 'profile_id'
                ? $this->response->getInputval4()
                : ''
              );
        if ($profileId) {
            $profile = Mage::getModel('sales/recurring_profile')->load($profileId, 'profile_id');

            if ($state == 'Active') {
                $status = $this->response->getTidStatus();
                $message = $this->helper->getModel('Recurring_Payment')->createIpnComment($status, $this->order);
                $this->payment->setPreparedMessage($message)
                    ->setAdditionalInformation('subs_id', $this->response->getSubsId())->save();
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE); //Set profile status as active
            } else {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED); //Set profile status as canceled
            }

            $profile->setReferenceId($this->response->getTid())->save();
        }
    }

    /**
     * Check change payment method
     *
     * @param  none
     * @return boolean
     */
    public function changePaymentMethod()
    {
        $paymentType = $this->allowedPayment[strtolower($this->code)];

        if (!in_array($this->response->getPaymentType(), $paymentType)) {
            if ($this->response->getSignupTid() && $this->response->getSubsBilling() != 1
                && in_array($this->response->getPaymentType(), $this->recurringAllowed) && $this->response->getStatus() == 100) {
                $paymentList = array('INVOICE_START' => 'novalnetInvoice', 'CREDITCARD' => 'novalnetCc',
                    'DIRECT_DEBIT_SEPA' => 'novalnetSepa', 'GUARANTEED_DIRECT_DEBIT_SEPA' => 'novalnetSepa',
                    'PAYPAL' => 'novalnetPaypal', 'GUARANTEED_INVOICE' => 'novalnetInvoice');
                $newPaymentCode = $paymentList[$this->response->getPaymentType()];
                $newPaymentCode = ($newPaymentCode == 'novalnetInvoice' && strtolower($this->response->getInvoiceType()) == 'prepayment')
                    ? 'novalnetPrepayment' : $newPaymentCode;
                $this->payment->setMethod($newPaymentCode)
                              ->save();
                $script = $this->helper->__('Novalnet callback script received. Subscription payment has been changed from %s to %s on %s.', $this->code, $newPaymentCode, $this->currentTime);
                $data = unserialize($this->payment->getAdditionalData());
                $data['NnComments'] = empty($data['NnComments'])
                    ? '<br />' . $script . '<br />' : $data['NnComments'] . '<br />' . $script . '<br />';
                $this->payment->setAdditionalData(serialize($data))->save();
                $newOrder = Mage::getModel('sales/order')->loadByIncrementId($this->orderNo);
                $recurringProfile = $this->getProfileInformation();
                $recurringProfile->setMethodCode($newOrder->getPayment()->getMethodInstance()->getCode())
                                 ->save();
                $paidUntil = $this->response->hasNextSubsCycle()
                    ? $this->response->getNextSubsCycle()
                    : ($this->response->hasPaidUntil() ? $this->response->getPaidUntil() : '');

                $orderNo = $this->getRecurringOrderId();
                $lastOrder = Mage::getModel('sales/order')->loadByIncrementId($orderNo);
                $payment = $lastOrder->getPayment();
                $lastOrderData = unserialize($payment->getAdditionalData());
                if ($paidUntil) {
                    $lastOrderData['paidUntil'] = Mage::helper('core')->formatDate($paidUntil);
                }

                $payment->setMethod($newPaymentCode)
                        ->setAdditionalData(serialize($lastOrderData))
                        ->save();
                $lastOrder->save();
                $this->showDebug($script);
            } else {
                $this->showDebug(
                    'Novalnet callback received. Payment type ( ' . $this->response->getPaymentType()
                    . ' ) is not matched with ' . $this->code . '!'
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Send critical error mail to Novalnet
     *
     * @param  none
     * @return none
     */
    public function sendCriticalErrorMail()
    {
        if (in_array($this->response->getStatus(), array(100, 90))) {
            $this->getEmailConfig(); // Get email configuration settings
            $emailTemplate = Mage::getModel('core/email_template')
            ->loadDefault('novalnet_callback_critical_email_template');
            $mailSubject = 'Critical error on shop system ' . Mage::app()->getStore()->getFrontendName() . ': order not found for TID: ' . $this->getParentTid();

            // Define some variables to assign to template
            $templateParams = array(
                'toName'      => 'Technic team',
                'message'     => 'Please evaluate this transaction and contact our payment module team at Novalnet.',
                'merchantId'  => $this->response->getVendorId(),
                'productId'   => $this->response->getProductId(),
                'tid'         => $this->response->getTid(),
                'tidStatus'   => $this->response->getTidStatus(),
                'orderNo'     => $this->response->getOrderNo(),
                'paymentType' => $this->response->getPaymentType(),
                'endUserMail' => $this->response->getEmail(),
                'regards'     => 'Novalnet Team'
            );
            $template = $emailTemplate->getProcessedTemplate($templateParams);

            $mail = new Zend_Mail('UTF-8');
            $mail->setBodyHtml($template);
            $mail->setFrom($this->emailFromAddr, $this->emailFromName);
            $this->helper->assignEmailAddress($this->technicNotifyMail, $mail, 'To');
            $mail->setSubject($this->emailSubject);

            try {
                $mail->send();
                $this->showDebug(__FUNCTION__ . ': Sending Email succeeded!' . $this->lineBreak, false);
            } catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->helper->__('Unable to send email'));
                $this->showDebug('Email sending failed: ', false);
                return false;
            }

            return true;
        }
    }

    /**
     * Check transaction cancellation
     *
     * @param  none
     * @return none
     */
    public function transactionCancellation()
    {

        $transactionId = $this->getParentTid();
        $transactionStatus = $this->helper->getModel('Mysql4_TransactionStatus')
                ->loadByAttribute('transaction_no', $transactionId);
        $paymentStatus = $transactionStatus->getTransactionStatus(); // Get payment original transaction status
        if ($this->response->getPaymentType() == 'TRANSACTION_CANCELLATION' && $paymentStatus == '103') {
            $this->showDebug('Novalnet callback received. Callback Script executed already. Refer Order :' . $this->orderNo);
            return true;
        }
        if ($this->response->getPaymentType() == 'TRANSACTION_CANCELLATION' && $paymentStatus != '103') {
            $this->responseModel->saveCanceledOrder($this->response, $this->order, $this->response->getTestMode());
            $script = $this->emailBody = $this->helper->__('Novalnet callback received. The transaction has been canceled on %s',
                $this->currentTime
            );
            $data = unserialize($this->payment->getAdditionalData());
            $data['NnComments'] = empty($data['NnComments'])
                ? '<br />' . $script : $data['NnComments'] . '<br /><br />' . $script;
            $this->payment->setAdditionalData(serialize($data))->save();
            $transactionStatus->setTransactionStatus($this->response->getTidStatus())->save();
            $this->sendCallbackMail();
            return true;
        }

        return false;
    }

}
