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
class Novalnet_Payment_Model_Source_CustomerGroups
{

    /**
     * Options getter (customer groups)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        $collection = Mage::getModel('customer/group')->getCollection();
        $groups = array();
        foreach ($collection as $group) {
            $groupInfo = array(
                'customer_group_id' => $group->getCustomerGroupId(),
                'customer_group_code' => $group->getCustomerGroupCode(),
            );

            if ($groupInfo) {
                array_push($groups, $groupInfo);
            }
        }

        foreach ($groups as $name) {
            $options[] = array(
                'value' => $name['customer_group_id'],
                'label' => $name['customer_group_code']
            );
        }

        return $options;
    }

}
