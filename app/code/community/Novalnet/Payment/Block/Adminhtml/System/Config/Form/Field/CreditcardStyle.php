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
class Novalnet_Payment_Block_Adminhtml_System_Config_Form_Field_CreditcardStyle extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    /**
     * Novalnet Credit Card form default styles
     */
    public $ccLocalFormConfig = array(
        'standard_style_label' => 'font-family: Raleway,Helvetica Neue,Verdana,Arial,sans-serif;font-size: 13px;font-weight: 600;color: #636363;line-height: 1.5;',
        'standard_style_input' => 'color: #636363;font-family: Helvetica Neue,Verdana,Arial,sans-serif;font-size: 14px;',
        'standard_style_css' => ''
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->addColumn(
            'regexp', array(
            'label' => Mage::helper('adminhtml')->__('Matched Expression'),
            'style' => 'width:120px',
            )
        );
        parent::__construct();
        $this->setTemplate('novalnet/system/creditcardStyle.phtml');
    }

    /**
     * Retrieve Novalnet Credit Card form style
     *
     * @param  string $param
     * @return string
     */
    public function getCcStyleValue($param)
    {
        $values = $this->getElement()->getValue();
        if (isset($values[$param])) {
            return $values[$param];
        } elseif (isset($this->ccLocalFormConfig[$param])) {
            return $this->ccLocalFormConfig[$param];
        }

        return '';
    }

}
