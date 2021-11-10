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
 * 
 * magento table 
 */
$tableOrderPayment = $this->getTable('sales/order_payment');

/**
 * 
 * Novalnet tables  
 */
$recurring = $this->getTable('novalnet_payment/recurring');

$installer = $this;

$installer->startSetup();

$paymentMethod = array(
    'method' => 'novalnetBanktransfer',
);
$installer->getConnection()->update(
    $tableOrderPayment, $paymentMethod, array('method = ?' => 'novalnetSofortueberweisung')
);

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_recurring
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$recurring}` (
          `id` int(11) UNSIGNED NOT NULL auto_increment,
          `profile_id` VARCHAR(50) NOT NULL DEFAULT '',
          `signup_tid` VARCHAR(50) NOT NULL DEFAULT '',
          `billingcycle` VARCHAR(50) NOT NULL,
          `callbackcycle` VARCHAR(50) NOT NULL,
          `cycle_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          PRIMARY KEY  (`id`),
          INDEX `NOVALNET_RECURRING` (`profile_id` ASC)
        );
"
);

$installer->endSetup();
