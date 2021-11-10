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

class Novalnet_Payment_Adminhtml_Novalnetpayment_Sales_RefundController extends Mage_Adminhtml_Sales_OrderController
{
    /**
     * Novalnet payments order grid
     *
     * @param  none
     * @return none
     */
    public function indexAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        $refundAmount = $params['nn-refund-amount'];
        $refundTid = $params['nn-refund-tid'];
        $order = $this->_initOrder();
        $helper = Mage::helper('novalnet_payment');

        if (!$refundAmount || !$refundTid) {
            $message = $this->__('The Amount should be in future');
            $this->_getSession()->addError($message);
            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
            return false;
        }

        if ($order) {
            $payment = $order->getPayment();
            $paymentObj = $payment->getMethodInstance();
            $paymentCode = $paymentObj->getCode();
            $request = new Varien_Object();
            $requestModel = $helper->getModel('Service_Api_Request'); // Get Novalnet Api request model
            $request = $requestModel->buildProcessRequest($payment, 'refund', $refundAmount); // Get Novalnet recurring process request
            $request->setTid($refundTid)
                    ->setRefundParam($refundAmount);

            $response = $paymentObj->postRequest($request); // send process request to Novalnet gateway
            $responseModel = $helper->getModel('Service_Api_Response');
            $responseModel->logTransactionTraces($request, $response, $order, $refundTid);

            if ($response->getStatus() == Novalnet_Payment_Model_Config::RESPONSE_CODE_APPROVED) {
                $refundKey = $params['nn-refund-key'];
                $data = unserialize($payment->getAdditionalData());
                $data['InstalmentDetails'][$refundKey]['Refund'][] = array('tid' => $response->getTid(), 'amount' => $refundAmount / 100);
                $data = $responseModel->getRefundTidInfo(($refundAmount / 100), $data, ($response->getTid() ? $response->getTid() : $refundTid), $refundTid);
                $payment->setAdditionalData(serialize($data))->save();

                $message = $this->__('The Refund executed properly');
                $this->_getSession()->addSuccess($message);
            } else {
                $message = $this->__($response['status_text']);
                $this->_getSession()->addError($message);
            }

            $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
            return false;
        }
        $this->_redirect('*/sales_order');
        return false;
    }
}
