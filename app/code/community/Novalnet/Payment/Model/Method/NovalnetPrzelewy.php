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
class Novalnet_Payment_Model_Method_NovalnetPrzelewy extends Novalnet_Payment_Model_Method_Abstract
{

    protected $_code = Novalnet_Payment_Model_Config::NN_PRZELEWY;
    protected $_canUseInternal = Novalnet_Payment_Model_Config::NN_PRZELEWY_CAN_USE_INTERNAL;
    protected $_formBlockType = Novalnet_Payment_Model_Config::NN_PRZELEWY_FORM_BLOCK;
    protected $_infoBlockType = Novalnet_Payment_Model_Config::NN_PRZELEWY_INFO_BLOCK;

    /**
     * Check whether payment method can be used
     *
     * @param  Mage_Sales_Model_Quote|null $quote
     * @return boolean
     */
    public function isAvailable($quote = null)
    {
        if (!empty($quote) && $quote->hasNominalItems()) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Get Novalnet payment redirect URL
     *
     * @param  none
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->helper->getUrl(Novalnet_Payment_Model_Config::GATEWAY_REDIRECT_URL);
    }

}
