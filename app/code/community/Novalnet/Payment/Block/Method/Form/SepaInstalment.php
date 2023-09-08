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
class Novalnet_Payment_Block_Method_Form_SepaInstalment extends Mage_Payment_Block_Form
{

    /**
     * Init default template for block
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('novalnet/method/form/SepaInstalment.phtml');
    }

    /**
     * Get information to end user from config
     *
     * @param  none
     * @return string
     */
    public function getUserInfo()
    {
        return trim(strip_tags($this->getMethod()->getConfigData('booking_reference')));
    }

    /**
     * Get billing address information
     *
     * @param  none
     * @return array
     */
    public function getBillingInfo()
    {
        $checkoutSession = Mage::helper('novalnet_payment')->getCheckout();
        return $checkoutSession->getQuote()->getBillingAddress();
    }

    /**
     * Get payment logo available status
     *
     * @param  none
     * @return boolean
     */
    public function logoAvailableStatus()
    {
        return Mage::getStoreConfig('novalnet_global/novalnet/enable_payment_logo');
    }

    /**
     * Get payment mode (test/live)
     *
     * @param  none
     * @return boolean
     */
    public function getPaymentMode()
    {
        $paymentMethod = Mage::getStoreConfig('novalnet_global/novalnet/live_mode');
        return (!preg_match('/' . $this->getMethodCode() . '/i', $paymentMethod)) ? false : true;
    }

    /**
     * Get Novalnet Guarantee instalment cycle periods
     *
     * @return null|string
     */
    public function getInstalmentCycle()
    {
        return $this->getMethod()->getConfigData('instalment_cycle_periods');
    }

    /**
     * Get Novalnet Guarantee instalment total periods
     *
     * @return null|string
     */
    public function getInstalmentTotal()
    {
        return $this->getMethod()->getConfigData('instalment_total_period');
    }
}
