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
 * Novalnet tables
 */
$orderTraces = $this->getTable('novalnet_payment/order_log');
$transactionStatus = $this->getTable('novalnet_payment/transaction_status');
$callback = $this->getTable('novalnet_payment/callback');

/**
 *
 * magento tables
 */
$tableOrderPayment = $this->getTable('sales/order_payment');

$installer = $this;

$installer->startSetup();

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_order_log
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$orderTraces}` (
            `nn_log_id`         int(11) UNSIGNED NOT NULL auto_increment,
            `request_data`      TEXT NOT NULL DEFAULT '',
            `response_data`     TEXT NOT NULL DEFAULT '',
            `order_id`          VARCHAR(50) NOT NULL DEFAULT '',
            `customer_id`           VARCHAR(10) NOT NULL DEFAULT '',
            `status`                int(11) UNSIGNED NOT NULL,
            `failed_reason`     TEXT NOT NULL DEFAULT '',
            `store_id`          int(11) UNSIGNED NOT NULL,
            `shop_url`          VARCHAR(255) NOT NULL DEFAULT '',
            `transaction_id`        bigint(20) NOT NULL,
            `additional_data`       TEXT NOT NULL DEFAULT '',
            `created_date`      datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (`nn_log_id`),
            INDEX `NOVALNET_ORDER_LOG` (`order_id` ASC, `transaction_id` ASC)
        );
"
);

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_transaction_status
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$transactionStatus}` (
            `nn_txn_id`         int(11) UNSIGNED NOT NULL auto_increment,
            `transaction_no`        VARCHAR(50) NOT NULL,
            `order_id`          VARCHAR(50) NOT NULL DEFAULT '',
            `transaction_status`    VARCHAR(20) NOT NULL DEFAULT 0,
            `nc_no`             VARCHAR(11) NOT NULL,
            `customer_id`           VARCHAR(10) NOT NULL DEFAULT '',
            `payment_name`      VARCHAR(50) NOT NULL DEFAULT '',
            `amount`                decimal(12,4) NOT NULL,
            `remote_ip`         VARCHAR(20) NOT NULL,
            `store_id`          int(11) UNSIGNED NOT NULL,
            `shop_url`          VARCHAR(255) NOT NULL DEFAULT '',
            `additional_data`       TEXT NOT NULL DEFAULT '',
            `created_date`      datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (`nn_txn_id`),
        INDEX `NOVALNET_TRANSACTION_STATUS` (`order_id` ASC, `transaction_no` ASC)
        );
"
);

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
    'novalnetpaypal' => 'novalnetPaypal',
    'novalnetCcpci' => 'novalnetCc',
    'novalnet_secure' => 'novalnetCc',
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
