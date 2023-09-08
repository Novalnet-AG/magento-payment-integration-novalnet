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
class Novalnet_Payment_Block_Adminhtml_Recurring_Profile extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    protected $_blockGroup = 'novalnet_payment';
    protected $_controller = 'adminhtml_recurring_profile';

    /**
     * Set header text and remove "addnew" button
     */
    public function __construct()
    {
        $this->_headerText = Mage::helper('novalnet_payment')->__('Novalnet Recurring Profiles');
        parent::__construct();
        $this->_removeButton('add');
    }

}
