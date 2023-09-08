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
 * If you wish to customize Novalnet payment extension for your needs, please contact technic@novalnet.de for more information.
 *
 * @category   Novalnet
 * @package    Novalnet_Payment
 * @copyright  Copyright (c) Novalnet AG
 * @license    https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
var $nnpaypal_j = jQuery.noConflict();

function paypalFormChange(type)
{
    if (type == 'given') {
        $nnpaypal_j('#paypal_ref_trans').val(1);
        $nnpaypal_j('#paypal_title_given, #paypal_title_new_description, #paypal_oneclick_new').css('display', 'none');
        $nnpaypal_j('#paypal_oneclick_given, #paypal_title_new, #paypal_title_given_description').css('display', 'block');
    } else if (type == 'new') {
        $nnpaypal_j('#paypal_ref_trans').val(0);
        $nnpaypal_j('#paypal_title_given, #paypal_title_new_description, #paypal_oneclick_new').css('display', 'block');
        $nnpaypal_j('#paypal_oneclick_given, #paypal_title_new, #paypal_title_given_description').css('display', 'none');
    }
}

function paypalOneClickShopping()
{
    if ($nnpaypal_j('#paypal_oneclick_shopping').val() == undefined) {
        return false;
    } else if ($nnpaypal_j('#paypal_oneclick_shopping').val() == 1) {
        $nnpaypal_j('#paypal_oneclick_link, #paypal_title_new, #paypal_oneclick_given, #paypal_title_given_description').css('display', 'block');
        $nnpaypal_j('#paypal_title_given, #paypal_title_new_description, #paypal_oneclick_new').css('display', 'none');
    } else {
        $nnpaypal_j('#paypal_ref_trans').val(0);
        $nnpaypal_j('#paypal_oneclick_link, #paypal_oneclick_given').css('display', 'none');
        $nnpaypal_j('#paypal_title_new_description, #paypal_oneclick_new').css('display', 'block');
    }
}

$nnpaypal_j(document).ready(
    function () {
        paypalOneClickShopping();

        Ajax.Responders.register(
            {onComplete: function () {
                    paypalOneClickShopping();
                }}
        );

        $nnpaypal_j('#opc-payment .step-title').click(
            function () {
                if ($nnpaypal_j('#paypal_oneclick_shopping').val() == 1) {
                    $nnpaypal_j('#paypal_ref_trans').val(1);
                }
            }
        );
    }
);
