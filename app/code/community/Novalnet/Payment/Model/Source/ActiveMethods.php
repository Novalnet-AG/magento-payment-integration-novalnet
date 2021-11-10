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
class Novalnet_Payment_Model_Source_ActiveMethods
{

    /**
     * Options getter (Active Novalnet payment methods)
     *
     * @param  none
     * @return array $methods
     */
    public function toOptionArray()
    {
        $methods = array();
        $activePayment = false;
        $scopeId = Mage::helper('novalnet_payment')->getScopeId(); // Get store id
        // Novalnet payment methods
        $novalnetPayments = array_keys(
            Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('paymentMethods')
        );

        foreach ($novalnetPayments as $paymentCode) {
            $paymentActive = Mage::getStoreConfig("payment/$paymentCode/active", $scopeId);
            if ($paymentActive == true) {
                $paymentTitle = Mage::getStoreConfig("payment/$paymentCode/title", $scopeId);
                $methods[$paymentCode] = array(
                    'label' => $paymentTitle,
                    'value' => $paymentCode,
                );
                $activePayment = true;
            }
        }

        if (!$activePayment) {
            $methods[$paymentCode] = array(
                'label' => Mage::helper('novalnet_payment')->__('No active payment method for this store'),
                'value' => false,
            );
        }

        return $methods;
    }

}
