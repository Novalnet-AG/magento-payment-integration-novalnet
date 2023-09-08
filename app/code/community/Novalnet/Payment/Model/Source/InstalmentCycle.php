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
class Novalnet_Payment_Model_Source_InstalmentCycle
{

    /**
     * Options getter (customer groups)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        $options = array(
            array(
                'value' => 1,
                'label' => __('Every month')
            ),
            array(
                'value' => 2,
                'label' => __('Every 2 months')
            ),
            array(
                'value' => 3,
                'label' => __('Every 3 months')
            ),
            array(
                'value' => 4,
                'label' => __('Every 4 months')
            ),
            array(
                'value' => 6,
                'label' => __('Every 6 months')
            )
        );

        return $options;
    }
}
