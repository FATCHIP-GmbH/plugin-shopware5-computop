<div class="payment--form-group">
    {block name="frontend_checkout_payment_fatchip_computop_afterpay_invoice_birthday_label"}
        <div>
            <p class="none">
                <label for="fatchip_computop_afterpay_invoice_birthday">{s name='birthdate' namespace='frontend/FatchipCTPayment/translations'}Bitte geben Sie Ihr Geburtsdatum an{/s}:</label>
            </p>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_afterpay_invoice_birthday_day_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_computop_afterpay_invoice_birthday]"
                    id="fatchip_computop_afterpay_invoice_birthday"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">--</option>
                {section name="birthdate" start=1 loop=32 step=1}
                    {$isSelected = $smarty.section.birthdate.index == $FatchipCTPaymentData.birthday}
                    <option value="{$smarty.section.birthdate.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthdate.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_afterpay_invoice_birthday_month_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_computop_afterpay_invoice_birthmonth]"
                    id="fatchip_computop_afterpay_invoice_birthmonth"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">-</option>
                {section name="birthmonth" start=1 loop=13 step=1}
                    {$isSelected = $smarty.section.birthmonth.index == $FatchipCTPaymentData.birthmonth}
                    <option value="{$smarty.section.birthmonth.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthmonth.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_afterpay_invoice_birthday_year_input"}
        <div class="select-field">
            <select name="FatchipComputopPaymentData[fatchip_computop_afterpay_invoice_birthyear]"
                    id="fatchip_computop_afterpay_invoice_birthyear"
                    class="is--required"
                    {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
            >
                <option disabled="disabled" value="">----</option>
                {assign var=thisyear value=$smarty.now|date_format:"%Y"}
                {section name="birthyear" loop=$thisyear-17 max=100 step=-1}
                    {$isSelected = $smarty.section.birthyear.index == $FatchipCTPaymentData.birthyear}
                    <option value="{$smarty.section.birthyear.index}" {if $isSelected}selected{/if}>
                        {$smarty.section.birthyear.index}
                    </option>
                {/section}
            </select>
        </div>
    {/block}

    {if $FatchipCTPaymentData.showsocialsecuritynumber}
        {block name="frontend_checkout_payment_fatchip_computop_klarna_invoice_socialsecuritynumber_label"}
            <div>
                <p class="none">
                    <label for="fatchip_computop_afterpay_invoice_socialsecuritynumber">{$FatchipCTPaymentData.SSNLabel}</label>
                </p>
            </div>
        {/block}
        {block name="frontend_checkout_payment_fatchip_computop_afterpay_socialsecuritynumber_input"}
            <div>
                <input name="FatchipComputopPaymentData[fatchip_computop_afterpay_invoice_socialsecuritynumber]"
                       type="text"
                       id="fatchip_computop_afterpay_invoice_socialsecuritynumber"
                       {if $FatchipCTPaymentData.SSNMaxLen}maxlength={$FatchipCTPaymentData.SSNMaxLen}{/if}
                       class="payment--field
                       is--required{if $error_flags.fatchip_computop__afterpay_socialsecuritynumber} has--error{/if}"
                placeholder="{$FatchipCTPaymentData.SSNLabel}{s name="RequiredField" namespace="frontend/register/index"}{/s}
                "
                {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                value="{$FatchipCTPaymentData.socialsecuritynumber}"
                />
            </div>
        {/block}
    {/if}
</div>