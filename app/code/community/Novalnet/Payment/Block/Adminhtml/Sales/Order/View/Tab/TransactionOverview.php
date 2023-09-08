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
class Novalnet_Payment_Block_Adminhtml_Sales_Order_View_Tab_TransactionOverview extends Mage_Adminhtml_Block_Widget_Grid implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Transaction status view tab
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('novalnet_payment_block_adminhtml_sales_order_view_tab_transactionoverview');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setSkipGenerateContent(true);
    }

    /**
     * Return Tab label
     *
     * @param  none
     * @return string
     */
    public function getTabLabel()
    {
        return $this->novalnetHelper()->__('Novalnet - Transaction Overview');
    }

    /**
     * Return Tab title
     *
     * @param  none
     * @return string
     */
    public function getTabTitle()
    {
        return $this->novalnetHelper()->__('Novalnet - Transaction Overview');
    }

    /**
     * Can show tab in tabs
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
     * Return Tab class
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
     * Return tab url
     *
     * @param  none
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl(
            'adminhtml/novalnetpayment_sales_order/transactionOverviewGrid', array(
            '_current' => true
            )
        );
    }

    /**
     * Return grid url
     *
     * @param  none
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'adminhtml/novalnetpayment_sales_order/transactionOverviewGrid', array('_current' => true)
        );
    }

    /**
     * Return row url
     *
     * @param  mixed $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/novalnetpayment_transactionoverview/view', array('nntxn_id' => $row->getId()));
    }

    /**
     * Prepare order Collection for transaction status
     *
     * @param  none
     * @return Novalnet_Payment_Model_TransactionStatus
     */
    protected function _prepareCollection()
    {
        $collection = $this->getTransactionStatusCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare order Collection for transaction status
     *
     * @param  none
     * @return mixed $collection
     */
    public function getTransactionStatusCollection()
    {
        $collection = Mage::getModel('novalnet_payment/Mysql4_TransactionStatus')->getCollection();
        $collection->getByOrder($this->getOrder());
        return $collection;
    }

    /**
     * Define transaction status grid
     *
     * @param  none
     * @return mixed
     */
    protected function _prepareColumns()
    {
        $helper = $this->novalnetHelper();
        $this->setColumn(
            'order_id', array(
            'header' => $helper->__('Order No'),
            'width' => '200px',
            'type' => 'text',
            'index' => 'order_id',
            )
        );
        $this->setColumn(
            'txid', array(
            'header' => $helper->__('Transaction Id'),
            'width' => '200px',
            'type' => 'text',
            'index' => 'transaction_no',
            )
        );
        $this->setColumn(
            'transaction_status', array(
            'header' => $helper->__('Transaction Status'),
            'width' => '200px',
            'type' => 'text',
            'index' => 'transaction_status',
            )
        );
        $this->setColumn(
            'customer_id', array(
            'header' => $helper->__('Customer Id'),
            'width' => '200px',
            'type' => 'text',
            'index' => 'customer_id',
            )
        );
        $this->setColumn(
            'store_id', array(
            'header' => $helper->__('Store Id'),
            'width' => '200px',
            'type' => 'text',
            'index' => 'store_id',
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Add coumn
     *
     * @param  none
     * @return null
     */
    protected function setColumn($field, $fieldColumnMap)
    {
        $this->addColumn($field, $fieldColumnMap);
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
