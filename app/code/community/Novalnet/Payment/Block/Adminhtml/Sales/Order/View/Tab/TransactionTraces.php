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
class Novalnet_Payment_Block_Adminhtml_Sales_Order_View_Tab_TransactionTraces extends Mage_Adminhtml_Block_Widget implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Transaction traces view tab
     */
    public function __construct()
    {
        $this->setTemplate('novalnet/sales/order/view/tab/transactiontraces.phtml');
    }

    /**
     * Return tab label
     *
     * @param  none
     * @return string
     */
    public function getTabLabel()
    {
        return $this->novalnetHelper()->__('Novalnet - Transaction Log');
    }

    /**
     * Return tab title
     *
     * @param  none
     * @return string
     */
    public function getTabTitle()
    {
        return $this->novalnetHelper()->__('Novalnet - Transaction Log');
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
        $paymentCode = $order->getPayment()->getMethodInstance()->getCode();
        return (bool)(preg_match("/novalnet/i", $paymentCode));
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
     * Get transaction overview
     *
     * @param  none
     * @return mixed
     */
    public function getTransactionTraces()
    {
        if (!Mage::registry('novalnet_payment_transactiontraces_collection')) {
            $order = $this->getOrder();
            $collection = Mage::getModel('novalnet_payment/Mysql4_TransactionTraces')->getCollection();
            $collection->getByOrder($order);
            Mage::register('novalnet_payment_transactiontraces_collection', $collection);
        }

        return Mage::registry('novalnet_payment_transactiontraces_collection');
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
