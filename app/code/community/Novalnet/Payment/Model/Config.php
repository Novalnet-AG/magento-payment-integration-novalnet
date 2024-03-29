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
class Novalnet_Payment_Model_Config
{
    /********************************************* */
    /*         NOVALNET GLOBAL PARAMS            */
    /******************************************** */

    static protected $_instance;
    protected $_paymentKey = array('novalnetCc' => 6, 'novalnetSepa' => 37, 'novalnetInvoice' => 27,
        'novalnetPrepayment' => 27, 'novalnetCashpayment' => 59, 'novalnetPaypal' => 34, 'novalnetBanktransfer' => 33,
        'novalnetIdeal' => 49, 'novalnetEps' => 50, 'novalnetGiropay' => 69, 'novalnetPrzelewy' => 78,
        'novalnetInvoiceInstalment' => 96, 'novalnetSepaInstalment' => 97);
    protected $_paymentMethods = array('novalnetCc' => 'Novalnet Credit Card',
        'novalnetSepa' => 'Novalnet Direct Debit SEPA', 'novalnetInvoice' => 'Novalnet Invoice',
        'novalnetPrepayment' => 'Novalnet Prepayment', 'novalnetCashpayment' => 'Novalnet Cashpayment',
        'novalnetPaypal' => 'Novalnet PayPal', 'novalnetBanktransfer' => 'Novalnet Instant Bank Transfer',
        'novalnetIdeal' => 'Novalnet iDEAL', 'novalnetEps' => 'Novalnet eps',
        'novalnetGiropay' => 'Novalnet giropay', 'novalnetPrzelewy' => 'Novalnet Przelewy24',
        'novalnetInvoiceInstalment' => 'Novalnet Guaranteed Invoice Instalment',
        'novalnetSepaInstalment'    => 'Novalnet Guaranteed Direct Debit SEPA Instalment');
    protected $_paymentTypes = array('novalnetCc' => 'CREDITCARD', 'novalnetSepa' => 'DIRECT_DEBIT_SEPA',
        'novalnetInvoice' => 'INVOICE', 'novalnetPrepayment' => 'PREPAYMENT', 'novalnetCashpayment' => 'CASHPAYMENT',
        'novalnetPaypal' => 'PAYPAL', 'novalnetBanktransfer' => 'ONLINE_TRANSFER', 'novalnetIdeal' => 'IDEAL',
        'novalnetEps' => 'EPS', 'novalnetGiropay' => 'GIROPAY', 'novalnetPrzelewy' => 'PRZELEWY24',
        'novalnetInvoiceInstalment' => 'INSTALMENT_INVOICE', 'novalnetSepaInstalment' => 'INSTALMENT_DIRECT_DEBIT_SEPA');
    protected $_redirectPayportUrl = array('novalnetPaypal' => 'paypal_payport',
        'novalnetBanktransfer' => 'online_transfer_payport', 'novalnetIdeal' => 'online_transfer_payport',
        'novalnetEps' => 'giropay', 'novalnetGiropay' => 'giropay', 'novalnetCc' => 'pci_payport',
        'novalnetPrzelewy' => 'globalbank_transfer');
    protected $_redirectPayments = array('novalnetPaypal', 'novalnetBanktransfer', 'novalnetIdeal', 'novalnetEps',
        'novalnetGiropay', 'novalnetPrzelewy');
    protected $_recurringPayments = array('novalnetCc', 'novalnetSepa', 'novalnetInvoice', 'novalnetPrepayment',
        'novalnetPaypal');
    protected $_hashParams = array('auth_code', 'product', 'tariff', 'amount', 'test_mode');
    protected $_onHoldPayments = array('novalnetCc', 'novalnetSepa', 'novalnetInvoice', 'novalnetPaypal', 'novalnetInvoiceInstalment', 'novalnetSepaInstalment');
    protected $_allowedCountry = array('AT', 'DE', 'CH');

    const RESPONSE_CODE_APPROVED = '100';
    const CC_ONHOLD_STATUS = '98';
    const PAYPAL_PENDING_STATUS = '90';
    const PAYPAL_ONHOLD_STATUS = '85';
    const PRZELEWY_PENDING_STATUS = '86';
    const GUARANTEE_PENDING_STATUS = '75';
    const PAYMENT_VOID_STATUS = '103';
    const METHOD_DISABLE_CODE = '0529006';
    const MAXPIN_DISABLE_CODE = '0529008';
    const PIN_STATUS = 'PIN_STATUS';
    const TRANSMIT_PIN_AGAIN = 'TRANSMIT_PIN_AGAIN';
    const NOVALNET_RETURN_METHOD = 'POST';
    const NOVALNET_REDIRECT_BLOCK = 'novalnet_payment/method_redirect';
    const GATEWAY_REDIRECT_URL = 'novalnet_payment/gateway/redirect';
    const GATEWAY_RETURN_URL = 'novalnet_payment/gateway/return';
    const GATEWAY_ERROR_RETURN_URL = 'novalnet_payment/gateway/error';
    const PAYPORT_URL = 'paygate.jsp';
    const INFO_REQUEST_URL = 'nn_infoport.xml';
    const SUBS_PAUSE = 'SUBSCRIPTION_PAUSE';
    const PREPAYMENT_PAYMENT_TYPE = 'Prepayment';
    const INVOICE_PAYMENT_TYPE = 'Invoice';
    const INVOICE_PAYMENT_GUARANTEE_TYPE = 'GUARANTEED_INVOICE';
    const INVOICE_PAYMENT_GUARANTEE_KEY = '41';
    const SEPA_PAYMENT_GUARANTEE_TYPE = 'GUARANTEED_DIRECT_DEBIT_SEPA';
    const SEPA_PAYMENT_GUARANTEE_KEY = '40';

    /**********************************************/
    /*         NOVALNET CREDIT CARD PARAMS        */
    /**********************************************/
    const NN_CC = 'novalnetCc';
    const NN_CC_CAN_AUTHORIZE = true;
    const NN_CC_FORM_BLOCK = 'novalnet_payment/method_form_Cc';
    const NN_CC_INFO_BLOCK = 'novalnet_payment/method_info_Cc';

    /**********************************************/
    /*      NOVALNET DIRECT DEBIT SEPA PARAMS     */
    /**********************************************/
    const NN_SEPA = 'novalnetSepa';
    const NN_SEPA_CAN_AUTHORIZE = true;
    const NN_SEPA_FORM_BLOCK = 'novalnet_payment/method_form_Sepa';
    const NN_SEPA_INFO_BLOCK = 'novalnet_payment/method_info_Sepa';

    /**********************************************/
    /*         NOVALNET INVOICE PARAMS            */
    /**********************************************/
    const NN_INVOICE = 'novalnetInvoice';
    const NN_INVOICE_CAN_AUTHORIZE = true;
    const NN_INVOICE_FORM_BLOCK = 'novalnet_payment/method_form_Invoice';
    const NN_INVOICE_INFO_BLOCK = 'novalnet_payment/method_info_Invoice';

    /**********************************************/
    /*         NOVALNET PREPAYMENT PARAMS         */
    /**********************************************/
    const NN_PREPAYMENT = 'novalnetPrepayment';
    const NN_PREPAYMENT_CAN_AUTHORIZE = true;
    const NN_PREPAYMENT_FORM_BLOCK = 'novalnet_payment/method_form_Prepayment';
    const NN_PREPAYMENT_INFO_BLOCK = 'novalnet_payment/method_info_Prepayment';

    /**********************************************/
    /*         NOVALNET CASHPAYMENT PARAMS        */
    /**********************************************/
    const NN_CASHPAYMENT = 'novalnetCashpayment';
    const NN_CASHPAYMENT_CAN_AUTHORIZE = true;
    const NN_CASHPAYMENT_FORM_BLOCK = 'novalnet_payment/method_form_Cashpayment';
    const NN_CASHPAYMENT_INFO_BLOCK = 'novalnet_payment/method_info_Cashpayment';

    /**********************************************/
    /*        NOVALNET PAYPAL PARAMS              */
    /**********************************************/
    const NN_PAYPAL = 'novalnetPaypal';
    const NN_PAYPAL_CAN_AUTHORIZE = true;
    const NN_PAYPAL_CAN_USE_INTERNAL = false;
    const NN_PAYPAL_FORM_BLOCK = 'novalnet_payment/method_form_Paypal';
    const NN_PAYPAL_INFO_BLOCK = 'novalnet_payment/method_info_Paypal';

    /**********************************************/
    /*   NOVALNET ONLINE BANK TRANSFER PARAMS     */
    /**********************************************/
    const NN_BANKTRANSFER = 'novalnetBanktransfer';
    const NN_BANKTRANSFER_CAN_USE_INTERNAL = false;
    const NN_BANKTRANSFER_FORM_BLOCK = 'novalnet_payment/method_form_Banktransfer';
    const NN_BANKTRANSFER_INFO_BLOCK = 'novalnet_payment/method_info_Banktransfer';

    /**********************************************/
    /*        NOVALNET IDEAL PARAMS               */
    /**********************************************/
    const NN_IDEAL = 'novalnetIdeal';
    const NN_IDEAL_CAN_USE_INTERNAL = false;
    const NN_IDEAL_FORM_BLOCK = 'novalnet_payment/method_form_Ideal';
    const NN_IDEAL_INFO_BLOCK = 'novalnet_payment/method_info_Ideal';

    /**********************************************/
    /*        NOVALNET EPS PARAMS                 */
    /**********************************************/
    const NN_EPS = 'novalnetEps';
    const NN_EPS_CAN_USE_INTERNAL = false;
    const NN_EPS_FORM_BLOCK = 'novalnet_payment/method_form_Eps';
    const NN_EPS_INFO_BLOCK = 'novalnet_payment/method_info_Eps';

    /**********************************************/
    /*        NOVALNET GIROPAY PARAMS             */
    /**********************************************/
    const NN_GIROPAY = 'novalnetGiropay';
    const NN_GIROPAY_CAN_USE_INTERNAL = false;
    const NN_GIROPAY_FORM_BLOCK = 'novalnet_payment/method_form_Giropay';
    const NN_GIROPAY_INFO_BLOCK = 'novalnet_payment/method_info_Giropay';

    /**********************************************/
    /*        NOVALNET PRZELEWY24 PARAMS          */
    /**********************************************/
    const NN_PRZELEWY = 'novalnetPrzelewy';
    const NN_PRZELEWY_CAN_USE_INTERNAL = false;
    const NN_PRZELEWY_FORM_BLOCK = 'novalnet_payment/method_form_Przelewy';
    const NN_PRZELEWY_INFO_BLOCK = 'novalnet_payment/method_info_Przelewy';

    /**********************************************/
    /*         NOVALNET INVOICE INSTALMENT PARAMS            */
    /**********************************************/
    const NN_INVOICE_INSTALMENT = 'novalnetInvoiceInstalment';
    const NN_INVOICE_INSTALMENT_CAN_AUTHORIZE = true;
    const NN_INVOICE_INSTALMENT_FORM_BLOCK = 'novalnet_payment/method_form_InvoiceInstalment';
    const NN_INVOICE_INSTALMENT_INFO_BLOCK = 'novalnet_payment/method_info_InvoiceInstalment';

    /**********************************************/
    /*      NOVALNET DIRECT DEBIT SEPA INSTALMENT PARAMS     */
    /**********************************************/
    const NN_SEPA_INSTALMENT = 'novalnetSepaInstalment';
    const NN_SEPA_INSTALMENT_CAN_AUTHORIZE = true;
    const NN_SEPA_INSTALMENT_FORM_BLOCK = 'novalnet_payment/method_form_SepaInstalment';
    const NN_SEPA_INSTALMENT_INFO_BLOCK = 'novalnet_payment/method_info_SepaInstalment';

    /**********************************************/
    /*         NOVALNET ABSTRACT FUNCTIONS        */
    /**********************************************/

    static public function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    public function getNovalnetVariable($key)
    {
        return $this->{'_' . $key};
    }
}
