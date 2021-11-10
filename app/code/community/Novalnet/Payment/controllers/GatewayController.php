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
class Novalnet_Payment_GatewayController extends Mage_Core_Controller_Front_Action
{

    /**
     * Initiate redirect payment process
     *
     * @param  none
     * @return none
     */
    public function redirectAction()
    {
        $helper = $this->_getHelper(); // Get Novalnet payment helper
        $session = $helper->getCheckoutSession(); // Get checkout session

        try {
            $order = $this->_getOrder(); // Get order object
            $payment = $order->getPayment(); // Get payment object
            $paymentObj = $payment->getMethodInstance(); // Get payment method instance
            $quoteId = $session->getQuoteId() ? $session->getQuoteId() : $session->getLastQuoteId();
            $items = Mage::getModel('sales/quote')->load($quoteId)->getItemsQty();
            $session->getQuote()->setIsActive(true)->save();
            $redirectActionFlag = $paymentObj->getCode() . '_redirectAction';

            if ($payment->getAdditionalInformation($redirectActionFlag) != 1 && $session->getLastRealOrderId() && $items
            ) {
                $payment->setAdditionalInformation($redirectActionFlag, 1);
                // Set order status as on-hold
                $status = $state = Mage_Sales_Model_Order::STATE_HOLDED;
                $order->setState($state, $status, $helper->__('Customer was redirected to Novalnet'), false)->save();
                $this->getResponse()->setBody(
                    $this->getLayout()
                        ->createBlock(Novalnet_Payment_Model_Config::NOVALNET_REDIRECT_BLOCK)
                        ->setOrder($order)
                        ->toHtml()
                );
            } else {
                $this->_redirect('checkout/cart');
            }
        } catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get Novalnet payment transaction response
     *
     * @param  none
     * @return none
     */
    public function returnAction()
    {
        $params = $this->getRequest()->getParams();
        $sameSiteFix = !empty($params['sess_lost']) ? $params['sess_lost'] : '';
        if (empty($sameSiteFix)) {
            header_remove('Set-Cookie');
            $params['sess_lost'] = 1;
            return $this->_redirectUrl(Mage::getUrl('novalnet_payment/gateway/return', array('_query' => http_build_query($params))));
        }
        $helper = $this->_getHelper(); // Get Novalnet payment helper
        $order = $this->_getOrder(); // Get order object
        $response = new Varien_Object();
        $response->setData($this->getRequest()->getParams()); // Get payment response data
        $this->_savePayportResponse($response, $order); // Save payment response traces
        $responseModel = $helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
        $status = $responseModel->checkReturnedData($response, $order);
        if ($status) {
            // Send order email for successful Novalnet transaction
            Mage::dispatchEvent('novalnet_sales_order_email', array('order' => $order));
        }

        $helper->getCheckoutSession()->getQuote()->setIsActive(false)->save();
        $this->_redirect(!$status ? 'checkout/onepage/failure' : 'checkout/onepage/success');
    }

    /**
     * Failure payment transaction
     *
     * @param  none
     * @return none
     */
    public function errorAction()
    {
        $params = $this->getRequest()->getParams();
        $sameSiteFix = !empty($params['sess_lost']) ? $params['sess_lost'] : '';
        if (empty($sameSiteFix)) {
            header_remove('Set-Cookie');
            $params['sess_lost'] = 1;
            return $this->_redirectUrl(Mage::getUrl('novalnet_payment/gateway/error', array('_query' => http_build_query($params))));
        }
        $helper = $this->_getHelper(); // Get Novalnet payment helper
        $order = $this->_getOrder(); // Get order object
        $response = new Varien_Object();
        $response->setData($this->getRequest()->getParams()); // Get payment response data
        $this->_savePayportResponse($response, $order); // Save payment response traces
        $responseModel = $helper->getModel('Service_Api_Response'); // Get Novalnet Api response model
        $responseModel->checkErrorReturnedData($response, $order); // Verify the payment response data
        
        // Restore the cart items 
        if (Mage::getStoreConfig('novalnet_global/novalnet/restore_cart', Mage::app()->getStore()->getStoreId())) {
            $order = $helper->getCheckoutSession()->getLastRealOrder();
            if ($order->getId()) {
                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(1)
                        ->setReservedOrderId(null)
                        ->save();
                    $helper->getCheckoutSession()
                        ->replaceQuote($quote)
                        ->unsLastRealOrderId();
                }
            }
        }

        $this->_redirect('checkout/onepage/failure', array('_secure' => true)); // Redirects to failure page
    }

    /**
     * Log Novalnet payment response data
     *
     * @param  Varien_Object $response
     * @param  Varien_Object $order
     * @return none
     */
    protected function _savePayportResponse($response, $order)
    {
        // Get Novalnet transaction traces model
        $transactionTraces = Mage::getModel('novalnet_payment/Mysql4_TransactionTraces')
            ->loadByAttribute('order_id', $response->getOrderNo());
        $transactionTraces->setTransactionId($response->getTid())
            ->setResponseData(base64_encode(serialize($response->getData())))
            ->setCustomerId($order->getCustomerId())
            ->setStatus($response->getStatus()) //transaction status code
            ->setStoreId($order->getStoreId())
            ->setShopUrl($response->getSystemUrl())
            ->save();
    }

    /**
     * Get last placed order object
     *
     * @param  none
     * @return Varien_Object
     */
    protected function _getOrder()
    {
        $incrementId = $this->_getHelper()->getCheckoutSession()->getLastRealOrderId();
        return Mage::getModel('sales/order')->loadByIncrementId($incrementId);
    }

    /**
     * Get Novalnet payment helper
     *
     * @param  none
     * @return Novalnet_Payment_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('novalnet_payment');
    }

}
