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

/**
 * Nominal items total
 * Collects only items segregated by isNominal property
 * Aggregates row totals per item
 */
class Novalnet_Payment_Model_Quote_Address_Total_Nominal extends Mage_Sales_Model_Quote_Address_Total_Nominal
{

    /**
     * Invoke collector for nominal items
     *
     * @param Mage_Sales_Model_Quote_Address               $address
     * @param Mage_Sales_Model_Quote_Address_Total_Nominal
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        $collector = Mage::getSingleton(
            'sales/quote_address_total_nominal_collector', array(
            'store' => $address->getQuote()->getStore())
        );

        // invoke nominal totals
        foreach ($collector->getCollectors() as $model) {
            $model->collect($address);
        }

        // aggregate collected amounts into one to have sort of grand total per item
        $total = 0;
        foreach ($address->getAllNominalItems() as $item) {
            $rowTotal = 0;
            $baseRowTotal = 0;
            $totalDetails = array();
            foreach ($collector->getCollectors() as $model) {
                $itemRowTotal = $model->getItemRowTotal($item);
                if ($model->getIsItemRowTotalCompoundable($item)) {
                    $rowTotal += $itemRowTotal;
                    $baseRowTotal += $model->getItemBaseRowTotal($item);
                    $isCompounded = true;
                } else {
                    $isCompounded = false;
                }

                if ((float) $itemRowTotal > 0) {
                    $label = $model->getLabel();
                    $helper = Mage::helper('novalnet_payment');
                    $regularPayment = $helper->__('Regular Payment');
                    $shipping = $helper->__('Shipping');
                    $tax = $helper->__('Tax');
                    if ($label == $regularPayment || $label == $shipping || $label == $tax) {
                        $total = $total + $itemRowTotal;
                    }

                    $totalDetails[] = new Varien_Object(
                        array(
                        'label' => $label,
                        'amount' => $itemRowTotal,
                        'is_compounded' => $isCompounded,
                        )
                    );
                }
            }

            $item->setNominalRowTotal($rowTotal);
            $item->setBaseNominalRowTotal($baseRowTotal);
            $item->setNominalTotalDetails($totalDetails);
            // Assign recurring payment amount for Novalnet subscription process (fraud prevention)
            Mage::getSingleton('checkout/session')->setNnRegularAmount($total)
                ->setNnRowAmount($rowTotal);
        }

        return $this;
    }

    /**
     * Fetch collected nominal items
     *
     * @param  Mage_Sales_Model_Quote_Address $address
     * @return Mage_Sales_Model_Quote_Address_Total_Nominal
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $items = $address->getAllNominalItems();
        if ($items) {
            $address->addTotal(
                array(
                    'code' => $this->getCode(),
                    'title' => Mage::helper('sales')->__('Nominal Items'),
                    'items' => $items,
                    'area' => 'footer',
                )
            );
        }

        return $this;
    }

}
