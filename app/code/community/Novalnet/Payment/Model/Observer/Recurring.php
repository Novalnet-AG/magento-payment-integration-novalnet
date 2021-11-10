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
class Novalnet_Payment_Model_Observer_Recurring
{

    /**
     * Get recurring product custom option values
     *
     * @param  varien_object $observer
     * @return Novalnet_Payment_Model_Observer_Recurring
     */
    public function getProfilePeriodValues(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getCart()->getQuote(); // Get quote object

        foreach ($quote->getAllItems() as $items) {
            if ($items->getProduct()->isRecurring()) {
                $recurringProfile = $items->getProduct()->getRecurringProfile(); // Get profile object
                // Get recurring profile period values
                Mage::getSingleton('checkout/session')->setNnPeriodUnit($recurringProfile['period_unit'])
                    ->setNnPeriodFrequency($recurringProfile['period_frequency']);
            }
        }
    }

    /**
     * Set redirect url for recurring payment
     *
     * @param  varien_object $observer
     * @return Novalnet_Payment_Model_Observer_Recurring
     */
    public function setPaymentRedirectUrl(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote(); // Get quote object
        $paymentCode = $quote->getPayment()->getMethodInstance()->getCode(); // Get payment method code
        if (preg_match("/novalnet/i", $paymentCode)) {
            $helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
            $checkoutSession = $helper->getCheckoutSession(); // Get checkout session
            // Get recurring profile id
            $profileIds = $checkoutSession->getLastRecurringProfileIds();
            $recurringProfileIds = !empty($profileIds) ? array_filter($profileIds) : '';
            $redirectPayments = array(Novalnet_Payment_Model_Config::NN_CC, Novalnet_Payment_Model_Config::NN_PAYPAL);

            if (!empty($recurringProfileIds) && in_array($paymentCode, $redirectPayments)) {
                $coreSession = $helper->getCoreSession();
                if (in_array($paymentCode, array(Novalnet_Payment_Model_Config::NN_CC, Novalnet_Payment_Model_Config::NN_PAYPAL)) && !$coreSession->hasDirectRp()
                ) {
                    $redirectUrl = $helper->getUrl(Novalnet_Payment_Model_Config::GATEWAY_REDIRECT_URL);
                    $checkoutSession->setLastOrderId($coreSession->getOrderId())
                        ->setLastRealOrderId($coreSession->getIncrementId())
                        ->setRedirectUrl($redirectUrl);
                }

                $coreSession->unsOrderId()
                    ->unsIncrementId()
                    ->unsDirectRp();
            }

            $order = $observer->getEvent()->getOrder();
            if (is_object($order)) {
                $setAfterStatus = false;
                if ($paymentCode == Novalnet_Payment_Model_Config::NN_INVOICE || Novalnet_Payment_Model_Config::NN_SEPA) {
                    $additionalData = $order->getPayment()->getAdditionalData();
                    $additionalData = unserialize($additionalData);
                    $transactionId = !empty($additionalData['NnTid']) ? $helper->makeValidNumber($additionalData['NnTid']) : '';
                    $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
                                ->loadByAttribute('transaction_no', $transactionId);
                    $paymentStatus = $transactionStatus->getTransactionStatus(); // Get payment original transaction status
                    $paymentObj = $order->getPayment()->getMethodInstance();

                    if ($paymentStatus == 75) {
                        $setAfterStatus = true;
                        $guaranteePending = $paymentObj->getConfigData('guarantee_pending_status')
                            ? $paymentObj->getConfigData('guarantee_pending_status')
                            : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                    }
                }

                if ($paymentCode == Novalnet_Payment_Model_Config::NN_PAYPAL
                    && $order->getPayment()->getLastTransId()
                    && $order->getPayment()->getAdditionalInformation('novalnetPaypal_redirectAction') != 1) {
                    $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
                        ->loadByAttribute('transaction_no', $order->getPayment()->getLastTransId());
                    $setAfterStatus = ($transactionStatus->getTransactionStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED)
                        ? true : false;
                }

                if ($setAfterStatus) {
                    $status = $guaranteePending ? $guaranteePending : ($paymentObj->getConfigData('order_status_after_payment')
                        ? $paymentObj->getConfigData('order_status_after_payment')
                        : Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $status, '', true);
                    $order->save();
                }
            }
        }
    }

    /**
     * Set affiliate id if exist
     *
     * @param  varien_object $observer
     * @return Novalnet_Payment_Model_Observer_Recurring
     */
    public function setAffiliateProcess(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            // Novalnet payment helper
            $helper = Mage::helper('novalnet_payment');

            $affiliateId = Mage::app()->getRequest()->getParam('nn_aff_id');
            if ($affiliateId) {
                $helper->getCoreSession()->setAffiliateId(trim($affiliateId));
            }
        }

        return $this;
    }

}
