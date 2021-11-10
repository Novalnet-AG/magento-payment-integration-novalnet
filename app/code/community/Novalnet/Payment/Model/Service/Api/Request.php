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
class Novalnet_Payment_Model_Service_Api_Request extends Novalnet_Payment_Model_Service_Abstract
{

    /**
     * Prepare payment request params
     *
     * @param  Varien_Object $infoObject
     * @param  string|null   $code
     * @param  int|null      $amount
     * @return Varien_Object $request
     */
    public function getPayportParams($infoObject, $code = null, $amount = null)
    {
        $this->code = ($code !== null) ? $code : $this->code;
        $request = new Varien_Object();
        $this->getVendorInfo($request); // Get Novalnet merchant authentication informations
        $this->getBillingInfo($request, $infoObject); // Get customer billing infromations
        $this->getCommonInfo($request, $infoObject, $amount); // Get common params for Novalnet payment api request
        $this->getPaymentInfo($request); // Get needed Novalnet payment params
        return $request;
    }

    /**
     * Assign Novalnet authentication Data
     *
     * @param  Varien_Object $request
     * @return Varien_Object $request
     */
    public function getVendorInfo(Varien_Object $request)
    {
        $testMode = $this->getNovalnetConfig('live_mode', true); // Novalnet payment mode configuration
        $request->setVendor($this->vendorId)
            ->setAuthCode($this->authcode)
            ->setProduct($this->productId)
            ->setTariff($this->tariffId)
            ->setKey($this->_helper->getPaymentId($this->code))
            ->setTestMode((int)!$testMode);

        $checkoutSession = $this->_helper->getCheckoutSession(); // Get checkout session
        if ($checkoutSession->getQuote()->hasNominalItems()) {
            $request->setTariff($this->recurringTariffId);
        }

        return $request;
    }

    /**
     * Get end-customer billing informations
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $info
     * @return mixed
     */
    public function getBillingInfo(Varien_Object $request, $info)
    {
        $infoObject = $this->_getInfoObject($info); // Get current payment object informations
        $billing = $infoObject->getBillingAddress(); // Get end-customer billing address
        $shipping = !$infoObject->getIsVirtual() ? $infoObject->getShippingAddress() : '';
        // Get company param if exist either billing/shipping address
        $company = $billing->getCompany()
            ? $billing->getCompany()
            : ($shipping && $shipping->getCompany() ? $shipping->getCompany() : '');
        $request->setFirstName($billing->getFirstname())
            ->setLastName($billing->getLastname())
            ->setEmail($billing->getEmail() ? $billing->getEmail() : $infoObject->getCustomerEmail())
            ->setCity($billing->getCity())
            ->setZip($billing->getPostcode())
            ->setTel($billing->getTelephone())
            ->setSearchInStreet(1)
            ->setStreet(implode(',', $billing->getStreet()))
            ->setCountryCode($billing->getCountry());

        $request = $company ? $request->setCompany($company) : $request; // Set company param if exist
        if($billing->getFax()){
            $request->setFax($billing->getFax());
        }
        
        // Check and assigns shipping details
        $this->setShippingDetails($request, $billing, $shipping);
        
    }
    
    /**
     * Get end-customer shipping informations
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $info
     * @return mixed
     */
    public function setShippingDetails(Varien_Object $request, $billing, $shipping)
    {
        if (!empty($shipping)) {
            if (($billing->getFirstname() != $shipping->getFirstname()) || ($billing->getLastname() != $shipping->getLastname())
                || ($billing->getCity() != $shipping->getCity()) || ($billing->getStreet() !== $shipping->getStreet())
                || ($billing->getPostcode() != $shipping->getPostcode()) || ($billing->getCountry() != $shipping->getCountry())
                || ($billing->getTelephone() != $shipping->getTelephone())
            ) {
                $request->setSFirstName($shipping->getFirstname())
                               ->setSLastName($shipping->getLastname())
                               ->setSEmail($request->getEmail())
                               ->setSStreet(implode(',', $shipping->getStreet()))
                               ->setSCity($shipping->getCity())
                               ->setSZip($shipping->getPostcode())
                               ->setSCountryCode($shipping->getCountryId())
                               ->setSTel($shipping->getTelephone());
                // Checks and assigns company parameter
                if ($shipping->getCompany()) {
                    $request->setSCompany($shipping->getCompany());
                }
            } else {
                $request->setShipAddSab(1);
            }
        }
    }

    /**
     * Get common params for Novalnet payment API request
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $info
     * @param  int|null      $amount
     * @return mixed
     */
    public function getCommonInfo(Varien_Object $request, $info, $amount = null)
    {
        $this->getReferenceParams($request);  // Get payment reference params like referer id/reference value
        $infoObject = $this->_getInfoObject($info);  // Get current payment object informations
        $amount = $amount ? $amount : $this->_helper->getFormatedAmount($this->_getAmount($info));
        $vendorScriptUrl = Mage::getStoreConfig('novalnet_global/merchant_script/vendor_script_url');
        $request->setAmount($amount)
            ->setCurrency($infoObject->getBaseCurrencyCode())
            ->setCustomerNo($this->_helper->getCustomerId())
            ->setLang(strtoupper($this->_helper->getDefaultLanguage()))
            ->setRemoteIp($this->_helper->getRealIpAddr())
            ->setSystemIp($this->_helper->getServerAddr())
            ->setSystemUrl($this->_helper->getBaseUrl())
            ->setSystemName('Magento')
            ->setSystemVersion($this->_helper->getMagentoVersion() . '-' . $this->_helper->getNovalnetVersion())
            ->setOrderNo($this->getOrderId($info));

        if ($vendorScriptUrl) {
            $request->setNotifyUrl($vendorScriptUrl); // Get vendor script url
        }

        if ($this->getNovalnetConfig('paymentaction')) {
            $setOnholdPayments = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('onHoldPayments');

            if (in_array($this->code, $setOnholdPayments) &&
                (string) $request->getAmount() >= (string) $this->getNovalnetConfig('manual_checking_amount')
            ) {
                $request->setOnHold(1); // Set payment status as onHold
            }
        }
    }

    /**
     * Set additional payment reference params
     *
     * @param  Varien_Object $request
     * @return mixed
     */
    public function getReferenceParams(Varien_Object $request)
    {
        $referenceOne = trim(strip_tags($this->getNovalnetConfig('reference_one')));
        // Assign reference value if exist
        ($referenceOne) ? $request->setInput1('reference1')->setInputval1($referenceOne) : '';
        $referenceTwo = trim(strip_tags($this->getNovalnetConfig('reference_two')));
        // Assign reference value if exist
        ($referenceTwo) ? $request->setInput2('reference2')->setInputval2($referenceTwo) : '';
        $adminUserId = ($this->_helper->checkIsAdmin())
            ? Mage::getSingleton('admin/session')->getUser()->getUserId() : '';
        // Assign admin order reference value if exist
        ($adminUserId) ? $request->setInput3('admin_user')->setInputval3($adminUserId) : '';
    }

    /**
     * Get order increment id
     *
     * @param  Varien_Object $info
     * @return int
     */
    public function getOrderId($info)
    {
        return ($this->_isPlaceOrder($info)) ? $info->getOrder()->getIncrementId() : $this->_getIncrementId();
    }

    /**
     * Assign Novalnet payment data
     *
     * @param  Varien_Object $request
     * @return none
     */
    public function getPaymentInfo(Varien_Object $request)
    {
        $methodSession = $this->_helper->getMethodSession($this->code); // Get current payment method session
        $request->setPaymentType($this->getPaymentType($this->code)); // Set payment type param in payment request

        switch ($this->code) {
            case Novalnet_Payment_Model_Config::NN_CC:
                $this->getCcFormParams($request, $methodSession);
                // Add Credit Card 3D Secure payment process params
                if ($methodSession->getNnCcDoRedirect() == 1) {
                    $this->getCcSecureParams($request);
                    $request->unsUserVariable_0();
                }
                break;
            case Novalnet_Payment_Model_Config::NN_SEPA:
                $this->getSepaFormParams($request, $methodSession);
                // Assign params for SEPA payment guarantee
                $helper = Mage::helper('novalnet_payment');
                $company = $helper->getEndUserCompany();
                if ($methodSession->getPaymentGuaranteeFlag() && ($methodSession->getCustomerDob() || $company)) {
                    $request->setPaymentType(Novalnet_Payment_Model_Config::SEPA_PAYMENT_GUARANTEE_TYPE)
                        ->setKey(Novalnet_Payment_Model_Config::SEPA_PAYMENT_GUARANTEE_KEY);
                    if ($methodSession->getCustomerDob()) {
                        $request->setBirthDate($methodSession->getCustomerDob());
                    }
                }

                if ($paymentDuration = $this->getNovalnetConfig('sepa_duedate')) { // Retrieves SEPA payment due date
                    $sepaDueDate = date('Y-m-d', strtotime('+' . $paymentDuration . ' days'));
                    $request->setSepaDueDate($sepaDueDate);
                }
                break;
            case Novalnet_Payment_Model_Config::NN_INVOICE:
                $request->setInvoiceType(Novalnet_Payment_Model_Config::INVOICE_PAYMENT_TYPE)
                    ->setInvoiceRef('BNR-' . $request->getProduct() . '-' . $request->getOrderNo());
                // Assign invoice payment due date
                if ($dueDate = $this->getPaymentDueDate()) {
                    $request->setDueDate($dueDate);
                }

                // Assign params for invoice payment guarantee
                $helper = Mage::helper('novalnet_payment');
                $company = $helper->getEndUserCompany();
                if ($methodSession->getPaymentGuaranteeFlag() && ($methodSession->getCustomerDob() || $company)) {
                    $request->setPaymentType(Novalnet_Payment_Model_Config::INVOICE_PAYMENT_GUARANTEE_TYPE)
                        ->setKey(Novalnet_Payment_Model_Config::INVOICE_PAYMENT_GUARANTEE_KEY);

                    if ($methodSession->getCustomerDob()) {
                        $request->setBirthDate($methodSession->getCustomerDob());
                    }
                }
                break;
            case Novalnet_Payment_Model_Config::NN_PREPAYMENT:
                $request->setInvoiceType(Novalnet_Payment_Model_Config::PREPAYMENT_PAYMENT_TYPE)
                    ->setInvoiceRef('BNR-' . $request->getProduct() . '-' . $request->getOrderNo());
                // Assign prepayment payment due date
                if ($dueDate = $this->getPaymentDueDate()) {
                    $request->setDueDate($dueDate);
                }
                break;
            case Novalnet_Payment_Model_Config::NN_CASHPAYMENT:
                // Assign cashpayment due date
                if ($dueDate = $this->getPaymentDueDate()) {
                    $request->setCpDueDate($dueDate);
                }
                break;
            case Novalnet_Payment_Model_Config::NN_PAYPAL:
                if ($methodSession->getNnRefTid()) {
                    $request->setPaymentRef($methodSession->getNnRefTid());
                } else {
                    if ($methodSession->getNnPaypalSaveAccount()) {
                        $request->setCreatePaymentRef(1);
                    }

                    $this->getRedirectPaymentParams($request);
                }
                break;
            case Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT:
                if ($methodSession->getInvoiceCustomerDob()) {
                    $request->setBirthDate($methodSession->getInvoiceCustomerDob());
                }

                $request->setInvoiceType(Novalnet_Payment_Model_Config::INVOICE_PAYMENT_TYPE)
                        ->setInvoiceRef('BNR-'.$request->getProduct().'-'.$request->getOrderNo())
                        ->setInstalmentPeriod($this->getNovalnetConfig('instalment_cycle_periods') . 'm')
                        ->setInstalmentCycles($methodSession->getInvoiceInstalment());
                break;
            case Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT:
                if ($methodSession->getSepaInstalmentCustomerDob()) {
                    $request->setBirthDate($methodSession->getSepaInstalmentCustomerDob());
                }

                if ($paymentDuration = $this->getNovalnetConfig('sepa_duedate')) { // Retrieves Instalment SEPA payment due date
                    $sepaDueDate = date('Y-m-d', strtotime('+' . $paymentDuration . ' days'));
                    $request->setSepaDueDate($sepaDueDate);
                }

                $request->setBankAccountHolder($methodSession->getSepaInstalmentHolder())
                        ->setIban($methodSession->getSepaInstalmentIban())
                        ->setInstalmentPeriod($this->getNovalnetConfig('instalment_cycle_periods') . 'm')
                        ->setInstalmentCycles($methodSession->getSepaInstalment());
                break;
            case Novalnet_Payment_Model_Config::NN_BANKTRANSFER:
            case Novalnet_Payment_Model_Config::NN_IDEAL:
            case Novalnet_Payment_Model_Config::NN_EPS:
            case Novalnet_Payment_Model_Config::NN_GIROPAY:
            case Novalnet_Payment_Model_Config::NN_PRZELEWY:
                $this->getRedirectPaymentParams($request);
                break;
        }
    }

    /**
     * Get payment form informations
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $methodSession
     * @return none
     */
    public function getCcFormParams($request, $methodSession)
    {
        if ($methodSession->getNnCcTid()) {
            $request->setPaymentRef($methodSession->getNnCcTid());
        } else {
            if ($this->getNovalnetConfig('cc_shop_type')
                && $methodSession->getNnCcSaveCard()
                && !$this->_helper->checkIsAdmin()
            ) {
                $request->setCreatePaymentRef(1);
            }
            
            $request->setPanHash($methodSession->getNnCcHash())
                ->setUniqueId($methodSession->getNnCcUniqId())
                ->setNnIt('iframe');
        }
    }

    /**
     * Get SEPA form informations
     *
     * @param  Varien_Object $request
     * @param  Varien_Object $methodSession
     * @return none
     */
    public function getSepaFormParams($request, $methodSession)
    {
        if ($methodSession->getNnSepaTid()) {
            $request->setPaymentRef($methodSession->getNnSepaTid());
        } else {
            if ($methodSession->getSepaSaveAccount() && !$this->_helper->checkIsAdmin()
            ) {
                $request->setCreatePaymentRef(1);
            }

            $request->setBankAccountHolder($methodSession->getSepaHolder())
                ->setIban($methodSession->getSepaIban());
        }
    }

    /**
     * Get due date for invoice payment
     *
     * @param  none
     * @return int
     */
    public function getPaymentDueDate()
    {
        $dueDate = '';
        if ($paymentDuration = trim($this->getNovalnetConfig('payment_duration'))) {
            $dueDate = Mage::getSingleton('core/date')
                ->gmtDate('Y-m-d', '+' . (int) $paymentDuration . ' days');
        }

        return $dueDate;
    }

    /**
     * Get Credit Card 3D Secure payment params
     *
     * @param  Varien_Object $request
     * @return none
     */
    public function getCcSecureParams($request)
    {
        $request->setUniqid($this->getUniqid())
            ->setSession(session_id())
            ->setImplementation('ENC');

        if($this->getNovalnetConfig('enforce_3d')) {
            $request->setData('enforce_3d',1);
        }
        $this->getMethodAndUrlInfo($request);
        $this->getEncodedParams($request);
    }

    /**
     * Get redirect payment params
     *
     * @param  Varien_Object $request
     * @return none
     */
    public function getRedirectPaymentParams($request)
    {
        $request->setUniqid($this->getUniqid())
            ->setSession(session_id())
            ->setImplementation('ENC');
        $this->getMethodAndUrlInfo($request);
        $this->getEncodedParams($request);
    }

    /**
     * Gets the Unique Id
     *
     * @return string
     */
    public function getUniqid()
    {
        $randomArray = array('8','7','6','5','4','3','2','1','9','0','9','7','6','1','2','3','4','5','6','7','8','9','0');
        shuffle($randomArray);
        return substr(implode($randomArray, ''), 0, 16);
    }

    /**
     * Retrieve Novalnet payment type
     *
     * @param  string $code
     * @return string
     */
    public function getPaymentType($code)
    {
        $arrPaymentType = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('paymentTypes');
        return $arrPaymentType[$code];
    }

    /**
     * Get method and url infromations for redirect payments
     *
     * @param  Varien_Object $request
     * @return mixed
     */
    public function getMethodAndUrlInfo($request)
    {
        $request->setUserVariable_0(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB))
            ->setReturnMethod(Novalnet_Payment_Model_Config::NOVALNET_RETURN_METHOD)
            ->setErrorReturnMethod(Novalnet_Payment_Model_Config::NOVALNET_RETURN_METHOD)
            ->setReturnUrl($this->_helper->getUrl(Novalnet_Payment_Model_Config::GATEWAY_RETURN_URL))
            ->setErrorReturnUrl($this->_helper->getUrl(Novalnet_Payment_Model_Config::GATEWAY_ERROR_RETURN_URL));
    }

    /**
     * build process (capture/void/refund) request
     *
     * @param  Varien_Object $payment
     * @param  string        $type
     * @param  float|NULL    $amount
     * @return Varien_Object $request
     */
    public function buildProcessRequest(Varien_Object $payment, $type, $amount = null)
    {
        $request = $this->getprocessVendorInfo($payment);  // Get Novalnet merchant authentication informations
        $request->setRemoteIp($this->_helper->getRealIpAddr());

        if (in_array($type, array('void', 'capture'))) { // Assign needed capture/void process request params
            $getTid = $this->_helper->makeValidNumber($payment->getLastTransId());
            $status = ($type == 'capture')
                ? Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED
                : Novalnet_Payment_Model_Config::PAYMENT_VOID_STATUS;
            $request->setTid($getTid)
                ->setStatus($status)
                ->setEditStatus(true);
        } else {  // Assign needed refund process request params
            // Refund validation for Invoice payment method
            $refundTid = $this->_helper->makeValidNumber($payment->getRefundTransactionId());
            $paymentCode = $payment->getMethodInstance()->getCode(); // Get payment method code

            $refundAmount = $this->_helper->getFormatedAmount($amount); // Refund amount in cents
            $request->setTid($refundTid)
                ->setRefundRequest(true)
                ->setRefundParam($refundAmount);
            if (in_array($payment->getMethodInstance()->getCode(), array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))
              && $this->_helper->getFormatedAmount($payment->getOrder()->getBaseGrandTotal()) == $this->_helper->getFormatedAmount($amount)) {
                $request->setRefundParam('DEACTIVATE');
            }

            // Get reference value for refund process
            $refundRef = Mage::app()->getRequest()->getParam('nn_refund_ref');
            if ($refundRef && !preg_match('/[#%\^<>@$=*!]/', $refundRef)) {
                $request->setRefundRef($refundRef); // Add reference param
            }
        }

        if (in_array($payment->getMethodInstance()->getCode(), array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))) {
            $request->setTid($this->_helper->makeValidNumber($payment->getLastTransId()));
        }

        return $request;
    }

    /**
     * Assign Novalnet authentication Data
     *
     * @param  Varien_Object $payment
     * @return Varien_Object $request
     */
    public function getprocessVendorInfo($payment)
    {
        $data = unserialize($payment->getAdditionalData()); // Get payment additional data
        $paymentCode = $payment->getMethodInstance()->getCode(); // Get payment method code
        $paymentId = $this->_helper->getPaymentId($paymentCode);

        $request = new Varien_Object();
        $request->setVendor(!empty($data['vendor']) ? trim($data['vendor']) : $this->vendorId)
            ->setAuthCode(!empty($data['auth_code']) ? trim($data['auth_code']) : $this->authcode)
            ->setProduct(!empty($data['product']) ? trim($data['product']) : $this->productId)
            ->setTariff(!empty($data['tariff']) ? trim($data['tariff']) : $this->tariffId)
            ->setKey(!empty($data['payment_id']) ? trim($data['payment_id']) : $paymentId);
        return $request;
    }

    /**
     * build recurring API process (cancel/suspend/active) request
     *
     * @param  Varien_Object                        $order
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @return Varien_Object $request
     */
    public function buildRecurringApiRequest(Varien_Object $order, $profile)
    {
        $request = new Varien_Object();
        // Get Novalnet merchant authentication informations
        $request = $this->getprocessVendorInfo($order->getPayment());

        if ($profile->getNewState() == 'canceled') {
            $getRequest = Mage::app()->getRequest()->getQuery();
            $request->setNnLang(strtoupper($this->_helper->getDefaultLanguage()))
                ->setCancelSub(1)
                ->setCancelReason($getRequest['reason'])
                ->setTid($profile->getReferenceId())
                ->setRemoteIp($this->_helper->getRealIpAddr());
        } elseif (in_array($profile->getNewState(), array('suspended', 'active'))) {
            $request = $this->recurringActiveSuspendRequest($order->getPayment(), $profile, $request);
        }

        return $request;
    }

    /**
     * build recurring API process (suspend/active) request
     *
     * @param  Varien_Object                        $payment
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @param  Varien_Object $request
     * @return Varien_Object $params
     */
    public function recurringActiveSuspendRequest(Varien_Object $payment, $profile, $request)
    {
        $type = $profile->getNewState(); // Get recurring profile state
        $periodInfo = $this->getPeriodValues($profile); // Get subscription period frequency and unit
        $subsIdRequest = $payment->getAdditionalInformation('subs_id'); // Get subsId
        $suspend = (int)($type == 'suspended');
        $pausePeriod = '';

        if ($suspend != 1) {
            $data = unserialize($payment->getAdditionalData());
            $nextSubsCycle = $preNextSubsCycle = Mage::getSingleton('core/date')->gmtDate('Y-m-d', $data['paidUntil']);
            $currentDate = Mage::getSingleton('core/date')->gmtDate('Y-m-d');

            if (strtotime($currentDate) > strtotime($nextSubsCycle)) {
                for ($i = 0; $i >= 0; $i++) {
                    if ($periodInfo['periodUnit'] == 'm') {
                        $nextSubsCycle = Mage::getSingleton('core/date')->gmtDate(
                            'Y-m-d', $nextSubsCycle . '+' . $periodInfo['periodFrequency'] . ' months'
                        );
                    } elseif ($periodInfo['periodUnit'] == 'd') {
                        $nextSubsCycle = Mage::getSingleton('core/date')->gmtDate(
                            'Y-m-d', $nextSubsCycle . '+' . $periodInfo['periodFrequency'] . ' days'
                        );
                    } elseif ($periodInfo['periodUnit'] == 'y') {
                        $nextSubsCycle = Mage::getSingleton('core/date')->gmtDate(
                            'Y-m-d', $nextSubsCycle . '+' . $periodInfo['periodFrequency'] . ' years'
                        );
                    }

                    if (strtotime($nextSubsCycle) > strtotime($currentDate)) {
                        break;
                    }
                }

                $subsCycleOne = new DateTime($preNextSubsCycle);
                $subsCycleTwo = new DateTime($nextSubsCycle);
                $difference = $subsCycleOne->diff($subsCycleTwo);

                $pausePeriod = $difference->days;
            }
        }

        $params = '<?xml version="1.0" encoding="UTF-8"?>';
        $params .= '<nnxml><info_request>';
        $params .= '<vendor_id>' . $request->getVendor() . '</vendor_id>';
        $params .= '<vendor_authcode>' . $request->getAuthCode() . '</vendor_authcode>';
        $params .= '<request_type>' . Novalnet_Payment_Model_Config::SUBS_PAUSE . '</request_type>';
        $params .= '<product_id>' . $request->getProduct() . '</product_id>';
        $params .= '<tid>' . $profile->getReferenceId() . '</tid>';
        $params .= '<subs_id>' . $subsIdRequest . '</subs_id>';
        if (!empty($pausePeriod)) {
            $params .= '<pause_period>' . $pausePeriod . '</pause_period>';
            $params .= '<pause_time_unit>d</pause_time_unit>';
        }

        $params .= '<suspend>' . $suspend . '</suspend>';
        $params .= '<remote_ip>' . $this->_helper->getRealIpAddr() . '</remote_ip>';
        $params .= '</info_request></nnxml>';

        return $params;
    }

    /**
     * Get subscription period frequency and unit
     *
     * @param  Mage_Payment_Model_Recurring_Profile $profile
     * @return string
     */
    public function getPeriodValues($profile)
    {
        $periodFrequency = $profile->getperiodFrequency();
        $periodUnit = $this->_helper->__(ucfirst($profile->getperiodUnit()));
        $periodUnitFormat = array(
            $this->_helper->__('Day') => 'd',
            $this->_helper->__('Month') => 'm',
            $this->_helper->__('Year') => 'y'
        );

        if ($periodUnit == 'Semi_month') {
            $tariffPeriod = array('periodFrequency' => '14', 'periodUnit' => 'd');
        } elseif ($periodUnit == 'Week') {
            $tariffPeriod = array('periodFrequency' => ($periodFrequency * 7), 'periodUnit' => 'd');
        } else {
            $tariffPeriod = array(
                'periodFrequency' => $periodFrequency,
                'periodUnit' => $periodUnitFormat[$periodUnit]
            );
        }

        return $tariffPeriod;
    }
}
