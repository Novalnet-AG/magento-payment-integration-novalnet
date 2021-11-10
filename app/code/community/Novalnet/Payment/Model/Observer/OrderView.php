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
class Novalnet_Payment_Model_Observer_OrderView
{

    /**
     * Add buttons to sales order view (single order)
     *
     * @param  Varien_Object $observer
     * @return Novalnet_Payment_Model_Observer_OrderView
     */
    public function addButton($observer)
    {
        $block = $observer->getEvent()->getBlock();

        // Add buttons to sales order view (single order)
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $block->getOrder(); // Get current order
            $paymentCode = $order->getPayment()->getMethodInstance()->getCode(); // Get payment method code
            $helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
            // Get Novalnet payment additional data if exist
            $additionalData = (preg_match("/novalnet/i", $paymentCode))
                ? unserialize($order->getPayment()->getAdditionalData()) : '';
            // Get payment Novalnet transaction id if exist
            $transactionId = !empty($additionalData['NnTid']) ? $helper->makeValidNumber($additionalData['NnTid']) : '';

            if (!empty($transactionId)) { // allow only for Novalnet payment methods
                // Get current transaction status information
                $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
                    ->loadByAttribute('transaction_no', $transactionId);
                $paymentStatus = $transactionStatus->getTransactionStatus(); // Get payment original transaction status
                // Get payment transaction amount
                $transactionAmount = (int) str_replace(array('.', ','), '', $transactionStatus->getAmount());

                if ($paymentStatus) {
                    // Rename invoice button for Novalnet payment orders
                    $block->updateButton('order_invoice', 'label', $helper->__('Capture'));
                    // Removes offline credit memo button in order history
                    $block->removeButton('order_creditmemo');
                }

                // Removes void button for success payment status
                if ($paymentStatus
                    && (!$transactionAmount
                    || in_array($paymentStatus, array(Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED, Novalnet_Payment_Model_Config::PRZELEWY_PENDING_STATUS, Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS, Novalnet_Payment_Model_Config::PAYMENT_VOID_STATUS, Novalnet_Payment_Model_Config::GUARANTEE_PENDING_STATUS)))
                ) {
                    $block->removeButton('void_payment');
                }

                // Removes order invoice button
                if ($paymentStatus
                    && (in_array($paymentStatus, array(Novalnet_Payment_Model_Config::PAYPAL_PENDING_STATUS, Novalnet_Payment_Model_Config::PRZELEWY_PENDING_STATUS, Novalnet_Payment_Model_Config::PAYMENT_VOID_STATUS, Novalnet_Payment_Model_Config::GUARANTEE_PENDING_STATUS))
                     || in_array(
                         $paymentCode, array(Novalnet_Payment_Model_Config::NN_INVOICE,
                         Novalnet_Payment_Model_Config::NN_PREPAYMENT,
                         Novalnet_Payment_Model_Config::NN_CASHPAYMENT)
                     ) || !$transactionAmount)) {
                    $block->removeButton('order_invoice');
                }

                // Add confirm button for Novalnet invoice payments (Invoice/Prepayment)
                if ($order->canInvoice() && $paymentStatus == 91
                  && $paymentCode != Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT) {
                    $confirmUrl = $block->getUrl(
                        'adminhtml/novalnetpayment_sales_order/confirm', array('_current' => true)
                    );
                    $message = Mage::helper('sales')->__('Are you sure you want to capture the payment?');
                    $block->addButton(
                        'novalnet_confirm', array(
                        'label' => Mage::helper('novalnet_payment')->__('Novalnet Capture'),
                        'onclick' => "confirmSetLocation('{$message}', '{$confirmUrl}')",
                        )
                    );
                }
                
                $guranteePayments = array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT,
                                            Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT,
                                            Novalnet_Payment_Model_Config::NN_SEPA,
                                            Novalnet_Payment_Model_Config::NN_INVOICE);
                if ( $paymentStatus == 75
                  && in_array($paymentCode, $guranteePayments)) {
                    $cancelUrl = $block->getUrl('adminhtml/novalnetpayment_sales_order/cancel', array('_current' => true));
                    $message = Mage::helper('sales')->__('Are you sure you want to cancel the payment?');
                    $block->removeButton('order_cancel');
                    $block->addButton(
                        'novalnet_cancel', array(
                        'label' => Mage::helper('novalnet_payment')->__('Cancel'),
                        'onclick' => "confirmSetLocation('{$message}', '{$cancelUrl}')",
                        )
                    );
                }
            }
        }
    }

}
