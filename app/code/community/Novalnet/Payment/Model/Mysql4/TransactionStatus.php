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
class Novalnet_Payment_Model_Mysql4_TransactionStatus extends Mage_Core_Model_Abstract
{

    /**
     * Constructor
     *
     * @see    lib/Varien/Varien_Object#_construct()
     * @return Novalnet_Payment_Model_Mysql4_TransactionStatus
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('novalnet_payment/transactionStatus');
    }

    /**
     * Load order transaction status by custom attribute value. Attribute value should be unique
     *
     * @param  string $attribute
     * @param  string $value
     * @return Novalnet_Payment_Model_Mysql4_TransactionStatus
     */
    public function loadByAttribute($attribute, $value)
    {
        $this->load($value, $attribute);
        return $this;
    }

    /**
     * Load order transaction status by transaction id
     *
     * @param  mixed $transactionStatus
     * @return Novalnet_Payment_Model_Mysql4_TransactionStatus
     */
    public function loadByTransactionStatusId(Novalnet_Payment_Model_Mysql4_TransactionStatus $transactionStatus)
    {
        $this->load($transactionStatus->getNnTxnId(), 'nn_txn_id');
        return $this;
    }

}
