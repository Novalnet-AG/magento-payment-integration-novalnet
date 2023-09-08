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
class Novalnet_Payment_Model_Observer_InvoiceView
{

    /**
     * Add buttons to sales order invoice view (single order)
     *
     * @param  Varien_Object $observer
     * @return Novalnet_Payment_Model_Observer_OrderView
     */
    public function addButton($observer)
    {
        $block = $observer->getEvent()->getBlock();

        // Add buttons to sales order view (single order)
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_View) {
            $order = $block->getInvoice()->getOrder(); // Get current order
            $orderPayment = $order->getPayment(); // Get order payment
            $paymentCode = $order->getPayment()->getMethodInstance()->getCode(); // Get payment method code
            // Allow only for Novalnet payment methods
            if (preg_match("/novalnet/i", $paymentCode)) {
                $block->removeButton('print');
                $block->removeButton('capture');

                if ($block->getInvoice()->getOrder()->canCreditmemo()) {
                    if (($orderPayment->canRefundPartialPerInvoice()
                         && $orderPayment->getAmountPaid() > $orderPayment->getAmountRefunded()
                        ) || ($orderPayment->canRefund() && !$block->getInvoice()->getIsUsedForRefund())
                    ) {
                        $this->getCreditMemoButton($block);
                    }
                }

                if ($block->getInvoice()->getId()) {
                    $this->getPrintButton($block);
                }
            }
        }
    }

    /**
     * Add creditmemo button
     *
     * @param  Mage_Adminhtml_Block_Sales_Order_Invoice_View $block
     * @return none
     */
    public function getCreditMemoButton($block)
    {
        $block->addButton(
            'capture', array(// capture?
            'label' => Mage::helper('sales')->__('Credit Memo'),
            'class' => 'go',
            'onclick' => 'setLocation(\'' . $block->getCreditMemoUrl() . '\')'
            )
        );
    }

    /**
     * Add print button
     *
     * @param  Mage_Adminhtml_Block_Sales_Order_Invoice_View $block
     * @return none
     */
    public function getPrintButton($block)
    {
        $block->addButton(
            'print', array(
            'label' => Mage::helper('sales')->__('Print'),
            'class' => 'save',
            'onclick' => 'setLocation(\'' . $block->getPrintUrl() . '\')'
            )
        );
    }

}
