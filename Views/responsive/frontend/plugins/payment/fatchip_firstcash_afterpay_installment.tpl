<div class="payment--form-group">
    <!-- <div id="MerchantId" ">MerchantId: CP_{$fatchipFCSPaymentConfig.merchantID}</div> -->
    <div id="installments" data-amount="{$sAmount}"></div>

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_iban_label"}
        <div>
            <p style="margin-top: 1.25rem !important">
                <label for="fatchip_firstcash_afterpay_installment_iban">IBAN</label>
            </p>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_iban_input"}
        <input name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_iban]" type="text"
               id="fatchip_firstcash_afterpay_installment_iban"
               class="payment--field is--required{if $error_flags.fatchip_firstcash__afterpay_installment_iban} has--error{/if}"
               placeholder="IBAN{s name="RequiredField" namespace="frontend/register/index"}{/s}"
               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
               value="{$FatchipFCSPaymentData.afterpayinstallmentiban}"
        />
        <div id="feedback_bank"></div>
    {/block}

    {*
        {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_bic_label"}
            <p class="none">
                <label for="fatchip_firstcash_afterpay_installment_bic">{s name='AfterpayBICLabel'}BIC{/s}</label>
            </p>
        {/block}
    *}
    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_bic_input"}
        <input name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_bic]" type="hidden"
               id="fatchip_firstcash_afterpay_installment_bic"
        />
    {/block}
    {*    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_productnr_label"}
            <p class="none">
                <label for="fatchip_firstcash_afterpay_installment_productnr">{s name='AfterpayProductNrLabel'}ProductNr{/s}</label>
            </p>
        {/block}
    *}
    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_productnr_input"}
        <input name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_productnr]" type="hidden"
               id="fatchip_firstcash_afterpay_installment_productnr"
        />
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_birthday_label"}
        <div>
            <p class="none">
                <label for="fatchip_firstcash_afterpay_installment_birthday">{s name='birthdate' namespace='frontend/FatchipFCSPayment/translations'}Bitte geben Sie Ihr Geburtsdatum an{/s}:</label>
            </p>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_birthday_day_input"}
        <div class="select-field">
            <select name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_birthday]"
                    id="fatchip_firstcash_afterpay_installment_birthday"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">--</option>
                {section name="birthdate" start=1 loop=32 step=1}
                    {$isSelected = $smarty.section.birthdate.index == $FatchipFCSPaymentData.birthday}
                    <option value="{$smarty.section.birthdate.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthdate.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_birthday_month_input"}
        <div class="select-field">
            <select name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_birthmonth]"
                    id="fatchip_firstcash_afterpay_installment_birthmonth"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">-</option>
                {section name="birthmonth" start=1 loop=13 step=1}
                    {$isSelected = $smarty.section.birthmonth.index == $FatchipFCSPaymentData.birthmonth}
                    <option value="{$smarty.section.birthmonth.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthmonth.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_installment_birthday_year_input"}
        <div class="select-field">
            <select name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_birthyear]"
                    id="fatchip_firstcash_afterpay_installment_birthyear"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">----</option>
                {assign var=thisyear value=$smarty.now|date_format:"%Y"}
                {section name="birthyear" loop=$thisyear-17 max=100 step=-1}
                    {$isSelected = $smarty.section.birthyear.index == $FatchipFCSPaymentData.birthyear}
                    <option value="{$smarty.section.birthyear.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthyear.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {if $FatchipFCSPaymentData.showsocialsecuritynumber}
        {block name="frontend_checkout_payment_fatchip_firstcash_klarna_installment_socialsecuritynumber_label"}
            <div>
                <p class="none">
                    <label for="fatchip_firstcash_afterpay_installment_socialsecuritynumber">{$FatchipFCSPaymentData.SSNLabel}</label>
                </p>
            </div>
        {/block}

        {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_socialsecuritynumber_input"}
            <div>
                <input name="FatchipFirstCashPaymentData[fatchip_firstcash_afterpay_installment_socialsecuritynumber]"
                       type="text"
                       id="fatchip_firstcash_afterpay_installment_socialsecuritynumber"
                       {if $FatchipFCSPaymentData.SSNMaxLen}maxlength={$FatchipFCSPaymentData.SSNMaxLen}{/if}
                       class="payment--field
                       is--required{if $error_flags.fatchip_firstcash__afterpay_socialsecuritynumber} has--error{/if}"
                placeholder="{$FatchipFCSPaymentData.SSNLabel}{s name="RequiredField" namespace="frontend/register/index"}{/s}
                "
                {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                value="{$FatchipFCSPaymentData.socialsecuritynumber}"
                />
            </div>
        {/block}
    {/if}

</div>
{if $payment_mean.id == $form_data.payment}
    <script type="text/javascript">
        var AfterPayJS_Bank_Lookup_Config = {
            IBAN_field: 'fatchip_firstcash_afterpay_installment_iban',
            BIC_field: 'fatchip_firstcash_afterpay_installment_bic',
            feedback_field: 'feedback_bank',
            merchantID: 'CP_{$fatchipFCSPaymentConfig.merchantID}',
            //merchantID : '4564',
            language: 'DE',
        };

        var AfterPayJS_PartPayment_Config = {
            installment_element: 'installments', //div in which the installment profiles should be shown
            feedback_field: 'fatchip_firstcash_afterpay_installment_productnr', //hidden element in which the installmentProfileNumber should be written
            merchantID: 'CP_{$fatchipFCSPaymentConfig.merchantID}',
            // merchantID : '4564',
            country: 'DE',
            language: 'DE',
        };

        {literal}
        !function () {
            var e = document.createElement("script");
            e.type = "text/javascript", e.async = !0, e.src = "https://cdn.afterpay.io/ressources/AfterPay.js", e.onload = function () {
                APJS_init_BankLookup();
                APJS_PartPayment_init_InstallmentSelection()
            };
            var t = document.getElementsByTagName("script")[0];
            t.parentNode.insertBefore(e, t)
        }();
        {/literal}
    </script>
{/if}

<script type="text/javascript">
    {if $FatchipFCSPaymentData.afterpayinstallmentiban}
    var el = document.getElementById("fatchip_firstcash_afterpay_installment_iban");
    window.addEventListener('load', function () {
        APJS_BankLookup_checkIBANInput(el.value);
    }, false);
    {/if}
</script>