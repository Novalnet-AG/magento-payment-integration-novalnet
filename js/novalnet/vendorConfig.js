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
var $nnConfig_j = jQuery.noConflict();

function checkVendorConfig(state)
{
    var publicKey = $nnConfig_j('#novalnet_global_novalnet_public_key').val();
    var vendorScriptUrl = $nnConfig_j('#nn_vendorscript_url').val();
    var apiKeyUrl = $nnConfig_j('#apiKeyUrl').val();

    if (vendorScriptUrl != '') {
        $nnConfig_j('#novalnet_global_merchant_script_vendor_script_url').val(vendorScriptUrl);
    }

    if (publicKey == '' || publicKey == undefined) {
        return false;
    }

    var request = {"hash": publicKey};
    new Ajax.Request(apiKeyUrl, {
        parameters : request,
        onSuccess: function(response) {
            var response = jQuery.parseJSON(response.responseText);
            getConfigResultValues(response,state);
        }
    });
}

function getConfigResultValues(response,state)
{
    if (response.status == '100') {
        var savedTariff = $nnConfig_j('#nn_tariff_id').val();
        var savedSubsTariff = $nnConfig_j('#nn_subsTariff_id').val();
        var tariff = response.tariff;
        if (state == 'change'){
            $nnConfig_j('#novalnet_global_novalnet_merchant_id').val(response.vendor);
            $nnConfig_j('#novalnet_global_novalnet_auth_code').val(response.auth_code);
            $nnConfig_j('#novalnet_global_novalnet_product_id').val(response.product);
            $nnConfig_j('#novalnet_global_novalnet_password').val(response.access_key);
            $nnConfig_j('#novalnet_global_novalnet_client_key').val(response.client_key);
            alert(Translator.translate('Your Novalnet Merchant details are now updated. Click the')+' "'+Translator.translate('Save Config')+'"'+Translator.translate('button to complete the configuration & save all changes!'));
        }
        $nnConfig_j("#novalnet_global_novalnet_tariff_id option").remove();
        $nnConfig_j("#novalnet_global_novalnet_subscrib_tariff_id option").remove();

        $nnConfig_j.each(tariff, function (index, value) {
            var tariffId = index;
            var tariffName = value.name;
            var tariffType = value.type;

            if (tariffType != 4) {
                $nnConfig_j('#novalnet_global_novalnet_tariff_id').append(
                    $nnConfig_j(
                        '<option>', {
                            value: tariffId,
                            text: tariffName
                        }
                    )
                );

                if (savedTariff != undefined && savedTariff == tariffId) {
                    $nnConfig_j('#novalnet_global_novalnet_tariff_id option[value=' + tariffId + ']').attr("selected", "selected");
                }
            }

            if (tariffType == 1 || tariffType == 4) {
                $nnConfig_j('#novalnet_global_novalnet_subscrib_tariff_id').append(
                    $nnConfig_j(
                        '<option>', {
                            value: tariffId,
                            text: tariffName
                        }
                    )
                );

                if (savedSubsTariff != undefined && savedSubsTariff == tariffId) {
                    $nnConfig_j('#novalnet_global_novalnet_subscrib_tariff_id option[value=' + tariffId + ']').attr("selected", "selected");
                }
            }
        });
    } else {
        if (response.status == '106') {
            alert($nnConfig_j('#ipErrorOne').val() + response.ip + $nnConfig_j('#ipErrorTwo').val());
            $nnConfig_j('#novalnet_global_novalnet_public_key').val('');
            return false;
        } else {
            alert(response.config_result);
            $nnConfig_j('#novalnet_global_novalnet_public_key').val('');
            return false;
        }
    }
}

function checkCcOrderStatus()
{
    var cc3dEnable = $nnConfig_j("#novalnet_payments_novalnetCc_enable_cc3d option:selected").val();
    var cc3dValidate = $nnConfig_j("#novalnet_payments_novalnetCc_cc3d_validation option:selected").val();
    if ((cc3dEnable == 0 && cc3dValidate == 0)
       ) {
        $nnConfig_j('#row_novalnet_payments_novalnetCc_order_status_before_payment').hide();
        $nnConfig_j('#row_novalnet_payments_novalnetCc_order_status_after_payment').hide();
    } else {
        $nnConfig_j('#row_novalnet_payments_novalnetCc_order_status_before_payment').show();
        $nnConfig_j('#row_novalnet_payments_novalnetCc_order_status_after_payment').show();
    }
}

$nnConfig_j(document).ready(
    function () {
        checkVendorConfig();
        checkCcOrderStatus();
        $nnConfig_j('#novalnet_global_novalnet_public_key').keyup(function(){
            $nnConfig_j('#novalnet_global_novalnet_merchant_id').val('');
            $nnConfig_j('#novalnet_global_novalnet_client_key').val('');
            $nnConfig_j('#novalnet_global_novalnet_auth_code').val('');
            $nnConfig_j('#novalnet_global_novalnet_product_id').val('');
            $nnConfig_j('#novalnet_global_novalnet_password').val('');
            $nnConfig_j('#novalnet_global_novalnet_subscrib_tariff_id').html('');
            $nnConfig_j('#novalnet_global_novalnet_tariff_id').html('');
            if ($nnConfig_j(this).val() == '') {
                $nnConfig_j('#nn-save-vendor-config').attr('disabled','disabled');
            } else {
                $nnConfig_j('#nn-save-vendor-config').removeAttr('disabled');
            }
        });

        $nnConfig_j('#nn-save-vendor-config').click(
            function () {
                checkVendorConfig('change');
            }
        );

        $nnConfig_j("#novalnet_payments_novalnetCc_enable_cc3d, #novalnet_payments_novalnetCc_cc3d_validation").on(
            'change', function () {
                checkCcOrderStatus();
            }
        );

    }
);

Validation.addAllThese(
    [
        ['validate-novalnet-minimum-amount', 'The minimum amount should be at least 9,99 EUR', function (v) {
            return validateMinimumAmount(v);
        }],
        ['validate-novalnet-minimum-instalment-amount', 'The minimum amount should be at least 19.98 EUR', function (v) {
            return validateMinimumInstalmentAmount(v);
        }],
        ['validate-novalnet-subs-period', 'The period of the subsequent subscription cycle (E.g: 1d/1m/1y)', function (v) {
                if (Validation.get('IsEmpty').test(v)) {
                    return true;
                }

                return (/^[1-9]\d*[d|m|y]{1}$/.test(v));
        }]
    ]
);

function validateMinimumAmount(v)
{
    if (Validation.get('IsEmpty').test(v) || (parseNumber(v) && v >= 999)) {
        return true;
    }

    return false;
}

function validateMinimumInstalmentAmount(v)
{
    if (Validation.get('IsEmpty').test(v) || (parseNumber(v) && v >= 19.98)) {
        return true;
    }

    return false;
}
