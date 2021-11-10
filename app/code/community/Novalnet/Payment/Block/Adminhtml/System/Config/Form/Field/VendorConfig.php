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
class Novalnet_Payment_Block_Adminhtml_System_Config_Form_Field_VendorConfig extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

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
        $this->setTemplate('novalnet/system/vendorConfig.phtml');
    }

    /**
     * Return element html
     *
     * @return string
     */
    protected function _getElementHtml($element)
    {
        $this->setElements($element);
        return $this->_toHtml();
    }

    /**
     * Return element value
     *
     * @param  none
     * @return object
     */
    public function getElement()
    {
        return $this->getElements();
    }
    
    /**
     * Remove scope label
     *
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Retrieve Novalnet Public key Value
     *
     * @param  string $param
     * @return string|null
     */
    public function getElementValue()
    {
       return $this->getElement()->getValue();
    }
}
