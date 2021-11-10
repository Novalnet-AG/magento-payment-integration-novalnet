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
class Novalnet_Payment_Block_Recurring_Profile_View extends Mage_Sales_Block_Recurring_Profile_View
{

    /**
     * Prepare recurring profile view
     *
     * @param  none
     * @return none
     */
    public function prepareViewData()
    {
        if (preg_match("/novalnet/i", $this->_profile->getMethodCode())) {
            $this->setTemplate('novalnet/recurring/profile/view.phtml');
        }

        $helper = Mage::helper('novalnet_payment');
        $lang = array($helper->__("Please select reason"), $helper->__("Product is costly"), $helper->__("Cheating"),
            $helper->__("Partner interfered"), $helper->__("Financial problem"),
            $helper->__("Content does not match my likes"), $helper->__("Content is not enough"),
            $helper->__("Interested only for a trial"), $helper->__("Page is very slow"),
            $helper->__("Satisfied customer"), $helper->__("Logging in problems"), $helper->__("Other reasons"));

        $select = Mage::app()->getLayout()->createBlock('core/html_select')
            ->setName("cancel_reason")
            ->setId("reason-unsubscribe")
            ->setOptions($lang);

        $this->addData(
            array(
                'reference_id' => $this->_profile->getReferenceId(),
                'can_cancel' => $this->_profile->canCancel(),
                'cancel_url' => $this->getUrl(
                    '*/*/updateState', array('profile' => $this->_profile->getId(),
                    'action' => 'cancel')
                ),
                'can_suspend' => $this->_profile->canSuspend(),
                'suspend_url' => $this->getUrl(
                    '*/*/updateState', array('profile' => $this->_profile->getId(),
                    'action' => 'suspend')
                ),
                'can_activate' => $this->_profile->canActivate(),
                'activate_url' => $this->getUrl(
                    '*/*/updateState', array('profile' => $this->_profile->getId(),
                    'action' => 'activate')
                ),
                'can_update' => $this->_profile->canFetchUpdate(),
                'update_url' => $this->getUrl('*/*/updateProfile', array('profile' => $this->_profile->getId())),
                'back_url' => $this->getUrl('*/*/'),
                'confirmation_message' => Mage::helper('sales')->__('Are you sure you want to do this?'),
                'cancel_reason' => $select->getHtml(),
            )
        );
    }

    /**
     * Prepare profile schedule info
     *
     * @param  none
     * @return none
     */
    public function prepareScheduleInfo()
    {
        $this->_shouldRenderInfo = true;
        $paymentCode = $this->_profile->getMethodCode();
        $recurringPayments = Novalnet_Payment_Model_Config::getInstance()->getNovalnetVariable('recurringPayments');

        $subscriptionFlag = 0;
        if (!in_array($paymentCode, $recurringPayments)) {
            $subscriptionFlag = 1;
            foreach (array('start_datetime', 'suspension_threshold') as $key) {
                $this->_addInfo(
                    array(
                        'label' => $this->_profile->getFieldLabel($key),
                        'value' => $this->_profile->renderData($key),
                    )
                );
            }
        }

        foreach ($this->_profile->exportScheduleInfo() as $i) {
            if (!$subscriptionFlag && $i->getTitle() != 'Trial Period') {
                $this->_addInfo(
                    array(
                        'label' => $i->getTitle(),
                        'value' => $i->getSchedule(),
                    )
                );
            } else if ($subscriptionFlag == 1) {
                $this->_addInfo(
                    array(
                        'label' => $i->getTitle(),
                        'value' => $i->getSchedule(),
                    )
                );
            }
        }
    }

    /**
     * Get transaction information
     *
     * @param  none
     * @return Varien_Object
     */
    public function getTransactionStatus()
    {
        $transactionId = $this->getReferenceId();
        // load transaction status information
        $helper = Mage::helper('novalnet_payment'); // Novalnet payment helper
        $transactionStatus = $helper->getModel('Mysql4_TransactionStatus')
            ->loadByAttribute('transaction_no', $transactionId);
        return $transactionStatus;
    }

}
