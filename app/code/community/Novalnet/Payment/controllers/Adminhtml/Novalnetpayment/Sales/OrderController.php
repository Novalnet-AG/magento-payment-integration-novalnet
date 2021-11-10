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
require_once 'Mage' . DS . 'Adminhtml' . DS . 'controllers' . DS . 'Sales' . DS . 'OrderController.php';

class Novalnet_Payment_Adminhtml_Novalnetpayment_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{

    /**
     * Init layout, menu and breadcrumb
     *
     * @param  none
     * @return Mage_Adminhtml_Sales_OrderController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->setUsedModuleName('novalnet_payment')
            ->_setActiveMenu('novalnet')
            ->_addBreadcrumb($this->__('Novalnet'), $this->__('Orders'));

        return $this;
    }

    /**
     * Novalnet payments order grid
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        $this->_title($this->__('Novalnet'))->_title($this->__('Orders'));

        $this->_initAction()
            ->renderLayout();
    }

    /**
     * Create sales order block for Novalnet payments
     *
     * @param  none
     * @return none
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('novalnet_payment/adminhtml_sales_order_grid')->toHtml()
        );
    }

    /**
     * Set transactionoverview grid in sales order view page
     *
     * @param  none
     * @return none
     */
    public function transactionOverviewGridAction()
    {
        $this->_initOrder();
        $this->getResponse()->setBody(
            Mage::getBlockSingleton('novalnet_payment/adminhtml_sales_order_view_tab_transactionOverview')->toHtml()
        );
    }

    /**
     * Set transactionoverview grid in sales order view page
     *
     * @param  none
     * @return none
     */
    public function transactionTracesGridAction()
    {
        $this->_initOrder();
        $this->getResponse()->setBody(
            Mage::getBlockSingleton('novalnet_payment/adminhtml_sales_order_view_tab_transactionTraces')->toHtml()
        );
    }

    /**
     * Order confirm process for Novalnet invoice payments (Invoice/Prepayment)
     *
     * @param  none
     * @return none
     */
    public function confirmAction()
    {
        $order = $this->_initOrder(); // Get order object
        $paymentObj = $order->getPayment()->getMethodInstance(); // Get payment method instance
        $this->code = $paymentObj->getCode(); // Get payment method code
        $this->helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
        // Get payment last transaction id
        $transactionId = $this->helper->makeValidNumber($order->getPayment()->getLastTransId());
        // Build confirm payment request
        $request = $this->helper->getModel('Service_Api_Request')
            ->getprocessVendorInfo($order->getPayment()); // Get Novalnet authentication Data
        $request->setTid($transactionId)
            ->setStatus(100)
            ->setEditStatus(true)
            ->setRemoteIp($this->helper->getRealIpAddr());
        $response = $paymentObj->postRequest($request);  // Send confirm payment request
        $this->validateConfirmResponse($response, $order, $transactionId); // Validate the payport response
        // Save the transaction traces
        $responseModel = $this->helper->getModel('Service_Api_Response');
        $responseModel->logTransactionTraces($request, $response, $order, $transactionId);
        if ($response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
            $this->_getSession()->addSuccess($this->__('The order has been updated.'));
        } else {
            $message = $this->__('Error in your process request. Status Code : ' . $response->getStatus());
            $this->_getSession()->addError($message);
        }

        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }

    /**
     * Validate the confirm payment response data
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @param  string        $transactionId
     * @return none
     */
    public function validateConfirmResponse($response, $order, $transactionId)
    {
        if ($response->getTidStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
            $payment = $order->getPayment(); // Get payment object
            // Save payment additional transaction details
            $data = unserialize($payment->getAdditionalData());
            $data['captureTid'] = $transactionId;
            $data['captureCreateAt'] = Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s');
            // Due date update
            if ($response->getDueDate()) {
                $formatDate = Mage::helper('core')->formatDate($response->getDueDate());
                $note = explode('|', $data['NnNote']);
                $note[0] = 'Due Date: ' . $formatDate;
                $data['NnNote'] = implode('|', $note);
                $data['NnDueDate'] = $formatDate;
            }

            $payment->setAdditionalData(serialize($data))->save();

            // Add transaction status information
            $transactionStatus = $this->helper->getModel('Mysql4_TransactionStatus')
                ->loadByAttribute('transaction_no', $transactionId);
            $transactionStatus->setTransactionStatus($response->getTidStatus())->save();

            // Create order invoice
            if ($this->code == Novalnet_Payment_Model_Config::NN_INVOICE && $order->canInvoice()) {
                $this->saveOrderInvoice($order, $transactionId);
            } elseif ($this->code == Novalnet_Payment_Model_Config::NN_PREPAYMENT) {
                $captureOrderStatus = Mage::getStoreConfig(
                    'novalnet_global/order_status_mapping/order_status', $order->getStoreId()
                ) ? Mage::getStoreConfig('novalnet_global/order_status_mapping/order_status', $order->getStoreId())
                  : Mage_Sales_Model_Order::STATE_PROCESSING;
                $message = Mage::helper('novalnet_payment')->__('The transaction has been confirmed');
                $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $captureOrderStatus, $message, true)->save();
            }
        }
    }

    /**
     * Save order invoice
     *
     * @param  varien_object $order
     * @param  int           $transactionId
     * @return none
     */
    protected function saveOrderInvoice($order, $transactionId)
    {
        $transMode = (version_compare($this->helper->getMagentoVersion(), '1.6', '<')) ? false : true;
        $order->getPayment()->setIsTransactionClosed($transMode)->save();
        $data = unserialize($order->getPayment()->getAdditionalData());
        // Create order invoice
        $invoice = $order->prepareInvoice();
        $invoice->setTransactionId($transactionId);
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
            ->register();
        if (!isset($data['NnGuarantee'])) {
            $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN)
                ->save();
        }

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
    }

    /**
     * Duedate update process
     *
     * @param  none
     * @return none
     */
    public function duedateupdateAction()
    {
        $invoiceDuedate = $this->getRequest()->getParam('invoice_duedate');
        $order = $this->_initOrder();
        $helper = Mage::helper('novalnet_payment');

        try {
            if ($invoiceDuedate && (strtotime($invoiceDuedate) < strtotime(date('Y-m-d')))) {
                $this->_getSession()->addError($helper->__('The date should be in future'));
                $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
                return false;
            }

            if ($order) {
                $payment = $order->getPayment();
                $paymentObj = $payment->getMethodInstance();
                $paymentCode = $paymentObj->getCode();
                $request = new Varien_Object();
                $requestModel = $helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
                $request = $requestModel->getprocessVendorInfo($payment); // Get Novalnet recurring process request
                $additionalData = unserialize($payment->getAdditionalData());
                $transactionId = !empty($additionalData['NnTid'])
                    ? $helper->makeValidNumber($additionalData['NnTid'])
                    : $helper->makeValidNumber($payment->getLastTransId());

                $request->setTid($transactionId)
                        ->setStatus(100)
                        ->setEditStatus(true)
                        ->setUpdateInvAmount(1)
                        ->setDueDate($invoiceDuedate)
                        ->setRemoteIp($helper->getRealIpAddr());

                $response = $paymentObj->postRequest($request); // send process request to Novalnet gateway
                $responseModel = $helper->getModel('Service_Api_Response');
                $responseModel->logTransactionTraces($request, $response, $order, $transactionId);
                if ($response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
                    $data = unserialize($payment->getAdditionalData());
                    $formatDate = Mage::helper('core')->formatDate($invoiceDuedate);
                    if (in_array($paymentCode, array(Novalnet_Payment_Model_Config::NN_INVOICE, Novalnet_Payment_Model_Config::NN_PREPAYMENT))) {
                        $note = explode('|', $data['NnNote']);
                        $note[0] = 'Due Date: ' . $formatDate;
                        $data['NnNote'] = implode('|', $note);
                        $data['NnDueDate'] = $formatDate;
                        $successMessage = $this->__('The transaction has been updated with  due date %s', $formatDate);
                    } elseif ($paymentCode == Novalnet_Payment_Model_Config::NN_CASHPAYMENT) {
                        $data['CpDueDate'] = $formatDate;
                        $successMessage = $this->__('The transaction has been updated with slip expiry date %s', $formatDate);
                    }

                    $data['dueDateUpdateAt'] = Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s');
                    $payment->setAdditionalData(serialize($data))
                            ->save();
                    $this->_getSession()->addSuccess($successMessage);
                } else {
                    $this->_getSession()->addError($response->getStatusDesc());
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        }

       $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
    
    /**
     * Order cancel process for Novalnet Guarantee payments
     *
     * @param  none
     * @return none
     */
    public function cancelAction()
    {
        try {
            $order = $this->_initOrder(); // Get order object
            $paymentObj = $order->getPayment()->getMethodInstance();
            $payment = $order->getPayment();
            $response = $paymentObj->void($payment);
            $this->helper = Mage::helper('novalnet_payment');
            $tid = $this->helper->makeValidNumber($payment->getLastTransId());
            $statusMessage =$this->helper->__('Novalnet callback received. The transaction has been canceled on %s',
                $this->currentTime
            );
            $payment = $order->getPayment();
            $shopMode = $paymentObj->getNovalnetConfig('live_mode', true);
            $testMode = ($response->getTestMode() == 1 || $shopMode == 0) ? 1 : 0;
            $data = $payment->getAdditionalData() ? unserialize($payment->getAdditionalData()) : [];
            $data['NnTid'] = $tid;
            $data['NnStatus'] = $response->getTidStatus();
            $data['NnTestMode'] = $testMode;
            $payment->setLastTransId($tid)
                    ->setAdditionalData(serialize($data))
                    ->save();
            // Cancels the order with the cancel text
            $order->registerCancellation($statusMessage)
                  ->save();
            $successMessage = __('The order has been cancelled.');
            $this->_getSession()->addSuccess($successMessage);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
        }

       $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
    }
    

}
