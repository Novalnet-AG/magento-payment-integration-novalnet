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
class Novalnet_Payment_Block_Adminhtml_Sales_Order extends Mage_Adminhtml_Block_Sales_Order
{

    /**
     * Novalnet sales orders
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'novalnet_payment';
        $this->_controller = 'adminhtml_sales_order';
        $this->_headerText = Mage::helper('novalnet_payment')->__('Orders');
        $this->removeButton('add');
    }

}
