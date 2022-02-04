<div class="payment--form-group">
    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_invoice_birthday_label"}
        <div>
            <p class="none">
                <label for="fatchip_firstcash_afterpay_invoice_birthday">{s name='birthdate' namespace='frontend/FatchipFCSPayment/translations'}Bitte geben Sie Ihr Geburtsdatum an{/s}:</label>
            </p>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_invoice_birthday_day_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_firstcash_afterpay_invoice_birthday]"
                    id="fatchip_firstcash_afterpay_invoice_birthday"
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

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_invoice_birthday_month_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_firstcash_afterpay_invoice_birthmonth]"
                    id="fatchip_firstcash_afterpay_invoice_birthmonth"
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

    {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_invoice_birthday_year_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_firstcash_afterpay_invoice_birthyear]"
                    id="fatchip_firstcash_afterpay_invoice_birthyear"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">----</option>
                {section name="birthyear" loop=2000 max=100 step=-1}
                    {$isSelected = $smarty.section.birthyear.index == $FatchipFCSPaymentData.birthyear}
                    <option value="{$smarty.section.birthyear.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthyear.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {if $FatchipFCSPaymentData.showsocialsecuritynumber}
        {block name="frontend_checkout_payment_fatchip_firstcash_klarna_invoice_socialsecuritynumber_label"}
            <div>
                <p class="none">
                    <label for="fatchip_firstcash_afterpay_invoice_socialsecuritynumber">{$FatchipFCSPaymentData.SSNLabel}</label>
                </p>
            </div>
        {/block}
        {block name="frontend_checkout_payment_fatchip_firstcash_afterpay_socialsecuritynumber_input"}
            <div>
                <input name="FatchipComputopPaymentData[fatchip_firstcash_afterpay_invoice_socialsecuritynumber]"
                       type="text"
                       id="fatchip_firstcash_afterpay_invoice_socialsecuritynumber"
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