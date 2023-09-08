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
class Novalnet_Payment_Model_Service_Validate_PaymentCheck extends Novalnet_Payment_Model_Service_Abstract
{

    /**
     * Check payment visibility based on payment configuration
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function checkVisibility($quote = null)
    {
        if (!$this->validateBasicParams()
            || !$this->checkOrdersCount()
            || !$this->checkCustomerAccess()
            || (!empty($quote) && !$quote->hasNominalItems() && !$quote->getGrandTotal())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Validate Novalnet basic params
     *
     * @param  none
     * @return boolean
     */
    public function validateBasicParams()
    {
        return (bool)($this->getNovalnetConfig('public_key', true));
    }

    /**
     * Check orders count by customer id
     *
     * @param  none
     * @return boolean
     */
    public function checkOrdersCount()
    {
        // Load orders by customer id
        $customerId = $this->_helper->getCustomerId();
        $orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $customerId);
        $ordersCount = $orders->count();
        // Get orders count from payment configuration
        $minOrderCount = $this->getNovalnetConfig('orders_count');
        return (bool)($ordersCount >= trim($minOrderCount));
    }

    /**
     * Check whether current user have access to process the payment
     *
     * @param  none
     * @return boolean
     */
    public function checkCustomerAccess()
    {
        // Excluded customer groups from payment configuration
        $excludedGroups = $this->getNovalnetConfig('user_group_excluded');
        $excludedGroupsLength = strlen($excludedGroups);

        if (!$this->_helper->checkIsAdmin() && $excludedGroupsLength) {
            $excludedGroups = explode(',', $excludedGroups);
            $customerGroupId = $this->_helper->getCustomerSession()->getCustomerGroupId();
            return !in_array($customerGroupId, $excludedGroups);
        }

        return true;
    }

    /**
     * validate Novalnet params to proceed checkout
     *
     * @param  Varien_Object $info
     * @return boolean
     */
    public function validateNovalnetParams($info)
    {
        if (!$this->validateBasicParams()) {
            $this->_helper->showException('Basic parameter not valid.');
            return false;
        } elseif (!$this->validateBillingInfo($info)) {
            $this->_helper->showException('Customer name/email fields are not valid');
            return false;
        }

        return true;
    }

    /**
     * Validate billing information
     *
     * @param  Varien_Object $info
     * @return boolean
     */
    public function validateBillingInfo($info)
    {
        $request = new Varien_Object();
        $requestModel = $this->_helper->getModel('Service_Api_Request');
        $requestModel->getBillingInfo($request, $info);

        if (!$this->validateEmail($request->getEmail()) || !$request->getFirstName() || !$request->getLastName()
        ) {
            return false;
        }

        return true;
    }

    /**
     * validate Novalnet form data
     *
     * @param  none
     * @return throw Mage Exception|none
     */
    public function validateFormInfo()
    {
        $methodSession = $this->_helper->getMethodSession($this->code);

        if ($this->code == Novalnet_Payment_Model_Config::NN_SEPA) {
            // Validate the Direct Debit SEPA form data
            if ($methodSession->getSepaNewForm()
                && (!$methodSession->getSepaHolder() || !$methodSession->getSepaIban())
            ) {
                $this->_helper->showException('Your account details are invalid');
            }
            // Verify the guarantee payment informations
            $this->_validateGuaranteeInfo($methodSession);
        } else if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE) {
            // Verify the guarantee payment informations
            $this->_validateGuaranteeInfo($methodSession);
        } else if ($this->code == Novalnet_Payment_Model_Config::NN_CC) {
            // Validate the Credit Card form data
            if (!$methodSession->getNnCcTid()
                && (!$methodSession->getNnCcHash() || !$methodSession->getNnCcUniqId())
            ) {
                $this->_helper->showException('Your credit card details are invalid');
            }
        } else if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT || $this->code == Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT) {
            // Verify the guarantee payment informations
            $this->_validateGuaranteeInfo($methodSession);
        }
    }

    /**
     * Verify the guarantee payment informations
     *
     * @param  Varien_Object $methodSession
     * @return none
     */
    private function _validateGuaranteeInfo($methodSession)
    {
        if ($methodSession->getGuaranteeErrorFlag()) {
            $this->_helper->showException(
                $methodSession->getGuaranteeErrorMessage()
            );
        }

        // Validate DOB
        if ($methodSession->getPaymentGuaranteeFlag()
        || (in_array($this->code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT)))) {
            if ($this->code == Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT) {
                $customerDob = (string) $methodSession->getSepaInstalmentCustomerDob();
            } elseif ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT) {
                $customerDob = (string) $methodSession->getInvoiceCustomerDob();
            } else {
                $customerDob = (string) $methodSession->getCustomerDob();
            }

            if ($customerDob && !$this->validateBirthDate($customerDob)) {
                $methodSession->unsPaymentGuaranteeFlag();
                if (!$this->getNovalnetConfig('payment_guarantee_force')) {
                    $this->_helper->showException('You need to be at least 18 years old');
                }
            }
        }
    }

    /**
     * Verify the payment method values
     *
     * @param  null
     * @return null
     */
    public function checkMethodSession()
    {
        if (!$this->_helper->checkIsAdmin()) {
            $checkoutSession = $this->_helper->getCheckoutSession();
            $customerSession = $this->_helper->getCustomerSession();
            $previousPaymentCode = $checkoutSession->getNnPaymentCode();

            // Unset payment method session (for pre-select Novalnet payment)
            $paymentCode = $checkoutSession->getQuote()->getPayment()->getMethod();
            if ($paymentCode && $previousPaymentCode && $paymentCode != $previousPaymentCode
            ) {
                $this->_helper->unsetMethodSession($previousPaymentCode);
            }

            $paymentSucess = $this->getNovalnetConfig('payment_last_success', true);
            if (empty($paymentCode) && $customerSession->isLoggedIn() && $paymentSucess
            ) {
                $this->getLastSuccessOrderMethod($customerSession->getId(), $checkoutSession);
            }
        }
    }

    /**
     * Get last successful payment method
     *
     * @param int                         $customerId
     * @param Mage_Checkout_Model_Session $checkoutSession
     */
    public function getLastSuccessOrderMethod($customerId, $checkoutSession)
    {
        $tablePrefix = Mage::getConfig()->getTablePrefix();
        $orderTable = $tablePrefix . 'sales_flat_order';
        $onCondition = "main_table.parent_id = $orderTable.entity_id";
        $orderCollection = Mage::getModel('sales/order_payment')->getCollection()
            ->addAttributeToSort('created_at', 'DESC')
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('method', array('like' => '%novalnet%'))
            ->addFieldToSelect('method')
            ->setPageSize(1);
        $orderCollection->getSelect()->join($orderTable, $onCondition);
        $getSize = $orderCollection->getSize();
        if ($getSize > 0) {
            foreach ($orderCollection as $order):
                $paymentMethod = $order->getMethod();
            endforeach;
            $checkoutSession->getQuote()->getPayment()->setMethod($paymentMethod);
        }
    }

    /**
     * Check customer DOB is valid
     *
     * @param  string $birthDate
     * @return boolean
     */
    public function validateBirthDate($birthDate)
    {
        $age = strtotime('+18 years', strtotime($birthDate));
        return (Mage::getSingleton('core/date')->gmtTimestamp() < $age) ? false : true;
    }

    /**
     * Check Novalnet invoice payment guarantee availability
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @param  string                      $code
     * @return boolean
     */
    public function getPaymentGuaranteeStatus($quote, $code)
    {
        $this->code = $code; // Payment method code
        $methodSession = $this->_helper->getMethodSession($code); // Get payment method session
        $methodSession->setPaymentGuaranteeFlag(0)
            ->setGuaranteeErrorFlag(0);

        if ($this->getNovalnetConfig('enable_guarantee') || (in_array($code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT)))) {
            $allowedCountryCode = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('allowedCountry');
            $countryCode = strtoupper($quote->getBillingAddress()->getCountryId()); // Get country code
            $orderAmount = $this->_helper->getFormatedAmount($quote->getBaseGrandTotal()); // Get order amount
            $guaranteeMinAmount = ($this->getNovalnetConfig('enable_guarantee') && $this->getNovalnetConfig('guarantee_min_order_total'))
                ? $this->getNovalnetConfig('guarantee_min_order_total') : 999;
            $validateAddr = $this->addressValidation($quote); // Validate end-customer address (billing & shipping)
            if ($quote->hasNominalItems()) {
                $checkoutSession = $this->_helper->getCheckoutSession(); // Get checkout session
                // Get order amount (Add initial fees if exist)
                $orderAmount = $this->_helper->getFormatedAmount($checkoutSession->getNnRowAmount());
            }

            $displayMinAmount = Mage::helper('core')->currency(
                $this->_helper->getFormatedAmount($guaranteeMinAmount, 'RAW'), true, false
            );

            $errorText = '';
            $addressError = false;
            $errorPaymentName = in_array($code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))
                ? __('instalment payment') : __('payment guarantee');
            if (!in_array($countryCode, $allowedCountryCode)) {
                $errorText = __('Only DE, CH countries allowed %s', $errorPaymentName);
            } elseif ($orderAmount < $guaranteeMinAmount) {
                $errorText = __('Minimum order amount should be %s %s', $errorPaymentName, $displayMinAmount);
            } elseif ($quote->getBaseCurrencyCode() != 'EUR') {
                $errorText = __('Only EUR currency allowed %s', $errorPaymentName);
            } elseif ($validateAddr !== true) {
                $errorText = __('The shipping address should be the same as the billing address %s', $errorPaymentName);
                $addressError = true;
            }

            if (in_array($code, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))) {
                $option = false;
                $allcycles = explode(',', $this->getNovalnetConfig('instalment_total_period'));
                foreach ($allcycles as $allcycle) {
                    if ($allcycle != 0 && ($orderAmount / $allcycle) >= 999) {
                        $option = true;
                    }
                }

                if (!$option) {
                    return false;
                }
            }

            if (empty($errorText)) {
                $methodSession->setPaymentGuaranteeFlag(1)
                    ->setGuaranteeErrorFlag(0);
                return true;
            } elseif ($this->getNovalnetConfig('payment_guarantee_force')) {
                $methodSession->setGuaranteeErrorFlag(0);
                return true;
            }

            $methodSession->setGuaranteeErrorFlag(1);
            if ($errorText) {
                $methodSession->setGuaranteeErrorMessage($errorText);
                return (!$addressError) ? false : true;
            }

            return false;
        }

        return true;
    }

    /**
     * Address validation for guarantee payments
     *
     * @param  Varien_Object $quote
     * @return boolean
     */
    public function addressValidation($quote)
    {
        $billing = $quote->getBillingAddress(); // Get end-customer billing address
        $shipping = !$quote->getIsVirtual() ? $quote->getShippingAddress() : ''; // Get end-customer shipping address

        if ($shipping == '') {
            return true;
        } elseif (($billing->getCity() != $shipping->getCity())
            || (implode(',', $billing->getStreet()) != implode(',', $shipping->getStreet()))
            || ($billing->getPostcode() != $shipping->getPostcode())
            || ($billing->getCountry() != $shipping->getCountry())
        ) {
            return false;
        }

        return true;
    }

}
