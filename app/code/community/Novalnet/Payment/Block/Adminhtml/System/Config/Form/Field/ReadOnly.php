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
class Novalnet_Payment_Block_Adminhtml_System_Config_Form_Field_ReadOnly extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Return element html
     *
     * @return string
     */
    protected function _getElementHtml($element)
    {
        $element->setReadonly(true);
        return parent::_getElementHtml($element);
    }
}
