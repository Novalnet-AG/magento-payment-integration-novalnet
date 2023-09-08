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
class Novalnet_Payment_Adminhtml_Novalnetpayment_ApiController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Novalnet vendor Api call
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        $helper = Mage::helper('novalnet_payment');
        $params = Mage::app()->getRequest()->getPost()
            ? Mage::app()->getRequest()->getPost() : Mage::app()->getRequest()->getQuery();
        $payportUrl = 'https://payport.novalnet.de/autoconfig';

        $request = new Varien_Object();
        $request->setHash($params['hash'])
                ->setLang(strtoupper($helper->getDefaultLanguage()));

        $gatewayModel = $helper->getModel('Service_Api_Gateway'); // Get Novalnet gateway model
        $response = $gatewayModel->payportRequestCall($request->getData(), $payportUrl);
        $this->getResponse()->setBody($response->getBody());
        return $this;
    }

    /**
     * Check admin permissions for this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
