<div class="payment--form-group">
    <div id="MerchantId" ">MerchantId: CP_{$fatchipCTPaymentConfig.merchantID}</div>
    <div id="installments" data-amount="{$sAmount}">Amount:{$sAmount}
    </div>
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_iban_label"}
        <p style="margin-top: 1.25rem !important">
            <label for="fatchip_computop_afterpay_installment_iban">{s name='LastschriftIbanLabel'}IBAN{/s}</label>
        </p>
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_iban_input"}
            <input name="FatchipComputopPaymentData[fatchip_computop_afterpay_installment_iban]" type="text"
                   id="fatchip_computop_afterpay_installment_iban"
                   class="payment--field is--required{if $error_flags.fatchip_computop__afterpay_installment_iban} has--error{/if}"
                   placeholder="{s name='lastschriftIban'}IBAN{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                   value="{$FatchipCTPaymentData.afterpayinstallmentiban}"
            />
        <div id="feedback_bank"></div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_bic_label"}
        <p class="none">
            <label for="fatchip_computop_afterpay_installment_bic">{s name='AfterpayBICLabel'}BIC{/s}</label>
        </p>
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_bic_input"}
        <input name="FatchipComputopPaymentData[fatchip_computop_afterpay_installment_bic]" type="text"
               id="fatchip_computop_afterpay_installment_bic"
        />
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_productnr_label"}
        <p class="none">
            <label for="fatchip_computop_afterpay_installment_productnr">{s name='AfterpayProductNrLabel'}ProductNr{/s}</label>
        </p>
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_installment_productnr_input"}
        <input name="FatchipComputopPaymentData[fatchip_computop_afterpay_installment_productnr]" type="text"
               id="fatchip_computop_afterpay_installment_productnr"
        />
    {/block}
</div>

<script type="text/javascript">
    var AfterPayJS_Bank_Lookup_Config = {
        IBAN_field : 'fatchip_computop_afterpay_installment_iban',
        BIC_field : 'fatchip_computop_afterpay_installment_bic',
        feedback_field : 'feedback_bank',
        merchantID : 'CP_{$fatchipCTPaymentConfig.merchantID}',
        language : 'DE',
    };

    var AfterPayJS_PartPayment_Config = {
        installment_element : 'installments', //div in which the installment profiles should be shown
        feedback_field : 'fatchip_computop_afterpay_installment_productnr', //hidden element in which the installmentProfileNumber should be written
        merchantID : 'CP_{$fatchipCTPaymentConfig.merchantID}',
        country : 'DE',
        language : 'DE',
    };

{literal}
    !function(){var e=document.createElement("script");e.type="text/javascript",e.async=!0,e.src="https://cdn.afterpay.io/ressources/AfterPay.js",e.onload=function(){APJS_init_BankLookup(); APJS_PartPayment_init_InstallmentSelection()};var t=document.getElementsByTagName("script")[0];t.parentNode.insertBefore(e,t)}();
{/literal}
</script>

<script type="text/javascript">
    {if $FatchipCTPaymentData.afterpayinstallmentiban}
    var el = document.getElementById("fatchip_computop_afterpay_installment_iban");
    window.addEventListener('load', function(){
        APJS_BankLookup_checkIBANInput(el.value);
    },false);
    {/if}
</script>