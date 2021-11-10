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
class Novalnet_Payment_Model_Source_InstalmentPeriod
{

    /**
     * Options getter (customer groups)
     *
     * @param  none
     * @return array $options
     */
    public function toOptionArray()
    {
        $options = array();
        $allCycles =  array(
          2 => 2 . __(' cycles'),
          3 => 3 . __(' cycles'),
          4 => 4 . __(' cycles'),
          6 => 6 . __(' cycles'),
          8 => 8 . __(' cycles'),
          9 => 9 . __(' cycles'),
          10 => 10 . __(' cycles'),
          12 => 12 . __(' cycles'),
          15 => 15 . __(' cycles'),
          18 => 18 . __(' cycles'),
          24 => 24 . __(' cycles')
        );

        foreach ($allCycles as $key => $value) {
            $options[$key] = array('value' => $key, 'label' => $value);
        }

        return $options;
    }
}
