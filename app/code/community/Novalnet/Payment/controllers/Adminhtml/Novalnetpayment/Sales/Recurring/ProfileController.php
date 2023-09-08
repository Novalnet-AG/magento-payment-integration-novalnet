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
require_once 'Mage' . DS . 'Adminhtml' . DS . 'controllers' . DS . 'Sales' . DS . 'Recurring' . DS . 'ProfileController.php';

class Novalnet_Payment_Adminhtml_Novalnetpayment_Sales_Recurring_ProfileController extends Mage_Adminhtml_Sales_Recurring_ProfileController
{

    /**
     * Recurring profiles list
     *
     * @param  none
     * @return Mage_Adminhtml_Sales_Recurring_ProfileController
     */
    public function indexAction()
    {
        $helper = Mage::helper('novalnet_payment');
        $this->_title($helper->__('Sales'))->_title($helper->__('Novalnet Recurring Profiles'))
            ->loadLayout()
            ->_setActiveMenu('novalnet')
            ->renderLayout();
        return $this;
    }

    /**
     * Profiles ajax grid
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('novalnet_payment/adminhtml_recurring_profile_grid')->toHtml()
        );
    }

}
