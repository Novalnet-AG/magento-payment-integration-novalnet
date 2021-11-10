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
class Novalnet_Payment_Model_Mysql4_AffiliateUser extends Mage_Core_Model_Abstract
{

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('novalnet_payment/affiliateUser');
    }

    /**
     * Load affiliate user information by custom attribute value. Attribute value should be unique
     *
     * @param  string $attribute
     * @param  string $value
     * @return Novalnet_Payment_Model_Mysql4_AffiliateUser
     */
    public function loadByAttribute($attribute, $value)
    {
        $this->load($value, $attribute);
        return $this;
    }

}
