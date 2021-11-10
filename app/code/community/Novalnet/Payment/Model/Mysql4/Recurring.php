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
class Novalnet_Payment_Model_Mysql4_Recurring extends Mage_Core_Model_Abstract
{

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('novalnet_payment/recurring');
    }

    /**
     * Get recurring profile increment id
     *
     * @param  Varien_Object $profile
     * @return int $incrementId
     */
    public function getRecurringOrderNo($profile)
    {
        $incrementId = array();
        $recurringCollection = Mage::getResourceModel('sales/order_grid_collection')
            ->addRecurringProfilesFilter($profile->getId());
        foreach ($recurringCollection as $profileValue) {
            $incrementId[] = $profileValue->getIncrementId();
        }

        return $incrementId;
    }

    /**
     * Get sales order
     *
     * @param  int $incrementId
     * @return Varien_Object $order
     */
    public function getOrderByIncrementId($incrementId)
    {
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        return $order;
    }

}
