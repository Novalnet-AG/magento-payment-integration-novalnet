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
class Novalnet_Payment_Block_Adminhtml_System_Config_Form_Field_GuaranteeInfo extends Mage_Adminhtml_Block_System_Config_Form_Field_Heading
{

    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><h3 id="%s">%s</h3>%s</td></tr>',
            $element->getHtmlId(), $element->getHtmlId(), $element->getLabel(), $element->getComment()
        );
    }

}
