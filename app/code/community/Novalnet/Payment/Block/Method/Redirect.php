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
class Novalnet_Payment_Block_Method_Redirect extends Mage_Core_Block_Abstract
{

    protected function _toHtml()
    {
        $helper = Mage::helper('novalnet_payment'); // Get Novalnet payment helper
        $paymentCode = $this->getOrder()->getPayment()->getMethodInstance()->getCode(); // Get payment method code
        $actionUrl = $helper->getPayportUrl('redirect', $paymentCode); // Get Novalnet payport URL
        $params = $helper->getMethodSession($paymentCode)->getPaymentReqData(); // Get payment method session
        $currentLang = array('nnLang' => Mage::getSingleton('core/translate')->getLocale());
        $this->getOrder()->getPayment()->setAdditionalData(serialize($currentLang))->save();

        // Create form
        $form = new Varien_Data_Form();
        $form->setAction($actionUrl)
            ->setId($paymentCode)
            ->setName($paymentCode)
            ->setMethod(Novalnet_Payment_Model_Config::NOVALNET_RETURN_METHOD)
            ->setUseContainer(true);
        foreach ($params->getData() as $field => $value) {
            $form->addField($field, 'hidden', array('name' => $field, 'value' => $value));
        }

        // Save payment transaction request data
        $request = $helper->getModel('Service_Api_Response')->removeSensitiveData($params, $paymentCode);
        $transactionTraces = $helper->getModel('Mysql4_TransactionTraces');
        $transactionTraces->setOrderId($request->getOrderNo())
            ->setRequestData(base64_encode(serialize($request->getData())))
            ->setCreatedDate($helper->getCurrentDateTime())
            ->save();

        $submitButton = new Varien_Data_Form_Element_Submit(
            array(
            'value' => $this->__('Click here if you are not redirected within 10 seconds...'),
            )
        );
        $submitButton->setId("submit_to_{$paymentCode}_button");
        $form->addElement($submitButton);

        $html = '<html><body>';
        $html.= $this->__('You will be redirected to Novalnet AG in a few seconds.');
        $html.= $form->toHtml();
        $html.= '<script type="text/javascript">
        document.getElementById("'.$paymentCode.'").addEventListener("submit", function(e){
				document.getElementById("submit_to_'.$paymentCode.'_button").setAttribute("disabled",true);
		});
        document.getElementById("' . $paymentCode . '").dispatchEvent(new Event("submit"));
        document.getElementById("' . $paymentCode . '").submit()</script>';
        $html.= '</body></html>';
        return $html;
    }

}
