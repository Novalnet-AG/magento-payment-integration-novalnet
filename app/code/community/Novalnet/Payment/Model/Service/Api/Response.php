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
class Novalnet_Payment_Model_Service_Api_Response extends Novalnet_Payment_Model_Service_Abstract
{

    /**
     * Validate the Novalnet payment response
     *
     * @param  Varien_Object $payment
     * @param  Varien_Object $request
     * @param  Varien_Object $response
     * @return Novalnet_Payment_Model_Service_Api_Response | none
     */
    public function validateResponse($payment, $request, $response)
    {
        // Novalnet successful transaction status || Novalnet PayPal pending status
        if (in_array($response->getStatus(),
          array(Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED, Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS))) {
            $order = $payment->getOrder(); // Get payment order
            $this->code = $payment->getMethodInstance()->getCode(); // Get payment method code

            // Novalnet payment mode from global configuration
            $shopMode = $this->getNovalnetConfig('live_mode', true);
            // Get Novalnet payment transaction mode
            $testMode = (int)($response->getTestMode() == 1 || $shopMode == 0);
            $this->logTransactionStatus($response, $order); // Log Novalnet payment transaction informations
            // Log Novalnet payment transaction traces informations
            $this->logTransactionTraces($request, $response, $order);

            // Get Novalnet transaction details
            $data = $this->getPaymentAddtionaldata($response, $request, $testMode);
            $this->saveAdditionalData($order, $data); // Save Novalnet payment additional informations
            $this->setRecurringState($response, $order); // Assign recurring profile state if profile exist
            $this->paymentAuthorize($order, $response); // Capture the payment based on real transaction status
            $this->logAffiliateUserInfo($request); // Log affiliate user information
            $statusText = $this->getStatusText($response); // Get payment transaction status message
            $this->_helper->getCoreSession()->addSuccess($statusText);
            $this->_helper->unsetMethodSession($this->code);
            return $this;
        } else {// Novalnet unsuccessful transaction status
            $statusMessage = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
            $this->_helper->showException($statusMessage);
        }
    }

    /**
     * Authorize payment
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $response
     * @return none
     */
    public function paymentAuthorize($order, $response)
    {
        $payment = $order->getPayment(); // Get payment object
        $transactionId = $response->getTid(); // Get Novalnet transaction id
        $payment->setIsTransactionClosed(false)->save();

        // Capture the payment only if status (original status - tid_status) is 100
        if ($order->canInvoice()
            && (!in_array($this->code, array(Novalnet_Payment_Model_Config::NN_PREPAYMENT, Novalnet_Payment_Model_Config::NN_CASHPAYMENT)))
            && $response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
        ) {
            $payment->setParentTransactionId($transactionId)
                ->setIsTransactionClosed(true) // Close the transaction
                ->capture(null)
                ->save();
        }

        // Save TID details
        $payment->setTransactionId($transactionId)
            ->setLastTransId($transactionId)
            ->setParentTransactionId(null)
            ->save();
    }

    /**
     * Log affiliate user details
     *
     * @param  Varien_Object $request
     * @return none
     */
    public function logAffiliateUserInfo($request)
    {
        if ($affiliateId = $this->_helper->getCoreSession()->getAffiliateId()) { // Get affiliate user id if exist
            $affiliateUserInfo = $this->_helper->getModel('Mysql4_AffiliateUser');
            $affiliateUserInfo->setAffId($affiliateId)
                ->setCustomerNo($request->getCustomerNo())
                ->setAffOrderNo($request->getOrderNo())
                ->save();
            $this->_helper->getCoresession()->unsAffiliateId();
        }
    }

    /**
     * Get Payment method additional informations
     *
     * @param Varien_Object $response
     * @param Varien_Object $request
     * @param int           $testMode
     * @param array
     */
    public function getPaymentAddtionaldata($response, $request, $testMode)
    {
        $data = array('NnTestOrder' => $testMode, 'NnTid' => trim($response->getTid()),
            'vendor' => $request->getVendor(), 'auth_code' => $request->getAuthCode(),
            'product' => $request->getProduct(), 'tariff' => $request->getTariff(),
            'payment_id' => $request->getKey(), 'accessKey' => $this->accessKey,
            'nnLang' => Mage::getSingleton('core/translate')->getLocale()
        );
        if (in_array($this->code, array(Novalnet_Payment_Model_Config::NN_INVOICE, Novalnet_Payment_Model_Config::NN_PREPAYMENT, Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT))) {
            $amount = Mage::helper('core')->currency($response->getInstalmentCycleAmount() ? $response->getInstalmentCycleAmount() : $response->getAmount(), true, false);
            $data['NnNote'] = $this->getInvoicePaymentNote($response);
            $data['NnDueDate'] = $response->getDueDate();
            $data['NnNoteAmount'] = 'NN_Amount: ' . $amount;
            $data['NnNoteTID'] = $this->getReferenceDetails($response, $request);
        }

        if (in_array($request->getKey(), array(Novalnet_Payment_Model_Config::INVOICE_PAYMENT_GUARANTEE_KEY, Novalnet_Payment_Model_Config::SEPA_PAYMENT_GUARANTEE_KEY))) {
            $data['NnGuarantee'] = 1;
        }

        if ($this->code == Novalnet_Payment_Model_Config::NN_CASHPAYMENT) {
            $data['CpDueDate'] = Mage::helper('core')->formatDate($response->getCpDueDate());
            $data['cpCheckoutToken'] = $response->getCpCheckoutToken();
            $strnos = '';
            $cashpaymentStores = array();
            foreach ($response->getData() as $key => $val) {
                if (strpos($key, 'nearest_store_title') !== FALSE){
                    $strnos++;
                }
            }

            for ($i = 1; $i <= $strnos; $i++) {
                $countryName = !empty($response['nearest_store_country_' . $i])
                    ? Mage::getModel('directory/country')->loadByCode($response['nearest_store_country_' . $i])->getName()
                    : '';
                $cashpaymentStores[] = array(
                    'title'   => $response['nearest_store_title_' . $i],
                    'country' => $countryName,
                    'street' => $response['nearest_store_street_'. $i],
                    'city'    => $response['nearest_store_city_' . $i],
                    'zipcode' => $response['nearest_store_zipcode_' . $i]
                );
            }

            $data['CashpaymentStores'] = $cashpaymentStores;
        }

        if (in_array($this->code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))) {
            $data['InstallPaidAmount'] = $response->getInstalmentCycleAmount();
            $data['InstallDueAmount'] = $this->_helper->getFormatedAmount($request->getAmount(), 'RAW') - $response->getInstalmentCycleAmount();
            $data['PaidInstall'] = $response->getInstalmentCyclesExecuted();
            $data['DueInstall'] = $response->getDueInstalmentCycles();
            $data['NextCycle'] = $response->getNextInstalmentDate();
            $data['InstallCycleAmount'] = $response->getInstalmentCycleAmount();

            if ($futureInstalment = $response->getFutureInstalmentDates()) {
                $futureInstalments = explode('|', $futureInstalment);
                foreach ($futureInstalments as $futureInstalment) {
                    $cycle = strtok($futureInstalment, "-");
                    $cycleDate = explode('-', $futureInstalment, 2);
                    $data['InstalmentDetails'][$cycle] = array('amount' => $response->getInstalmentCycleAmount(),
                        'nextCycle' => $cycleDate[1],
                        'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                        'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                        'reference' => ($cycle == 1) ? $response->getTid() : '');
                }
            }

            $data['InstalmentDetails'][1] = array('amount' => $response->getInstalmentCycleAmount(),
                'nextCycle' => $response->getNextInstalmentDate(),
                'paidDate' => date('Y-m-d'),
                'status' => 'Paid',
                'reference' => $response->getTid());
        }

        return $data;
    }

    /**
     * Get Novalnet invoice payments bank account details
     *
     * @param  array $response
     * @return mixed
     */
    public function getInvoicePaymentNote($response)
    {
        $dueDate = $response->getDueDate() ? Mage::helper('core')->formatDate($response->getDueDate()) : '';
        $note = null;
        $note .= 'Due Date: ' . $dueDate . '|NN Account Holder: ' . $response->getInvoiceAccountHolder();
        $note .= '|IBAN: ' . $response->getInvoiceIban();
        $note .= '|BIC: ' . $response->getInvoiceBic();
        $note .= '|NN_Bank: ' . $response->getInvoiceBankname() . ' ' . $response->getInvoiceBankplace();
        return $note;
    }

    /**
     * Get Novalnet invoice payment reference details
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $request
     * @return string
     */
    public function getReferenceDetails($response, $request)
    {
        $transactionId = trim($response->getTid());
        $note = "NN_Reference_desc1:|NN_Reference1:TID $transactionId";
        if (!$response->getInstalmentCycleAmount()) {
            $invoiceRef = $request->getInvoiceRef();
            $note .= "|NN_Reference2:$invoiceRef";
        }

        return $note;
    }

    /**
     * Remove sensitive data form Novalnet request
     *
     * @param  Varien_Object $request
     * @param  string        $paymentCode
     * @return Varien_Object $request
     */
    public function removeSensitiveData($request, $paymentCode)
    {
        if ($paymentCode) {
            switch ($paymentCode) {
                case Novalnet_Payment_Model_Config::NN_CC:
                    $request->unsPanHash()
                        ->unsUniqueId();
                    break;
                case Novalnet_Payment_Model_Config::NN_SEPA:
                    $request->unsBankAccountHolder()
                        ->unsIban();
                    break;
            }
        }

        return $request;
    }

    /**
     * Save payment additional informations
     *
     * @param  Varien_Object $order
     * @param  array         $data
     * @return none
     */
    public function saveAdditionalData($order, $data)
    {
        $payment = $order->getPayment(); // Get payment object
        // Save additional transaction informations
        $payment->setIsTransactionClosed(false)
            ->setAdditionalData(serialize($data))
            ->save();
        $order->setPayment($payment);
        $order->save();
    }

    /**
     * Save payment informations
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $response
     * @return none
     */
    public function capturePayment($order, $response)
    {
        $payment = $order->getPayment(); // Get payment object
        $paymentCode = $payment->getMethodInstance()->getCode(); // Payment method code
        $transactionId = $response->getTid(); // Get Novalnet transaction id
        // Save TID details
        $payment->setTransactionId($transactionId)
            ->setLastTransId($transactionId)
            ->setParentTransactionId(null);
        // Capture the payment only if status (original status - tid_status) is 100
        if ($order->canInvoice()
            && (!in_array($paymentCode, array(Novalnet_Payment_Model_Config::NN_PREPAYMENT, Novalnet_Payment_Model_Config::NN_CASHPAYMENT)))
            && $response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
        ) {
            $captureMode = (version_compare($this->_helper->getMagentoVersion(), '1.6', '<')) ? false : true;
            $payment->setTransactionId($transactionId) // Add capture text to make the new transaction
                ->setIsTransactionClosed($captureMode) // Close the transaction
                ->capture(null);
        }

        $payment->save();
        $order->setPayment($payment)->save();

        if (in_array($response->getTidStatus(), array(85, 91, 98, 99))) {
            $payment->setTransactionId($transactionId)
                ->setIsTransactionClosed(false);
            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, false);
            $transaction->setParentTxnId(null)
                ->save();
        }
    }

    /**
     * Get response status message
     *
     * @param  Varien_Object $result
     * @return string
     */
    public function getStatusText(Varien_Object $result)
    {
        return $result->getStatusMessage()
            ? $result->getStatusMessage()
            : ($result->getStatusDesc()
                ? $result->getStatusDesc()
                : ($result->getStatusText() ? $result->getStatusText() : $this->_helper->__('successful'))
              );
    }

    /**
     * Get response status message for failure payments
     *
     * @param  Varien_Object $result
     * @return string
     */
    public function getUnSuccessPaymentText(Varien_Object $result)
    {
        return $result->getStatusMessage()
            ? $result->getStatusMessage()
            : ($result->getStatusDesc()
                ? $result->getStatusDesc()
                : ($result->getStatusText()
                    ? $result->getStatusText()
                    : $this->_helper->__('Payment was not successfull')
                  )
              );
    }

    /**
     * Save canceled payment transaction
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @param  int           $testMode
     * @return none
     */
    public function saveCanceledOrder($response, $order, $testMode)
    {
        $payment = $order->getPayment();
        $statusMessage = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
        $payStatus = "<b><font color='red'>" . $this->_helper->__('Payment Failed') .
            "</font> - " . $statusMessage . "</b>";
        $data = unserialize($payment->getAdditionalData());
        $data['NnTid'] = $response->getTid();
        $data['NnTestOrder'] = $testMode;
        $data['NnComments'] = empty($data['NnComments']) ? $payStatus : $data['NnComments'] . '<br />' . $payStatus;
        $payment->setLastTransId($response->getTid())
            ->setAdditionalData(serialize($data))
            ->save();
        $order->registerCancellation($statusMessage)
            ->save();
        Mage::dispatchEvent('sales_order_payment_void', array('payment' => $payment));
    }

    /**
     * Checking Novalnet response data for redirect payments
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @return boolean
     */
    public function checkReturnedData($response, $order)
    {
        $status = false;
        $payment = $order->getPayment(); // Get payment object
        $paymentObj = $payment->getMethodInstance(); // Get payment method instance
        $this->code = $paymentObj->getCode(); // Get payment method code
        $this->_helper->getCheckout()->getQuote()->setIsActive(true)->save();

        // Unhold an order
        if ($order->canUnhold()) {
            $order->unhold()->save();
        }

        $checkHash = $this->checkHash($response); // Validate hash value if exist
        $this->getDecodedParams($response); // Get decoded payment params if exist
        $shopMode = $this->getNovalnetConfig('live_mode', true); // Novalnet payment mode from global configuration
        $testMode = (int)($response->getTestMode() == 1 || $shopMode == 0); // Get Novalnet payment mode

        if (!$checkHash) { // Cancel the order when hash value mismatched
            $response->setStatusMessage($this->_helper->__('checkHash failed'));
            $this->saveCanceledOrder($response, $order, $testMode); // Cancel the order for failure transaction
            $this->_helper->getCoreSession()->addError($response->getStatusMessage());
            return false;
        }

        $request = $this->_helper->getMethodSession($this->code)->getPaymentReqData(); // Get payment request params
        $this->logTransactionStatus($response, $order); // Log Novalnet payment transaction informations

        if ($response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
            || $response->getTidStatus() == Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS
        ) { // Novalnet successful transaction status
            $successActionFlag = $this->code . '_successAction';
            if ($payment->getAdditionalInformation($successActionFlag) != 1) {
                $payment->setAdditionalInformation($successActionFlag, 1)->save();
                // Assign recurring profile state if profile exist
                $this->setRecurringState($response, $order);
                // save payment additional informations
                $data = array('NnTestOrder' => $testMode, 'NnTid' => trim($response->getTid()),
                    'vendor' => $response->getVendor(),
                    'auth_code' => $response->getAuthCode(),
                    'product' => $response->getProduct(),
                    'tariff' => $response->getTariff(),
                    'payment_id' => $request->getKey(), 'accessKey' => $this->accessKey,
                    'nnLang' => Mage::getSingleton('core/translate')->getLocale()
                );
                $paidUntil = $response->getNextSubsCycle()
                    ? $response->getNextSubsCycle()
                    : ($response->getPaidUntil() ? $response->getPaidUntil() : '');
                $data['paidUntil'] = $paidUntil ? Mage::helper('core')->formatDate($paidUntil) : '';
                $this->saveAdditionalData($order, $data);
                // save transaction informations to order
                $this->saveSuccessOrder($order, $request, $response);
            }

            $status = true;
        } else {  // Novalnet unsuccessful transaction status
            $this->setRecurringState($response, '', 'canceled'); // Assign recurring profile state if profile exist
            $this->saveCanceledOrder($response, $order, $testMode); // Cancel the order for failure transaction
            $statusMessage = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
            $this->_helper->getCoreSession()->addError($statusMessage);
            $status = false;
        }

        $this->_helper->unsetMethodSession($this->code); // Unset payment method session
        Mage::unregister('payment_code'); // Unregister the payment code
        $order->save();
        return $status;
    }

    /**
     * validate the Novalnet response and save the order
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $request
     * @param  Varien_Object $response
     * @return none
     */
    public function saveSuccessOrder($order, $request, $response)
    {
        $payment = $order->getPayment(); // Get payment object
        $paymentObj = $payment->getMethodInstance(); // Get payment method instance
        $this->code = $paymentObj->getCode();
        $this->capturePayment($order, $response); // Capture the transaction
        $this->logAffiliateUserInfo($request); // Log affiliate user information
        if($this->code == Novalnet_Payment_Model_Config::NN_CC) {
            $orderStatus = $this->getNovalnetConfig('order_status') 
                ? $this->getNovalnetConfig('order_status')
                : Mage_Sales_Model_Order::STATE_PROCESSING;
        } else {
            $orderStatus = $this->getNovalnetConfig('order_status_after_payment')
                ? $this->getNovalnetConfig('order_status_after_payment')
                : Mage_Sales_Model_Order::STATE_PROCESSING;
        }
        if (in_array(
            $response->getTidStatus(), array(Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS,
            Novalnet_Payment_Model_Config::PAYPAL_ONHOLD_STATUS,
            Novalnet_Payment_Model_Config::PRZELEWY_PENDING_STATUS)
        )) {
            $orderStatus = $this->getNovalnetConfig('order_status')
                ? $this->getNovalnetConfig('order_status') : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
            
            if ($response->getTidStatus() == Novalnet_Payment_Model_Config::PAYPAL_ONHOLD_STATUS) {
                $orderStatus = Mage::getStoreConfig(
                    'novalnet_global/order_status_mapping/order_status',
                    $order->getStoreId()
                );
            }
        } elseif ($response->getTidStatus() == Novalnet_Payment_Model_Config::CC_ONHOLD_STATUS) {
            $orderStatus = Mage::getStoreConfig(
                'novalnet_global/order_status_mapping/order_status',
                $order->getStoreId()
            );
        }

        $message = $this->_helper->__('Customer successfully returned from Novalnet');
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatus, $message, true)->save();
        $statusText = $this->getStatusText($response); // Get payment response status text
        $this->_helper->getCoreSession()->addSuccess($statusText);
    }

    /**
     * Save the failure transaction
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @return none
     */
    public function checkErrorReturnedData($response, $order)
    {
        $payment = $order->getPayment(); // Get payment object
        $this->code = $payment->getMethodInstance()->getCode(); // Get payment method code
        $this->_helper->getCheckout()->getQuote()->setIsActive(false)->save();
        $this->getDecodedParams($response); // Get decoded payment data
        $shopMode = $this->getNovalnetConfig('live_mode', true); // Novalnet payment mode from global configuration
        $testMode = (int)($response->getTestMode() == 1 || $shopMode == 0); // Get Novalnet payment mode
        //Unhold an order
        if ($order->canUnhold()) {
            $order->unhold()->save();
        }

        // Assign recurring profile state if profile exist
        $this->setRecurringState($response, '', 'canceled');

        // Cancel the order
        $errorActionFlag = $this->code . '_errorAction';
        if ($payment->getAdditionalInformation($errorActionFlag) != 1) {
            $this->logTransactionStatus($response, $order); // Log Novalnet payment transaction informations
            $payment->setAdditionalInformation($errorActionFlag, 1); // Get payment request params
            $this->saveCanceledOrder($response, $order, $testMode); // Cancel the order for failure transaction
            $statusMessage = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
            $this->_helper->getCoreSession()->addError($statusMessage);
            $this->_helper->unsetMethodSession($this->code); // Unset payment method session
            Mage::unregister('payment_code'); // Unregister the payment code
        }
    }

    /**
     * Set recurring profile state
     *
     * @param  Varien_Object      $response
     * @param  Varien_Object|null $order
     * @param  string|null        $state
     * @return none
     */
    public function setRecurringState($response, $order, $state = 'Active')
    {
        $profileId = $response->hasProfileId()
            ? $response->getProfileId()
            : ($response->hasInput4() && $response->getInput4() == 'profile_id' ? $response->getInputval4() : '');

        if ($profileId) {
            $profile = Mage::getModel('sales/recurring_profile')->load($profileId, 'profile_id');

            if ($state == 'Active') {
                $payment = $order->getPayment();
                $recurringModel = $this->_helper->getModel('Recurring_Payment');
                $message = $recurringModel->createIpnComment($response->getTidStatus(), $order);
                $payment->setPreparedMessage($message)
                    ->setAdditionalInformation('subs_id', $response->getSubsId())->save();
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_ACTIVE);
            } else {
                $profile->setState(Mage_Sales_Model_Recurring_Profile::STATE_CANCELED);
            }

            $profile->setReferenceId($response->getTid())->save();
        }
    }

    /**
     * Log Novalnet transaction status data
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @return none
     */
    public function logTransactionStatus($response, $order)
    {
        $amount = str_replace(array('.', ','), '', $response->getAmount()); // Get amount
        $maskedAccountInfo = $this->saveMaskedAccountInfo($response); // Get masked card/account data
        $transactionStatus = $this->_helper->getModel('Mysql4_TransactionStatus'); // Get transaction status model
        $paymentCode = $order->getPayment()->getMethodInstance()->getCode(); // Get payment method code
        $transactionStatus->setTransactionNo(trim($response->getTid()))
            ->setOrderId($order->getIncrementId())
            ->setTransactionStatus($response->getTidStatus())
            ->setNcNo($response->getNcNo())
            ->setCustomerId($order->getCustomerId())
            ->setPaymentName($paymentCode)
            ->setAmount($amount)
            ->setRemoteIp($this->_helper->getRealIpAddr())
            ->setStoreId($order->getStoreId())
            ->setShopUrl($this->_helper->getBaseUrl())
            ->setCreatedDate($this->_helper->getCurrentDateTime())
            ->setNovalnetAccDetails($maskedAccountInfo)
            ->setReferenceTransaction($this->refTransaction)
            ->save();
    }

    /**
     * Get masked card/account data
     *
     * @param  Varien_Object $response
     * @return array         $maskedInfo
     */
    public function saveMaskedAccountInfo($response)
    {
        $maskedInfo = '';
        $methodSession = $this->_helper->getMethodSession($this->code);
        $this->refTransaction = 1;

        if ($this->code == Novalnet_Payment_Model_Config::NN_CC
            && $methodSession->getNnCcSaveCard()
            && $this->getNovalnetConfig('cc_shop_type')
            && !$this->_helper->checkIsAdmin()
            && in_array($response->getTidStatus(), array(100, 98))
        ) {
            $this->refTransaction = (int)(bool)($methodSession->getNnCcTid());
            $maskedInfo = $this->getMaskedCardInfo($response); // Get masked card data
        } elseif ($this->code == Novalnet_Payment_Model_Config::NN_SEPA
            && !$this->_helper->checkIsAdmin()
            && $methodSession->getSepaSaveAccount()
            && $methodSession->getPaymentReqData()->hasIban()
            && in_array($response->getTidStatus(), array(100, 99, 75))
        ) {
            $this->refTransaction = (int)(bool)($methodSession->getNnSepaTid());
            $maskedInfo = $this->getMaskedAccountInfo($response, $methodSession); // Get masked account data
        } elseif ($this->code == Novalnet_Payment_Model_Config::NN_PAYPAL
            && $this->getNovalnetConfig('paypal_shop_type')
            && in_array($response->getTidStatus(), array(100, 90, 85))
        ) {
            $this->refTransaction = (int)(bool)($methodSession->getNnRefTid());
            $transAccInfo = array(
                'paypal_tid' => $response->getPaypalTransactionId(),
                'nn_tid' => $response->getTid()
            );
            $maskedInfo = base64_encode(serialize($transAccInfo));
        }

        return $maskedInfo;
    }

    /**
     * Get masked card data
     *
     * @param  Varien_Object $response
     * @return string        $maskedCardInfo
     */
    public function getMaskedCardInfo($response)
    {
        $maskedCardInfo = array(
            'card_type' => $response->getCcCardType(),
            'card_holder' => $response->getCcHolder(),
            'cc_no' => $response->getCcNo(),
            'exp_month' => $response->getCcExpMonth(),
            'exp_year' => $response->getCcExpYear(),
            'nn_tid' => $response->getTid(),
            'nn_amount' => $response->getAmount()
        );
        return base64_encode(serialize($maskedCardInfo));
    }

    /**
     * Get masked account data
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $methodSession
     * @return string        $maskedAccountInfo
     */
    public function getMaskedAccountInfo($response, $methodSession)
    {
        $maskedAccountInfo = array(
            'account_holder' => $response->getBankaccountHolder(),
            'iban' => $response->getIban(),
            'nn_tid' => $response->getTid(),
            'nn_amount' => $response->getAmount()
        );
        return base64_encode(serialize($maskedAccountInfo));
    }

    /**
     * Log Novalnet payment response data
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @param  string        $transactionId
     * @return none
     */
    public function logTransactionTraces($request, $response, $order, $transactionId = null)
    {
        $transactionId = ($transactionId != null) ? $transactionId : $response->getTid(); // Novalnet transaction id
        // Remove the sensitive data from payment request
        $request = $this->removeSensitiveData($request, $order->getPayment()->getMethodInstance()->getCode());
        $transactionTraces = $this->_helper->getModel('Mysql4_TransactionTraces'); // Get transaction traces model
        $transactionTraces->setTransactionId(trim($transactionId))
            ->setOrderId($order->getIncrementId())
            ->setRequestData(base64_encode(serialize($request->getData())))
            ->setResponseData(base64_encode(serialize($response->getData())))
            ->setCustomerId($order->getCustomerId())
            ->setStatus($response->getStatus())
            ->setStoreId($order->getStoreId())
            ->setShopUrl($request->getSystemUrl())
            ->setCreatedDate($this->_helper->getCurrentDateTime())
            ->save();
    }

    /**
     * Send process (capture/void/refund) request
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $payment
     * @param  string        $type
     * @param  float|NULL    $amount
     * @return none
     */
    public function postProcessRequest($request, $payment, $type, $amount = null)
    {
        $paymentObj = $payment->getMethodInstance(); // Get payment method instance
        $this->code = $paymentObj->getCode(); // Get payment method code
        $response = $paymentObj->postRequest($request); // send process request to Novalnet gateway
        // log payment capture traces
        $this->logTransactionTraces($request, $response, $payment->getOrder(), $request->getTid());
        // set profile state
        $nominalItem = $this->_helper->checkNominalItem($payment->getOrder());
        if ($nominalItem && $response->getTidStatus() == Novalnet_Payment_Model_Config::PAYMENT_VOID_STATUS
        ) {
            $profile = Mage::getModel('sales/recurring_profile')->load($request->getTid(), 'reference_id');
            if ($profile->hasReferenceId()) {
                $profile->setState('canceled')->save(); // Set profile status as canceled
            }
        }

        // Novalnet successful transaction status
        if ($response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
            if ($type == 'capture' || $type == 'void') {
                // get current payment status
                $transactionId = $this->_helper->makeValidNumber($payment->getLastTransId()); // payment transaction id
                $transactionStatusModel = $this->_helper->getModel('Mysql4_TransactionStatus');
                $transactionStatus = $transactionStatusModel->loadByAttribute('transaction_no', $transactionId);
                $paymentStatus = $transactionStatus->getTransactionStatus();

                if ($type == 'capture') {
                    $data = unserialize($payment->getAdditionalData());
                    if ($response->getInstalmentCyclesExecuted()) {
                        $data['PaidInstall'] = $response->getInstalmentCyclesExecuted();
                        $data['DueInstall'] = $response->getDueInstalmentCycles();
                        $data['NextCycle'] = $response->getNextInstalmentDate();
                        if ($futureInstalment = $response->getFutureInstalmentDates()) {
                            $futureInstalments = explode('|', $futureInstalment);
                            foreach ($futureInstalments as $futureInstalment) {
                                $cycle = strtok($futureInstalment, "-");
                                $cycleDate = explode('-', $futureInstalment, 2);
                                $data['InstalmentDetails'][$cycle] = array('amount' => $data['InstallPaidAmount'],
                                    'nextCycle' => $cycleDate[1],
                                    'paidDate' => ($cycle == 1) ? date('Y-m-d') : '',
                                    'status' => ($cycle == 1) ? 'Paid' : 'Pending',
                                    'reference' => ($cycle == 1) ? $request->getTid() : '');
                            }
                        }

                        $data['InstalmentDetails'][1]['nextCycle'] = $response->getNextInstalmentDate();
                    }

                    if ($payment->getMethodInstance()->getCode() == Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT
                      && $response->getDueDate()) {
                        $formatDate = Mage::helper('core')->formatDate($response->getDueDate());
                        $note = explode('|', $data['NnNote']);
                        $note[0] = 'Due Date: ' . $formatDate;
                        $data['NnNote'] = implode('|', $note);
                        $data['NnDueDate'] = $response->getDueDate();
                    }

                    $payment->setAdditionalData(serialize($data));
                }

                // set payment transaction informations
                $this->saveProcessTransInfo($payment, $paymentStatus, $request, $type);
                // reset payment transaction status
                $transactionStatus->setTransactionStatus($response->getTidStatus());
                if ($this->code == Novalnet_Payment_Model_Config::NN_PAYPAL) {
                    if ($this->getNovalnetConfig('paypal_shop_type') && $response->getPaypalTransactionId()) {
                        $transAccInfo = array(
                            'paypal_tid' => $response->getPaypalTransactionId(),
                            'nn_tid' => $request->getTid()
                        );
                        $transactionStatus->setNovalnetAccDetails(base64_encode(serialize($transAccInfo)))
                            ->setReferenceTransaction(0);
                    }

                    if ($response->getTidStatus() == Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS) {
                        $coreSession = $this->_helper->getCoreSession();
                        $coreSession->setPaypalOnholdFlag(1);
                    }
                }

                $transactionStatus->save();
            } else {
                // set payment refund transaction informations
                $this->saveRefundTransInfo($payment, $amount, $request, $response);
            }
        } else { // Novalnet unsuccessful transaction status
            $statusMessage = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
            $this->_helper->showException($statusMessage);
        }
    }

    /**
     * Verify the process (capture/void) response
     *
     * @param  Varien_Object $payment
     * @param  int           $paymentStatus
     * @param  Varien_Object $request
     * @param  string        $type
     * @return none
     */
    public function saveProcessTransInfo($payment, $paymentStatus, $request, $type)
    {
        $transactionId = $this->_helper->makeValidNumber($payment->getLastTransId()); // Novalnet transaction id
        $magentoVersion = $this->_helper->getMagentoVersion(); // Get installed Magento shop system version
        // Save additional informations
        $data = unserialize($payment->getAdditionalData());
        $data[$type . 'Tid'] = $request->getTid();
        $data[$type . 'CreateAt'] = $this->_helper->getCurrentDateTime();
        $payment->setAdditionalData(serialize($data))->save();

        if ($type == 'capture' && $paymentStatus != Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
        ) { // Novalnet successful transaction status
            // Make capture transaction open for lower versions to make refund
            if (version_compare($magentoVersion, '1.6', '<')) {
                $payment->setIsTransactionClosed(false)->save();
            }
        } elseif ($type == 'void') {
            // Save void transaction information
            $payment->setTransactionId($transactionId)
                ->setLastTransId($transactionId)
                ->save();
        }
    }

    /**
     * Save refund additional transaction information
     *
     * @param  Varien_Object $payment
     * @param  float         $amount
     * @param  Varien_Object $request
     * @param  Varien_Object $response
     * @return none
     */
    public function saveRefundTransInfo($payment, $amount, $request, $response)
    {
        $order = $payment->getOrder(); // Get order object
        $parentTxnId = $this->_helper->makeValidNumber($payment->getRefundTransactionId()); // Get refund transaction id
        $childTid = $response->hasTid() ? trim($response->getTid()) : ''; // Get child tid if exist
        if (!$childTid) { // Save transaction information for full refund
            $transactionStatusModel = $this->_helper->getModel('Mysql4_TransactionStatus');
            $transactionStatus = $transactionStatusModel->loadByAttribute('transaction_no', $parentTxnId);
            $transactionStatus->setTransactionStatus($response->getTidStatus())->save();
        }

        // Save payment refund transaction additional information
        $refundTid = !empty($childTid) ? $childTid : $payment->getLastTransId() . '-refund';
        $data = unserialize($payment->getAdditionalData());
        $data['fullRefund'] = (bool)((string) $order->getBaseGrandTotal() == (string) $amount);
        $data = $this->getRefundTidInfo($amount, $data, $refundTid, $parentTxnId);
        $payment->setTransactionId($refundTid)
            ->setLastTransId($refundTid)
            ->setAdditionalData(serialize($data));

        // Make capture transaction open for lower versions to make refund
        if (version_compare($this->_helper->getMagentoVersion(), '1.6', '<')) {
            $payment->setIsTransactionClosed(true) // refund initiated by merchant
                ->setShouldCloseParentTransaction(!$order->canCreditmemo());
        }

        $payment->save();

        // Save refund child transaction informations
        if ($childTid) {
            $this->saveChildRefundTransInfo($order, $request, $response);
        }
    }

    /**
     * Get refund transaction information
     *
     * @param  float $amount
     * @param  array $data
     * @param  int   $refundTid
     * @param  int   $parentTxnId
     * @return array
     */
    public function getRefundTidInfo($amount, $data, $refundTid, $parentTxnId)
    {
        $refundAmount = Mage::helper('core')->currency($amount, true, false);
        if (!isset($data['refunded_tid'])) {
            $refundedTid = array('refunded_tid' => array($refundTid => array(
                        'reftid' => $refundTid,
                        'refamount' => $refundAmount,
                        'reqtid' => $parentTxnId
            )));
            $data = array_merge($data, $refundedTid);
        } else {
            $data['refunded_tid'][$refundTid]['reftid'] = $refundTid;
            $data['refunded_tid'][$refundTid]['refamount'] = $refundAmount;
            $data['refunded_tid'][$refundTid]['reqtid'] = $parentTxnId;
        }

        return $data;
    }

    /**
     * Save refund child transaction information
     *
     * @param  Varien_Object $order
     * @param  Varien_Object $request
     * @param  Varien_Object $response
     * @return none
     */
    public function saveChildRefundTransInfo($order, $request, $response)
    {
        // Refund child transaction information log
        $transactionStatus = $this->_helper->getModel('Mysql4_TransactionStatus');
        $transactionStatus->setTransactionNo(trim($response->getTid()))
            ->setOrderId($order->getIncrementId())
            ->setTransactionStatus($response->getTidStatus())
            ->setCustomerId($order->getCustomerId())
            ->setRemoteIp($this->_helper->getRealIpAddr())
            ->setShopUrl($this->_helper->getBaseUrl())
            ->setPaymentName($order->getPayment()->getMethodInstance()->getCode())
            ->setAmount($request->getRefundParam())
            ->setStoreId($order->getStoreId())
            ->setReferenceTransaction(1)
            ->setCreatedDate($this->_helper->getCurrentDateTime())
            ->save();
    }

    /**
     * Send recurring process (cancel/re-active/suspend) request
     *
     * @param  Varien_Object $order
     * @param  string        $type
     * @param  Varien_Object $request
     * @return none
     */
    public function postRecurringApiRequest($order, $type, $request)
    {
        $payment = $order->getPayment(); // Get payment object
        $paymentObj = $payment->getMethodInstance(); // Get payment method instance
        $data = unserialize($payment->getAdditionalData());

        if ($type == 'canceled') { // For subscription cancel process
            $response = $paymentObj->postRequest($request);
            $data['subsCancelReason'] = $request->getCancelReason();
            $payment->setAdditionalData(serialize($data))->save();
        } elseif ($type == 'suspended' || $type == 'active') { // For subscription suspend/re-active process
            $gatewayModel = $this->_helper->getModel('Service_Api_Gateway'); // Get Novalnet gateway model
            $payportUrl = $this->_helper->getPayportUrl('infoport'); // Get Novalnet payport url
            $response = $gatewayModel->payportRequestCall($request, $payportUrl, 'XML');
            // Convert xml request to array for transaction traces log
            $xmlRequest = simplexml_load_string($request);
            $decodedRequest = json_decode(json_encode($xmlRequest), true);
            $request = new Varien_Object($decodedRequest['info_request']);
            if ($type == 'active') {
                $data['paidUntil'] = $response->getNextSubsCycle()
                    ? Mage::helper('core')->formatDate($response->getNextSubsCycle()) : '';
                $payment->setAdditionalData(serialize($data))->save();
            }
        }

        // Log Novalnet payment transaction traces informations
        $this->logTransactionTraces($request, $response, $order, $request->getTid());

        if ($response->getStatus() != Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
            $statusText = $this->getUnSuccessPaymentText($response); // Get payment transaction status message
            $this->_helper->showException($statusText, false);
        }
    }

}
