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
 * Novalnet table
 */
$callback = $this->getTable('novalnet_payment/callback');

/**
 *
 * magento table
 */
$tableOrderPayment = $this->getTable('sales/order_payment');

$installer = $this;

$installer->startSetup();

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_callback
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$callback}` (
          `id` int(11) UNSIGNED NOT NULL auto_increment,
          `order_id` VARCHAR(50) NOT NULL DEFAULT '',
          `callback_amount` int(11) UNSIGNED NOT NULL,
          `reference_tid` VARCHAR(50) NOT NULL,
          `callback_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
          `callback_tid` VARCHAR(50) NOT NULL,
          `callback_log` TEXT NOT NULL DEFAULT '',
          PRIMARY KEY  (`id`),
          INDEX `NOVALNET_CALLBACK` (`order_id` ASC)
        );
"
);

$methodFields = array();
$methodData = array(
    'sofortueberweisung' => 'novalnetSofortueberweisung',
    'novalnetsofortueberweisung' => 'novalnetSofortueberweisung',
    'novalnetpaypal' => 'novalnetPaypal',
    'novalnetideal' => 'novalnetIdeal',
    'novalnetCcpci' => 'novalnetCc',
    'novalnet_secure' => 'novalnetCc',
    'novalnetSecure' => 'novalnetCc',
    'novalnetElvatpci' => 'novalnetSepa',
    'novalnetElvdepci' => 'novalnetSepa'
);

foreach ($methodData as $variableId => $value) {
    $methodFields['method'] = $value;
    $installer->getConnection()->update(
        $tableOrderPayment, $methodFields, array('method = ?' => $variableId)
    );
}

$installer->endSetup();
