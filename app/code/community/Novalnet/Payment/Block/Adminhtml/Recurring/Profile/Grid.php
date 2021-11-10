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
class Novalnet_Payment_Block_Adminhtml_Recurring_Profile_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    /**
     * Set ajax/session parameters
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('novalnet_recurring_profile_grid');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare grid collection object
     *
     * @param  none
     * @return Mage_Sales_Block_Adminhtml_Recurring_Profile_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('sales/recurring_profile_collection')
            ->addFieldToFilter(
                'method_code', array(
                'like' => '%novalnet%',
                )
            );
        $collection->setOrder('profile_id', 'desc');
        $this->setCollection($collection);

        if (!$this->getParam($this->getVarNameSort())) {
            $collection->setOrder('profile_id', 'desc');
        }

        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @param  none
     * @return Mage_Sales_Block_Adminhtml_Recurring_Profile_Grid
     */
    protected function _prepareColumns()
    {
        $profile = Mage::getModel('sales/recurring_profile');

        $this->addColumn(
            'reference_id', array(
            'header' => $profile->getFieldLabel('reference_id'),
            'index' => 'reference_id',
            'html_decorators' => array('nobr'),
            'width' => 1,
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id', array(
                'header' => Mage::helper('adminhtml')->__('Store'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
                )
            );
        }

        $profileState = $profile->getAllStates();
        uasort($profileState, 'strcasecmp');
        $this->addColumn(
            'state', array(
            'header' => $profile->getFieldLabel('state'),
            'index' => 'state',
            'type' => 'options',
            'options' => $profileState,
            'html_decorators' => array('nobr'),
            'width' => 1,
            )
        );

        $this->addColumn(
            'created_at', array(
            'header' => Mage::helper('novalnet_payment')->__('Created on'),
            'index' => 'created_at',
            'type' => 'datetime',
            'html_decorators' => array('nobr'),
            'width' => 1,
            )
        );

        $this->addColumn(
            'updated_at', array(
            'header' => Mage::helper('novalnet_payment')->__('Updated on'),
            'index' => 'updated_at',
            'type' => 'datetime',
            'html_decorators' => array('nobr'),
            'width' => 1,
            )
        );

        $methods = array();
        foreach (Mage::helper('payment')->getRecurringProfileMethods() as $method) {
            if (preg_match("/novalnet/i", $method->getCode())) {
                $methods[$method->getCode()] = $method->getTitle();
            }
        }

        $this->addColumn(
            'method_code', array(
            'header' => $profile->getFieldLabel('method_code'),
            'index' => 'method_code',
            'type' => 'options',
            'options' => $methods,
            )
        );

        $this->addColumn(
            'schedule_description', array(
            'header' => $profile->getFieldLabel('schedule_description'),
            'index' => 'schedule_description',
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Get row url for js event handlers
     *
     * @param  mixed $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('adminhtml/sales_recurring_profile/view', array('profile' => $row->getId()));
    }

    /**
     * Get grid url
     *
     * @param  none
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}
