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
class Novalnet_Payment_Block_Adminhtml_Sales_Order_Render_Delete extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Render the delete button
     *
     * @param  Varien_Object $row
     * @return mixed $result
     */
    public function render(Varien_Object $row)
    {
        $info = $row->getData();
        $orderId = $info['entity_id'];
        $message = Mage::helper('sales')->__('Are you sure you want to delete this order?');
        $viewLink = $this->getUrl('adminhtml/sales_order/view', array('order_id' => $orderId));
        $deleteLink = $this->getUrl(
            'adminhtml/novalnetpayment_sales_deleteorder/delete', array('order_id' => $orderId)
        );
        $result = '<a href="' . $viewLink . '">View</a>';
        $result .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $result .= '<a href="#" onclick="deleteConfirm(\'' . $message . '\', \'' . $deleteLink . '\')">Delete</a>';
        return $result;
    }

}
