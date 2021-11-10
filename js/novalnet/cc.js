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
var $nncc_j = jQuery.noConflict();
var nnButton, nnIfrmButton, iframeWindow, targetOrigin, onestepcheckout;
nnButton = nnIfrmButton = iframeWindow = targetOrigin = onestepcheckout = false;

function initIframe()
{
        var reqObj = JSON.parse($nncc_j('#nn_cc_req_obj').val()),
            clientKey = $nncc_j('#nn_client_key').val();
            if (clientKey == null || clientKey == undefined || clientKey == '') {
                return;
            }
            NovalnetUtility.setClientKey(clientKey);
        var request = {
                    callback: {
                        on_success: function (result) {
                            if (result) {
                                var isadmin = $nncc_j('#nn_is_admin').val();
                                if (isadmin && isadmin == 1 && result['do_redirect'] == 1) {
                                    alert('Card type not accepted, try using another card type');
                                    $nncc_j('button[onclick*="getHash()"]').attr('disabled',false);
                                } else {
                                    $nncc_j('button[onclick*="getHash()"]').attr('disabled',false);
                                    if($nncc_j('#nn_cc_save_card').val() == 1) {
                                        $nncc_j('#nn_pan_hash').val(result['hash']);
                                        $nncc_j('#nn_cc_uniqueid').val(result['unique_id']);
                                        $nncc_j('#form-change-payment-creditcard')[0].submit();
                                    } else {
                                        $nncc_j('#nn_pan_hash').val(result['hash']);
                                        $nncc_j('#nn_cc_uniqueid').val(result['unique_id']);
                                        $nncc_j('#nn_cc_do_redirect').val(result['do_redirect']);
                                        if (onestepcheckout == true) {
                                            $nncc_j.each(
                                                $nncc_j('#onestepcheckout-place-order'), function (i, j) {
                                                    $nncc_j(j).click();
                                                }
                                            );
                                        }

                                        eval($nncc_j('#nn_chk_button').val());
                                    }
                                }
                            }
                        },
                        on_error: function (result) {
                            $nncc_j('button[onclick*="getHash()"]').attr('disabled',false);
                            alert(result['error_message']);
                        },
                        on_show_overlay: function () {
                            $nncc_j('#novalnet_iframe').addClass("novalnet-challenge-window-overlay");
                        },
                        on_hide_overlay: function () {
                            $nncc_j('#novalnet_iframe').removeClass("novalnet-challenge-window-overlay");
                        },
                        on_show_captcha: function (result) {
                            $nncc_j('#' + this.getCode() + '_gethash').val(0);
                        },
                    },
                    iframe: {
                        id: "novalnet_iframe",
                        inline: $nncc_j('#nn_cc_layout').val(),
                        style: {
                            container: $nncc_j('#nn_cc_standard_style_css').val(),
                            input: $nncc_j('#nn_cc_standard_style_input').val(),
                            label: $nncc_j('#nn_cc_standard_style_label').val(),
                        },
                        text: {
                            cardHolder : {
                                label: $nncc_j('#nn_cc_holder_label').val(),
                                input: $nncc_j('#nn_cc_holder_field').val(),
                            },
                            cardNumber : {
                                label: $nncc_j('#nn_cc_number_label').val(),
                                input: $nncc_j('#nn_cc_number_field').val()
                            },
                            expiryDate : {
                                label: $nncc_j('#nn_cc_date_label').val(),
                                input: $nncc_j('#nn_cc_date_field').val()
                            },
                            cvc : {
                                label: $nncc_j('#nn_cc_cvc_label').val(),
                                input: $nncc_j('#nn_cc_cvc_field').val()
                            },
                            cvcHint : $nncc_j('#nn_cc_cvc_hint').val(),
                            error : $nncc_j('#nn_cc_validate_text').val()
                        }
                    },
                    customer: reqObj.customer,
                    transaction: reqObj.transaction
                };
        // Create the Credit Card form
        NovalnetUtility.createCreditCardForm(request);

    if ($nncc_j('#opc-payment').attr('class') == 'section allow active') {
        setButtonAttr();
    } else if ($nncc_j('#opc-payment').length == 0) {
        setButtonAttr();
    }
}

function setButtonAttr()
{
    if ($nncc_j('#cc_enter_data').val() == 1) {
        if ($nncc_j('#firecheckout-form button[onclick*="checkout.save()"]')[0]) {
            nnButton = $nncc_j('#firecheckout-form button[onclick*="checkout.save()"]');
        } else if ($nncc_j('#onestepcheckout-form')[0]) {
            nnButton = $nncc_j('#onestepcheckout-form button[onclick*="review.save()"]');
        } else if ($nncc_j('#edit_form')[0]) {
            nnButton = $nncc_j('button[onclick*="order.submit()"]');
        } else {
            nnButton = $nncc_j('button[onclick*="payment.save()"]');
        }

        if (nnButton !== undefined) {
            $nncc_j.each(
                nnButton, function (i, j) {
                    nnButtonContent = $nncc_j(j).attr('onclick');
                    $nncc_j('#nn_chk_button').val(nnButtonContent);
                    j.removeAttribute('onclick');
                    j.stopObserving('click');
                    $nncc_j(j).attr('onclick', 'getHash()');
                }
            );
        }
    }
}

function getHash()
{
    if ($nncc_j('input[name="payment[method]"]:checked').val() === 'novalnetCc'
            && $nncc_j('#cc_enter_data').val() == 1) {
        if ($nncc_j('#nn_pan_hash').val().trim() == '') {
            $nncc_j('button[onclick*="getHash()"]').attr('disabled',true);
            NovalnetUtility.getPanHash();
        }
    } else {
        eval($nncc_j('#nn_chk_button').val());
    }
}

function reSize()
{
    if ($nncc_j('#novalnet_iframe').length > 0) {
        clientKey = $nncc_j('#nn_client_key').val();
        if (clientKey == null || clientKey == undefined || clientKey == '') {
            return;
        }
        NovalnetUtility.setClientKey(clientKey);
        NovalnetUtility.setCreditCardFormHeight();
    }
}

function formChange(type)
{
    if (type == 'given') {
        $nncc_j('#cc_enter_data').val(0);
        $nncc_j('#cc_oneclick_new, #cc_title_given').css('display', 'none');
        $nncc_j('#cc_oneclick_given, #cc_title_new').css('display', 'block');
    } else if (type == 'new') {
        $nncc_j('#cc_enter_data').val(1);
        $nncc_j('#cc_oneclick_new, #cc_title_given').css('display', 'block');
        $nncc_j('#cc_oneclick_given, #cc_title_new').css('display', 'none');
    }

    setButtonAttr();
}

function ccOneClickShopping()
{
    if ($nncc_j('#cc_oneclick_shopping').val() == undefined) {
        return false;
    } else if ($nncc_j('#cc_oneclick_shopping').val() == 1) {
        $nncc_j('#cc_enter_data').val(0);
        $nncc_j('#cc_oneclick_link, #cc_title_new, #cc_oneclick_given').css('display', 'block');
        $nncc_j('#cc_oneclick_new, #cc_title_given').css('display', 'none');
    } else {
        $nncc_j('#cc_oneclick_link, #cc_oneclick_given').css('display', 'none');
    }
}

$nncc_j(document).ready(
    function () {
        ccOneClickShopping();
        $nncc_j('#onestepcheckout-place-order').on(
            'click', function (e) {
                if ($nncc_j('input[name="payment[method]"]:checked').val() === 'novalnetCc'
                    && $nncc_j('#cc_enter_data').val() == 1
                    && $nncc_j('#nn_pan_hash').val().trim() == '') {
                        e.stopImmediatePropagation();
                        onestepcheckout = true;
                        NovalnetUtility.getPanHash();
                }
            }
        );

        Ajax.Responders.register(
            {onComplete: function () {
                    ccOneClickShopping();
                }}
        );

        $nncc_j(document).on(
            'click', '#co-payment-form input[type="radio"]', function (event) {
                if (this.value == "novalnetCc") {
                    $nncc_j(this).addClass('active');
                    reSize();
                    ccOneClickShopping();
                }
            }
        );

        $nncc_j('#opc-payment .step-title').click(
            function () {
                $nncc_j('#nn_pan_hash').val('');
            }
        );

        $nncc_j('#opc-billing .step-title, #opc-shipping .step-title, #opc-shipping_method .step-title').click(
            function () {
                if ($nncc_j('#nn_chk_button').val() != '') {
                    $nncc_j('#nn_pan_hash').val('');
                    $nncc_j.each(
                        nnButton, function (i, j) {
                            $nncc_j(j).attr('onclick', $nncc_j('#nn_chk_button').val());
                        }
                    );
                }
            }
        );

        $nncc_j(window).resize(
            function () {
                reSize();
            }
        );
    }
);
