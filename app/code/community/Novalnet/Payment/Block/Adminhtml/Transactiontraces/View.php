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
class Novalnet_Payment_Block_Adminhtml_Transactiontraces_View extends Mage_Adminhtml_Block_Widget_Form_Container
{

    /**
     * Transaction traces view
     */
    public function __construct()
    {
        $this->_objectId = 'nnlog_id';
        $this->_mode = 'view';
        $this->_blockGroup = 'novalnet_payment';
        $this->_controller = 'adminhtml_transactiontraces';

        parent::__construct();

        $this->setId('transactiontraces_view');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_date');
        $this->setDefaultDir('DESC');

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        $this->_removeButton('save');
    }

    /**
     * Get Novalnet transaction traces
     *
     * @param  none
     * @return string
     */
    public function getTransactionTraces()
    {
        return Mage::registry('novalnet_payment_transactiontraces');
    }

    /**
     * Get payment method title
     *
     * @param  none
     * @return string
     */
    public function getPaymentTitle()
    {
        $title = '';
        $order = Mage::getModel("sales/order")->loadByIncrementId(trim($this->getTransactionTraces()->getOrderId()));
        if ($order->getPayment()) {
            $paymentMethod = $order->getPayment()->getMethod();
            $title = Mage::helper("novalnet_payment")->getPaymentModel($paymentMethod)->getConfigData('title');
        }

        return $title;
    }

    /**
     * Get header text of transaction traces
     *
     * @param  none
     * @return string
     */
    public function getHeaderText()
    {
        $transStatus = $this->getTransactionTraces();
        $text = Mage::helper('novalnet_payment')->__(
            'Order #%s | TID : %s ', $transStatus->getOrderId(), $transStatus->getTransactionId()
        );
        return $text;
    }

}
