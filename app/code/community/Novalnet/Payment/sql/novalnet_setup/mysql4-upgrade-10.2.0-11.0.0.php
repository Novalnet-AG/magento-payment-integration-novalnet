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
$transactionStatus = $this->getTable('novalnet_payment/transaction_status');

$installer = $this;

$installer->startSetup();

// ----------------------------------------------------------------------
// -- Drop Table novalnet_payment_amountchanged
// ----------------------------------------------------------------------

$installer->run(
    "
    DROP TABLE IF EXISTS novalnet_payment_amountchanged;
"
);

// ---------------------------------------
// -- Drop Table novalnet_payment_separefill
// ---------------------------------------

$installer->run(
    "
    DROP TABLE IF EXISTS novalnet_payment_separefill;
"
);

$connection = $installer->getConnection();

// -----------------------------------------------------------------
// -- Alter Table novalnet_payment_transaction_status
// -----------------------------------------------------------------

$connection->addColumn(
    $transactionStatus, 'novalnet_acc_details', array(
    'TYPE' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'NULLABLE' => true,
    'COMMENT' => 'novalnet_acc_details')
);

$connection->addColumn(
    $transactionStatus, 'reference_transaction', array(
    'TYPE' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'LENGTH' => 6,
    'NULLABLE' => false,
    'COMMENT' => 'reference_transaction',
    'DEFAULT' => 0)
);

$installer->endSetup();
