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
class Novalnet_Payment_Block_Adminhtml_Sales_Order_View_Tab_Instalment extends Mage_Adminhtml_Block_Widget implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Transaction traces view tab
     */
    public function __construct()
    {
        $this->setTemplate('novalnet/sales/order/view/tab/instalment.phtml');
    }

    /**
     * Return tab label
     *
     * @param  none
     * @return string
     */
    public function getTabLabel()
    {
        return $this->novalnetHelper()->__('Instalment');
    }

    /**
     * Return tab title
     *
     * @param  none
     * @return string
     */
    public function getTabTitle()
    {
        return $this->novalnetHelper()->__('Instalment');
    }

    /**
     * Can show tab
     *
     * @param  none
     * @return boolean
     */
    public function canShowTab()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        $paymentCode = $payment->getMethodInstance()->getCode();
        if (in_array($paymentCode, array(Novalnet_Payment_Model_Config::NN_INVOICE_INSTALMENT, Novalnet_Payment_Model_Config::NN_SEPA_INSTALMENT))) {
            $helper = $this->novalnetHelper();
            $additionalData = unserialize($payment->getAdditionalData());
            $transactionId = !empty($payment->getLastTransId()) ? $payment->getLastTransId() : $additionalData['NnTid'];

            if (!empty($transactionId)) {
                // Get current transaction status information
                $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
                    ->loadByAttribute('transaction_no', $helper->makeValidNumber($transactionId)); // Get payment original transaction status
                return ($transactionStatus->getTransactionStatus() == 100) ? true : false;
            }
        }

        return false;
    }

    /**
     * Tab is hidden
     *
     * @param  none
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Return tab class
     *
     * @param  none
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax novalnet-widget-tab';
    }

    /**
     * Get current order
     *
     * @param  none
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Get URL to edit the customer.
     *
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if ($this->getOrder()->getCustomerIsGuest() || !$this->getOrder()->getCustomerId()) {
            return '';
        }

        return Mage::helper('adminhtml')->getUrl('adminhtml/customer/edit', array('id' => $this->getOrder()->getCustomerId()));
    }

    /**
     * Get order store name
     *
     * @return null|string
     */
    public function getOrderStoreName()
    {
        if ($this->getOrder()) {
            $storeId = $this->getOrder()->getStoreId();
            if ($storeId === null) {
                $deleted = __(' [deleted]');
                return nl2br($this->getOrder()->getStoreName()) . $deleted;
            }
            $store = Mage::app()->getStore($storeId);
            $name = array($store->getWebsite()->getName(), $store->getGroup()->getName(), $store->getName());
            return implode('<br/>', $name);
        }

        return null;
    }

    /**
     * Get payment additional data
     *
     * @return null|string
     */
    public function getAdditionalData($key)
    {
        $payment = $this->getOrder()->getPayment();
        $details = unserialize($payment->getAdditionalData());
        if (is_array($details)) {
            return (!empty($key) && isset($details[$key])) ? $details[$key] : '';
        }
    }

    /**
     * Get Novalnet payment helper
     *
     * @param  none
     * @return Novalnet_Payment_Helper_Data
     */
    protected function novalnetHelper()
    {
        return Mage::helper('novalnet_payment');
    }

}
