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
class Novalnet_Payment_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public $novalnetPayments = array();
    public $groups = array();

    /**
     * Novalnet sales order grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('novalnet_sales_order_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);

        // Novalnet
        $novalnetPayment = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('paymentMethods');
        $novalPaymentMethods = array_keys($novalnetPayment);

        foreach ($novalPaymentMethods as $paymentCode) {
            $paymentTitle = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
            $this->novalnetPayments[$paymentCode] = $paymentTitle;
        }

        // Novalnet
        // Customer groups
        $this->groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt' => 0))
            ->load()
            ->toOptionHash();
        // Customer groups
    }

    /**
     * Retrieve collection class
     *
     * @param  none
     * @return string
     */
    protected function _getCollectionClass()
    {
        return 'sales/order_grid_collection';
    }

    /**
     * Prepare order Collection for novalnet payments
     *
     * @param  none
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $tablePrefix = Mage::getConfig()->getTablePrefix();
        // For customer email filter
        $collection->getSelect()->joinLeft(
            array('email' => $tablePrefix . 'sales_flat_order'),
            'email.entity_id=main_table.entity_id',
            array('email.customer_email')
        );
        $collection->getSelect()->joinLeft(
            array('cgroup' => $tablePrefix . 'sales_flat_order'),
            'cgroup.entity_id=main_table.entity_id',
            array('cgroup.customer_group_id')
        );

        if (version_compare(Mage::helper('novalnet_payment')->getMagentoVersion(), '1.6.0.0', '>')) {
            $collection->join(array('payment' => 'sales/order_payment'), 'main_table.entity_id = parent_id', 'method')
                ->getSelect()->where("`payment`.`method` like '%novalnet%'");
        } else {
            $flatOrderPayment = $collection->getTable('sales/order_payment');
            $collection->getSelect()->join(
                array('payment' => $flatOrderPayment), 'main_table.entity_id = payment.parent_id', 'method'
            )->where("`payment`.`method` like '%novalnet%'");
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare column for order grid
     *
     * @param  none
     * @return Novalnet_Payment_Block_Adminhtml_Sales_Order_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'real_order_id', array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type' => 'text',
            'index' => 'increment_id',
            'filter_index' => 'main_table.increment_id'
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id', array(
                'header' => Mage::helper('sales')->__('Purchased From (Store)'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
                'filter_index' => 'main_table.store_id'
                )
            );
        }

        $this->addColumn(
            'created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
            'filter_index' => 'main_table.created_at'
            )
        );

        $this->addColumn(
            'billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
            )
        );

        $this->addColumn(
            'shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
            )
        );

        $this->addColumn(
            'customer_email', array(
            'header' => Mage::helper('sales')->__('Email'),
            'index' => 'customer_email',
            'filter_index' => 'email.customer_email'
            )
        );

        $this->addColumn(
            'customer_group_id', array(
            'header' => Mage::helper('sales')->__('Group'),
            'index' => 'customer_group_id',
            'filter_index' => 'cgroup.customer_group_id',
            'type' => 'options',
            'options' => $this->groups,
            )
        );

        $this->addColumn(
            'base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type' => 'currency',
            'currency' => 'base_currency_code',
            'filter_index' => 'main_table.base_grand_total'
            )
        );

        $this->addColumn(
            'grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
            'filter_index' => 'main_table.grand_total'
            )
        );

        $orderStatus = Mage::getSingleton('sales/order_config')->getStatuses();
        uasort($orderStatus, 'strcasecmp');
        $this->addColumn(
            'status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '70px',
            'options' => $orderStatus,
            'filter_index' => 'main_table.status'
            )
        );

        uasort($this->novalnetPayments, 'strcasecmp');
        $this->addColumn(
            'novalnet_method', array(
            'header' => Mage::helper('sales')->__('Payment Method'),
            'index' => 'method',
            'type' => 'options',
            'width' => '70px',
            'options' => $this->novalnetPayments,
                ), 'method'
        );

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn(
                'action', array(
                'header' => Mage::helper('sales')->__('Action'),
                'width' => '80px',
                'type' => 'action',
                'getter' => 'getId',
                'renderer' => 'novalnet_payment/adminhtml_sales_order_render_delete',
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
                )
            );
        }

        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');

        $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));

        parent::_prepareColumns();
        return $this;
    }

    /**
     * prepare column for massaction order grid
     *
     * @param  none
     * @return Novalnet_Payment_Block_Adminhtml_Sales_Order_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/cancel')) {
            $this->getMassactionBlock()->addItem(
                'cancel_order', array(
                'label' => Mage::helper('sales')->__('Cancel'),
                'url' => $this->getUrl('*/*/massCancel'),
                )
            );
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/hold')) {
            $this->getMassactionBlock()->addItem(
                'hold_order', array(
                'label' => Mage::helper('sales')->__('Hold'),
                'url' => $this->getUrl('*/*/massHold'),
                )
            );
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/unhold')) {
            $this->getMassactionBlock()->addItem(
                'unhold_order', array(
                'label' => Mage::helper('sales')->__('Unhold'),
                'url' => $this->getUrl('*/*/massUnhold'),
                )
            );
        }

        $this->getMassactionBlock()->addItem(
            'pdfinvoices_order', array(
            'label' => Mage::helper('sales')->__('Print Invoices'),
            'url' => $this->getUrl('*/*/pdfinvoices'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'pdfshipments_order', array(
            'label' => Mage::helper('sales')->__('Print Packingslips'),
            'url' => $this->getUrl('*/*/pdfshipments'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'pdfcreditmemos_order', array(
            'label' => Mage::helper('sales')->__('Print Credit Memos'),
            'url' => $this->getUrl('*/*/pdfcreditmemos'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'pdfdocs_order', array(
            'label' => Mage::helper('sales')->__('Print All'),
            'url' => $this->getUrl('*/*/pdfdocs'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'print_shipping_label', array(
            'label' => Mage::helper('sales')->__('Print Shipping Labels'),
            'url' => $this->getUrl('adminhtml/sales_order_shipment/massPrintShippingLabel'),
            )
        );

        $this->getMassactionBlock()->addItem(
            'delete_order', array(
            'label' => Mage::helper('sales')->__('Delete Order'),
            'url' => $this->getUrl('adminhtml/novalnetpayment_sales_deleteorder/massDelete'),
            'confirm' => Mage::helper('sales')->__('Are you sure you want to delete order?')
            )
        );

        return $this;
    }

    /**
     * Return row url
     *
     * @param  none
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
    }

    /**
     * Return grid url
     *
     * @param  none
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}
