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
 * novalnet table 
 */
$affiliateInfo = $this->getTable('novalnet_payment/affiliate_info');
$affiliateUserInfo = $this->getTable('novalnet_payment/affiliate_user');

$installer = $this;

$installer->startSetup();

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_aff_account_detail
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$affiliateInfo}` (
          `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
          `vendor_id` int(11) unsigned NOT NULL,
          `vendor_authcode` varchar(40) NOT NULL,
          `product_id` int(11) unsigned NOT NULL,
          `product_url` varchar(200) NOT NULL,
          `activation_date` datetime NOT NULL,
          `aff_id` int(11) unsigned DEFAULT NULL,
          `aff_authcode` varchar(40) DEFAULT NULL,
          `aff_accesskey` varchar(40) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `vendor_id` (`vendor_id`),
          KEY `product_id` (`product_id`),
          KEY `aff_id` (`aff_id`),
          INDEX `NOVALNET_AFFILIATE` (`aff_id` ASC)
        );
"
);

// -----------------------------------------------------------------
// -- Create Table novalnet_payment_aff_user_detail
// -----------------------------------------------------------------
$installer->run(
    "
        CREATE TABLE IF NOT EXISTS `{$affiliateUserInfo}` (
          `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
          `aff_id` int(11) unsigned NULL,
          `customer_no` varchar(40) NULL,
          `aff_order_no` varchar(40) NULL,
          PRIMARY KEY (`id`),
          KEY `aff_id` (`aff_id`),
          KEY `customer_no` (`customer_no`),
          KEY `aff_order_no` (`aff_order_no`),
          INDEX `NOVALNET_AFFILIATE_USER` (`customer_no` ASC)
        );
"
);

$installer->endSetup();
