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
class Novalnet_Payment_Block_Method_Info_Przelewy extends Mage_Payment_Block_Info
{

    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('novalnet/method/info/Przelewy.phtml');
    }

    /**
     * Render as PDF
     *
     * @param  none
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('novalnet/method/pdf/Przelewy.phtml');
        return $this->toHtml();
    }

    /**
     * Get some specific information
     *
     * @param  string $key
     * @return array
     */
    public function getAdditionalData($key)
    {
        return Mage::helper('novalnet_payment')->getAdditionalData($this->getInfo(), $key);
    }

    /**
     * Retrieve payment method model
     *
     * @param  none
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function getMethod()
    {
        return $this->getInfo()->getMethodInstance();
    }

}
