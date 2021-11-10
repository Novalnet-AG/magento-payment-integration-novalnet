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
class Novalnet_Payment_Model_Observer_SalesEmails
{

    /**
     * Send order mail
     *
     * @param  Varien_Object $observer
     * @return Novalnet_Payment_Model_Observer_SalesEmails
     */
    public function sendOrderEmail($observer)
    {
        /* $order Magento_Sales_Model_Order */
        $order = $observer->getEvent()->getOrder();
        if (!$order->getEmailSent() && $order->getId()) {
            try {
                $order->sendNewOrderEmail()
                    ->setEmailSent(true)
                    ->save();
            } catch (Mage_Core_Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Send order invoice mail
     *
     * @param  Varien_Object $observer
     * @return Novalnet_Payment_Model_Observer_SalesEmails
     */
    public function sendInvoiceEmail($observer)
    {
        try {
            /* $order Magento_Sales_Model_Order_Invoice */
            $invoice = $observer->getEvent()->getInvoice();
            // Get payment method code
            $paymentCode = $invoice->getOrder()->getPayment()->getMethodInstance()->getCode();
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $coreSession = Mage::helper('novalnet_payment')->getCoreSession();
            // Set capture status for Novalnet payments
            if (Mage::app()->getStore()->isAdmin() && !preg_match("/sales_order_create/i", $currentUrl)
            ) {
                $this->setCaptureOrderStatus($invoice->getOrder(), $paymentCode);
            }

            // Set order invoice status as pending for Novalnet invoice payment
            if ($paymentCode == Novalnet_Payment_Model_Config::NN_INVOICE
                && !Mage::app()->getRequest()->getParam('tid_payment')
            ) {
                $additionalData = $invoice->getOrder()->getPayment()->getAdditionalData();
                $additionalData = unserialize($additionalData);
                if (!isset($additionalData['NnGuarantee'])) {
                    $invoice->setState(1);
                }
            } elseif ($paymentCode == Novalnet_Payment_Model_Config::NN_PAYPAL && $coreSession->hasPaypalOnholdFlag()) {
                $invoice->setState(1);
                $coreSession->unsPaypalOnholdFlag();
            }

            $invoice->save();
            $invoice->sendEmail($invoice);
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
        }

        return $this;
    }

    /**
     * Send order creditmemo mail
     *
     * @param  Varien_Object $observer
     * @return Novalnet_Payment_Model_Observer_SalesEmails
     */
    public function sendCreditmemoEmail($observer)
    {
        try {
            /* $order Magento_Sales_Model_Order_Creditmemo */
            $refund = $observer->getEvent()->getCreditmemo();
            $refund->save();
            $refund->sendEmail($refund);
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e->getMessage());
        }

        return $this;
    }

    /**
     * Set canceled/VOID status for Novalnet payments
     *
     * @param Varien_Object $observer
     * @param none
     */
    public function setVoidOrderStatus($observer)
    {
        /* $order Magento_Sales_Model_Order */
        $payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder(); // Get order object

        if (preg_match("/novalnet/i", $payment->getMethodInstance()->getCode())) {
            $voidOrderStatus = Mage::getStoreConfig(
                'novalnet_global/order_status_mapping/void_status', $order->getStoreId()
            ) ? Mage::getStoreConfig(
                'novalnet_global/order_status_mapping/void_status', $order->getStoreId()
            ) : Mage_Sales_Model_Order::STATE_CANCELED;
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                $voidOrderStatus,
                Mage::helper('novalnet_payment')->__('Order was canceled'), true
            )->save();
        }
    }

    /**
     * Set capture status for Novalnet payments
     *
     * @param Varien_Object $order
     * @param string $paymentCode
     * @param none
     */
    protected function setCaptureOrderStatus($order, $paymentCode)
    {
        if (preg_match("/novalnet/i", $paymentCode)) {
            $paymentObj = $order->getPayment()->getMethodInstance();
            $additionalData = unserialize($order->getPayment()->getAdditionalData());

            if (($paymentCode == Novalnet_Payment_Model_Config::NN_INVOICE && !empty($additionalData['NnGuarantee'])) || $paymentCode == Novalnet_Payment_Model_Config::NN_PAYPAL) {
                $captureOrderStatus = $paymentObj->getConfigData('order_status_after_payment');
            } else {
                $captureOrderStatus = $paymentObj->getConfigData('order_status');
            }

            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                $captureOrderStatus,
                Mage::helper('novalnet_payment')->__('The transaction has been confirmed'), true
            )->save();
        }
    }

}
