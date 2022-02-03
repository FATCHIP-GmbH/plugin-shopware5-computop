
    {* The main container for filling in the birthday field *}
    <div class="fatchip-firstcash-payment-easycredit-form payment--form-group">

        {* The main form field table *}
        {block name="frontend_checkout_payment_fatchip_firstcash_easycredit_birthday_label"}
            <p class="none">
                <label for="fatchip_firstcash_easycredit_birthday">{s name='birthdate' namespace='frontend/FatchipCTPayment/translations'}Bitte geben Sie Ihr Geburtsdatum an{/s}:</label>
            </p>
        {/block}

        <div class="select-field">
        {block name="frontend_checkout_payment_fatchip_firstcash_easycredit_birthday_day_input"}
                <select name="FatchipComputopPaymentData[fatchip_firstcash_easycredit_birthday]"
                        id="fatchip_firstcash_easycredit_birthday"
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
        {/block}
        </div>

        <div class="select-field">
        {block name="frontend_checkout_payment_fatchip_firstcash_easycredit_birthday_month_input"}
                <select name="FatchipComputopPaymentData[fatchip_firstcash_easycredit_birthmonth]"
                        id="fatchip_firstcash_easycredit_birthmonth"
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
        {/block}
        </div>

        <div class="select-field">
        {block name="frontend_checkout_payment_fatchip_firstcash_easycredit_birthday_year_input"}
                <select name="FatchipComputopPaymentData[fatchip_firstcash_easycredit_birthyear]"
                        id="fatchip_firstcash_easycredit_birthyear"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">----</option>
                    {section name="birthyear" loop=2000 max=100 step=-1}
                        {$isSelected = $smarty.section.birthyear.index == $FatchipCTPaymentData.birthyear}
                        <option value="{$smarty.section.birthyear.index}" {if $isSelected}selected{/if}>
                            {$smarty.section.birthyear.index}
                        </option>
                    {/section}
                </select>
        {/block}
        </div>

</div>
