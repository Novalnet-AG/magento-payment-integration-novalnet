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
class Novalnet_Payment_Model_Observer_UpdateOrderStatus
{
    /**
     * Set On Hold status to Orders
     *
     * @param  varien_object $observer
     * @return Novalnet_Payment_Model_Observer_UpdateOrderStatus
     */
    public function setOnHoldOrderStatus(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order && preg_match('/novalnet/', $order->getPayment()->getMethod())) {
            $paymentMethod = $order->getPayment()->getMethod();
            $paymentObj = $order->getPayment()->getMethodInstance();
            $redirectPayments = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('redirectPayments');
            $additionalData = unserialize($order->getPayment()->getAdditionalData());
            $transactionId = !empty($additionalData['NnTid']) ? Mage::helper('novalnet_payment')->makeValidNumber($additionalData['NnTid']) : '';

            $transactionStatus = Mage::helper('novalnet_payment')->getModel('Mysql4_TransactionStatus')
                    ->loadByAttribute('transaction_no', $transactionId);
            $paymentStatus = $transactionStatus->getTransactionStatus();

            if (!in_array($paymentMethod, $redirectPayments)
            || ($paymentMethod == 'novalnetCc') && ($paymentObj->getConfigData('enable_cc3d') || $paymentObj->getConfigData('cc3d_validation'))) {
                $setOrderStatus = Mage_Sales_Model_Order::STATE_PROCESSING;
                // Assigns order status based on payment configured
                if (in_array(
                    $paymentMethod,
                    array('novalnetCc', 'novalnetInvoice', 'novalnetSepa', 'novalnetInvoiceInstalment', 'novalnetSepaInstalment')
                )) {
                    // Guaranteed pending payment status
                    if ($paymentStatus == 75) {
                        $setOrderStatus = $paymentObj->getConfigData('guarantee_pending_status');
                    } elseif (in_array($paymentStatus, array(91, 99, 98))) {
                        $setOrderStatus = Mage::getStoreConfig(
                            'novalnet_global/order_status_mapping/order_status',
                            $order->getStoreId()
                        );
                    } elseif ($paymentStatus == 100 && $paymentMethod == Novalnet_Payment_Model_Config::NN_INVOICE && !empty($additionalData['NnGuarantee'])) {
                        $setOrderStatus = $paymentObj->getConfigData('order_status_after_payment');
                    } elseif ($paymentStatus == 100) {
                        $setOrderStatus = $paymentObj->getConfigData('order_status');
                    }
                }

                // Verifies and sets order status
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)
                    ->setStatus($setOrderStatus);
                $order->save();
            }
        }
    }
}
