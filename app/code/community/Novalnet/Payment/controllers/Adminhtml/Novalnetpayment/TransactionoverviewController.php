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
class Novalnet_Payment_Adminhtml_Novalnetpayment_TransactionoverviewController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init layout, menu and breadcrumb
     *
     * @param  none
     * @return Novalnet_Payment_Adminhtml_Novalnetpayment_TransactionoverviewController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->setUsedModuleName('novalnet_payment')
            ->_setActiveMenu('novalnet/transactionstatus')
            ->_addBreadcrumb($this->__('Novalnet'), $this->__('Transaction'));

        return $this;
    }

    /**
     * Transaction status overview
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Create transaction status block
     *
     * @param  none
     * @return none
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('novalnet_payment/adminhtml_transactionoverview_grid')->toHtml()
        );
    }

    /**
     * Get the transaction status information
     *
     * @param  none
     * @return none
     */
    public function viewAction()
    {
        $nnTxnId = $this->getRequest()->getParam('nntxn_id');
        $transactionStatus = Mage::getModel('novalnet_payment/Mysql4_TransactionStatus');
        $statusCollection = $transactionStatus->load($nnTxnId);

        if (empty($nnTxnId) || !$statusCollection->getNnTxnId()) {
            $this->_forward('noRoute');
        }

        $this->_title(sprintf("#%s", $statusCollection->getTransactionNo()));
        $transactionStatus->loadByTransactionStatusId($statusCollection);
        Mage::register('novalnet_payment_transactionoverview', $transactionStatus);
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Check admin permissions for this controller
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('novalnetpayment_transactionoverview');
    }

}
