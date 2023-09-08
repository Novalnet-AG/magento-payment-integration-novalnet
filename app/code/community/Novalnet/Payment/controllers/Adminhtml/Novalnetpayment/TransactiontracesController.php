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
class Novalnet_Payment_Adminhtml_Novalnetpayment_TransactiontracesController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init layout, menu and breadcrumb
     *
     * @param  none
     * @return Novalnet_Payment_Adminhtml_Novalnetpayment_TransactiontracesController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->setUsedModuleName('novalnet_payment')
            ->_setActiveMenu('novalnet/transactiontraces')
            ->_addBreadcrumb($this->__('Novalnet'), $this->__('Transaction Traces'));

        return $this;
    }

    /**
     * Transaction traces overview
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
     * Create transaction traces block
     *
     * @param  none
     * @return none
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('novalnet_payment/adminhtml_transactiontraces_grid')->toHtml()
        );
    }

    /**
     * Get the transaction traces information
     *
     * @param  none
     * @return none
     */
    public function viewAction()
    {
        $nnLogId = $this->getRequest()->getParam('nnlog_id');
        $transactionTraces = Mage::getModel('novalnet_payment/Mysql4_TransactionTraces');
        $tracesCollection = $transactionTraces->load($nnLogId);

        if (empty($nnLogId) || !$tracesCollection->getNnLogId()) {
            $this->_forward('noRoute');
        }

        $this->_title(sprintf("#%s", $tracesCollection->getTransactionId()));

        $transactionTraces->loadByOrderLogId($tracesCollection);

        Mage::register('novalnet_payment_transactiontraces', $tracesCollection);

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
        return Mage::getSingleton('admin/session')->isAllowed('novalnetpayment_transactiontraces');
    }

}
