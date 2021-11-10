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
var $nnsepa_j = jQuery.noConflict();

function sepaFormValidate(event)
{
    var keycode = ('which' in event) ? event.which : event.keyCode;
    var reg = /^(?:[A-Za-z0-9]+$)/;
    if (event.target.id == 'novalnetSepa_account_holder') {
        var reg = /[^0-9\[\]\/\\#,+@!^()$~%'"=:;<>{}\_\|*?`]/g;
    }

    return (reg.test(String.fromCharCode(keycode)) || keycode == 0 || keycode == 8 || (event.ctrlKey == true && keycode == 114));
}

function SepaFormChange(type)
{
    if (type == 'given') {
        $nnsepa_j('#nnSepa_new_form').val(0);
        $nnsepa_j('#sepa_local_form, #sepa_title_given').css('display', 'none');
        $nnsepa_j('#sepa_oneclick_given, #sepa_title_new').css('display', 'block');
    } else if (type == 'new') {
        $nnsepa_j('#nnSepa_new_form').val(1);
        $nnsepa_j('#sepa_oneclick_given, #sepa_title_new').css('display', 'none');
        $nnsepa_j('#sepa_local_form, #sepa_title_given').css('display', 'block');
    }
}

function sepaOneClickShopping()
{
    if ($nnsepa_j('#nnSepa_oneclick_shopping').val() == undefined
            || $nnsepa_j('#nnSepa_new_form').val() == undefined
            ) {
        return false;
    } else if ($nnsepa_j('#nnSepa_new_form').val() == 1) {
        if ($nnsepa_j('#nnSepa_oneclick_shopping').val() == 1) {
            $nnsepa_j('#sepa_title_given').css('display', 'block');
            $nnsepa_j('#sepa_title_new').css('display', 'none');
            $nnsepa_j('#sepa_oneclick_link').css('display', 'block');
        }

        $nnsepa_j('#sepa_oneclick_given').css('display', 'none');
        $nnsepa_j('#nnSepa_oneclick_new').css('display', 'block');
    } else if ($nnsepa_j('#nnSepa_oneclick_shopping').val() == 1) {
        $nnsepa_j('#sepa_local_form, #sepa_title_given').css('display', 'none');
        $nnsepa_j('#sepa_oneclick_link, #sepa_title_new, #sepa_oneclick_given').css('display', 'block');
    } else {
        $nnsepa_j('#sepa_oneclick_link, #sepa_oneclick_given').css('display', 'none');
        $nnsepa_j('#sepa_local_form').css('display', 'block');
    }
}

$nnsepa_j(document).ready(
    function () {
        sepaOneClickShopping();
        Ajax.Responders.register(
            {onComplete: function () {
                    sepaOneClickShopping();
                }
            }
        );

        $nnsepa_j(document).on(
            'click', '#co-payment-form input[type="radio"]', function (event) {
                if (this.value == "novalnetSepa") {
                    $nnsepa_j(this).addClass('active');
                    sepaOneClickShopping();
                } else if (this.value == 'novalnetInvoiceInstalment') {
                    $nnsepa_j( "#novalnetInvoiceInstalment_instalment_period" ).trigger('change');
                } else if (this.value == 'novalnetSepaInstalment') {
                    $nnsepa_j( "#novalnetSepaInstalment_instalment_period" ).trigger('change');
                }
            }
        );

        $nnsepa_j(document).on(
            'click', '#sepa_mandate_toggle', function (event) {
                $nnsepa_j('#sepa_mandate_details').toggle();
        });

        $nnsepa_j(document).on(
            'click', '#sepa_instalment_mandate_toggle', function (event) {
                    $nnsepa_j('#sepa_instalment_mandate_details').toggle();
        });

        $nnsepa_j(document).on('keyup change input paste', '.validate-novalnet-date',function(e){
            var $this = $nnsepa_j(this);
            var val = $this.val();
            var valLength = val.length;
            var maxCount = 2;
            if (valLength == 1 || valLength == 2) {
                $nnsepa_j(this).val(checkValue($nnsepa_j(this).val(),31));
            }
            if(valLength>maxCount){
                $this.val($this.val().substring(0,maxCount));
                $nnsepa_j('.validate-novalnet-month').focus();
            }
        });
        $nnsepa_j(document).on('keyup change input paste', '.validate-novalnet-month',function(e){
            var $this = $nnsepa_j(this);
            var val = $this.val();
            var valLength = val.length;
            var maxCount = 2;
            if (valLength == 1 || valLength == 2) {
                $nnsepa_j(this).val(checkValue($nnsepa_j(this).val(),12));
            }
            if (valLength>maxCount) {
                $this.val($this.val().substring(0,maxCount));
                $nnsepa_j('.validate-novalnet-year').focus();
            }
        });
        
        $nnsepa_j(document).on('keyup change input paste', '.validate-novalnet-month',function(e){
            var $this = $nnsepa_j(this);
            var val = $this.val();
            var valLength = val.length;
            var maxCount = 2;
            if (valLength == 1 || valLength == 2) {
                $nnsepa_j(this).val(checkValue($nnsepa_j(this).val(),12));
            }
            if (valLength>maxCount) {
                $this.val($this.val().substring(0,maxCount));
                $nnsepa_j('.validate-novalnet-year').focus();
            }
        });
        $nnsepa_j(document).on('input', '.validate-novalnet-year',function(e){
            year_validation(e, $nnsepa_j(this));
        });
        
        $nnsepa_j(document).on('change', '#novalnetInvoiceInstalment_instalment_period, #novalnetSepaInstalment_instalment_period', function (e) {
            var payment = $nnsepa_j('input[name="payment[method]"]:checked').val();
            var orderTotal = parseFloat($nnsepa_j('#'+payment+'-order-total').val()).toFixed(2);
            var cycle = parseFloat($nnsepa_j(this).val()).toFixed(2);
            var cycleAmount = parseFloat( orderTotal/cycle ).toFixed(2);
            var lastCycleAmount = parseFloat(orderTotal - (cycleAmount * (cycle - 1))).toFixed(2);
            var instalmentPeriod = $nnsepa_j('#' +payment + '_InstalmentPeriod').val();
            var istalmentDate = {};
            var priceFormat = $nnsepa_j.parseJSON($nnsepa_j('#' +payment + '_priceFormat').val());
            var html = '<table class="data-table"><thead><tr><th>'+Translator.translate('Instalment number')+'</th><th>'+Translator.translate('Monthly instalment amount')+'</th></thead><tbody>';
            var j =0;
            var number_text;
            var lang = $nnsepa_j('#' +payment+ '_current_lang').val();
            for (var i = 1; i <= cycle; i++) {
                if (lang == 'en_US') {
                    number_text = getNumberWithOrdinal(i) +' Instalment';
                } else {
                    number_text = i +'. Instalment';
                }
                if ( i != cycle) {
                    html += '<tr><td>'+ number_text +'</td><td>'+formatCurrency(cycleAmount, priceFormat)+'</td></tr>';
                }else if( i == cycle) {
                    html +='<tr><td>'+ number_text +'</td><td>'+formatCurrency(lastCycleAmount, priceFormat)+'</td></tr></tbody></table>';
                }
                j++;
            }
            $nnsepa_j('.'+payment+'-instalment-details-table').html(html);
        });
    }
);



//For DOB autofill date&month validation
function checkValue(str, max) {
    if (str.charAt(0) !== '0') {
        var num = parseInt(str);
        if (isNaN(num) || num <= 0 || num > max) num = 1;
            str = num > parseInt(max.toString().charAt(0)) 
              && num.toString().length == 1 ? '0' + num : ((max == 12) && (parseInt(str) > max)) ? '12' : ((max == 31) && (parseInt(str) > max)) ? '31' : num.toString();
    } else if (str == '00') {
        str = '01';
    }
    return str;
};

function getNumberWithOrdinal(n) {
    var s=["th","st","nd","rd"],
    v=n%100;
    return n+(s[(v-20)%10]||s[v]||s[0]);
}



function autoCompleteYear(input_val) {
    var current_date = new Date();
    var max_year = current_date.getFullYear() - 18;
    var min_year = current_date.getFullYear() - 91;
    var year_range = [];
    for( var year = max_year; year >= min_year; year-- ) {
        year_range.push('' + year + '');
    }
    input_val.addEventListener("input", function ( e ) {
        var a, b, i, val = this.value;
        if (!val || val.length < 2) {
            return false;
        }
    closeAllitems(input_val);
    currentFocus = -1;
    a = document.createElement( "datalist" );
    a.setAttribute( "id", "years" );
    a.setAttribute( "class", "autocomplete-items" );
    this.parentNode.appendChild(a);
    var count = 1;
    for ( i = 0; i < year_range.length; i++ ) {     
        var regex = new RegExp( val, 'g' );
        if ( year_range[i].match( regex ) ) {   
            if ( count == 11 ) {
                break;
            }
            b = document.createElement( "div" );
            b.innerHTML = year_range[i].replace( val,"<strong>" + val + "</strong>" );
            b.innerHTML += "<input type='hidden' class='year_active' value='" + year_range[i] + "'>";
            b.addEventListener( "click", function ( e ) {
                input_val.value = this.getElementsByTagName( "input" )[0].value;
                closeAllitems(input_val);
            });
            a.appendChild(b);
            count++;
        }
    }
});
}


function closeAllitems(input_val,elmnt) {
    var x = document.getElementsByClassName( "autocomplete-items" );
        for ( var i = 0; i < x.length; i++ ) {
        if ( elmnt != x[i] && elmnt != input_val ) {
            x[i].parentNode.removeChild( x[i] );
        }
    }
}

function year_validation(e, payment) {
    var current_date = new Date();
    var max_year = current_date.getFullYear() - 18;
    var min_year = current_date.getFullYear() - 91;
    var year_val = payment.val();
    var year_len = year_val.length;
    let maximum_year = parseInt( max_year.toString().substring( 0 ,year_len ) );
    let minimum_year = parseInt( min_year.toString().substring( 0 ,year_len ) );
    let user_val = year_val.substring( 0, year_len );               
    if( e.keyCode != 8 || e.keyCode != 46 ) {        
        if( user_val > maximum_year || user_val <  minimum_year || isNaN( user_val ) )  {
            payment.val( year_val.substring( 0, year_len - 1 ) );
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
      }  
    }
}
