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
class Novalnet_Payment_Block_Adminhtml_Recurring_Profile_View extends Mage_Sales_Block_Adminhtml_Recurring_Profile_View
{

    /**
     * Prepare layout for recurring profile
     *
     * @param  none
     * @return Mage_Sales_Block_Adminhtml_Recurring_Profile_View
     */
    protected function _prepareLayout()
    {
        $profile = Mage::registry('current_recurring_profile');

        if (!preg_match("/novalnet/i", $profile->getMethodCode())) {
            return parent::_prepareLayout();
        }

        $this->_addButton(
            'back', array(
            'label' => Mage::helper('adminhtml')->__('Back'),
            'onclick' => "setLocation('{$this->getUrl('*/*/')}')",
            'class' => 'back'
            )
        );
        // Get transaction information
        $transactionStatus = $this->getTransactionStatus($profile);

        $comfirmationMessage = Mage::helper('sales')->__('Are you sure you want to do this?');

        // cancel
        if ($profile->canCancel() && $transactionStatus->getAmount()) {
            $url = $this->getUrl(
                '*/*/updateState', array('profile' => $profile->getId(),
                'action' => 'cancel')
            );
            $this->_addButton(
                'cancel', array(
                'label' => Mage::helper('sales')->__('Cancel'),
                'onclick' => "cancelButtonViewStatus('recurring_buttons_view','recurring_cancel_button_view')",
                'class' => 'delete',
                )
            );
        }

        // suspend
        $state = $profile->getState();
        if ($profile->canSuspend() && $state != 'pending' && $transactionStatus->getAmount()) {
            $url = $this->getUrl(
                '*/*/updateState', array('profile' => $profile->getId(),
                'action' => 'suspend')
            );
            $this->_addButton(
                'suspend', array(
                'label' => Mage::helper('sales')->__('Suspend'),
                'onclick' => "confirmSetLocation('{$comfirmationMessage}', '{$url}')",
                'class' => 'delete',
                )
            );
        }

        // activate
        if ($profile->canActivate() && $state != 'pending') {
            $url = $this->getUrl(
                '*/*/updateState', array('profile' => $profile->getId(),
                'action' => 'activate')
            );
            $this->_addButton(
                'activate', array(
                'label' => Mage::helper('sales')->__('Activate'),
                'onclick' => "confirmSetLocation('{$comfirmationMessage}', '{$url}')",
                'class' => 'add',
                )
            );
        }
    }

    /**
     * Set title and a hack for tabs container
     *
     * @param  none
     * @return Mage_Sales_Block_Adminhtml_Recurring_Profile_View
     */
    protected function _beforeToHtml()
    {
        $profile = Mage::registry('current_recurring_profile');
        $this->_headerText = Mage::helper('sales')->__('Recurring Profile # %s', $profile->getReferenceId());
        $this->setViewHtml('<div id="' . $this->getDestElementId() . '"></div>');
        return parent::_beforeToHtml();
    }

    /**
     * Get cancel reasons for recurring cancel
     *
     * @param  none
     * @return mixed
     */
    protected function _getCancelButtonWithReasons()
    {
        $profile = Mage::registry('current_recurring_profile');
        $comfirmationMessage = Mage::helper('sales')->__('Are you sure you want to do this?');
        $helper = Mage::helper('sales');
        $lang = array($helper->__("Please select reason"), $helper->__("Product is costly"), $helper->__("Cheating"),
            $helper->__("Partner interfered"), $helper->__("Financial problem"),
            $helper->__("Content does not match my likes"), $helper->__("Content is not enough"),
            $helper->__("Interested only for a trial"), $helper->__("Page is very slow"),
            $helper->__("Satisfied customer"), $helper->__("Logging in problems"), $helper->__("Other reasons"));
        $cancelview = "";
        if ($profile->canCancel()) {
            $cancelReason = $helper->__("Please select the reason of subscription cancellation");
            $select = Mage::app()->getLayout()->createBlock('core/html_select')
                ->setName("cancel_reason")
                ->setId("reason-unsubscribe")
                ->setOptions($lang);

            $cancelview .= $select->getHtml();

            $url = $this->getUrl(
                '*/*/updateState', array('profile' => $profile->getId(),
                'action' => 'cancel')
            );
            $this->setChild(
                'cancel', $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
                    array(
                        'label' => Mage::helper('sales')->__('Cancel'),
                        'onclick' => "subscriptionCancel('{$comfirmationMessage}', '{$url}', '{$cancelReason}')",
                        'class' => 'delete',
                    )
                )
            );
            $cancelview .= $this->getChildHtml('cancel');
        }

        return $cancelview;
    }

    /**
     * Get transaction information
     *
     * @param  Varien_Object $profile
     * @return Varien_Object
     */
    public function getTransactionStatus($profile)
    {
        $transactionId = $profile->getReferenceId();
        // load transaction status information
        $helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
        $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
            ->loadByAttribute('transaction_no', $transactionId);
        return $transactionStatus;
    }

}
