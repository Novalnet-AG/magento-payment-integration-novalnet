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
var $nnInfo_j = jQuery.noConflict();
$nnInfo_j(document).ready(
    function () {
        $nnInfo_j('a[href*="novalnetpayment_information_merchantadmin/"], a[href*="novalnetpayment_information_module/"]').attr('target', '_blank');
    }
);
