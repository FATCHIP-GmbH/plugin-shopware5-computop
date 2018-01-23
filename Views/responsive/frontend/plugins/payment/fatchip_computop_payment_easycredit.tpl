
    {* The main container for filling in the birthday field *}
    <div class="fatchip-computop-payment-easycredit-form payment--form-group">
        {* A box for displaying general errors *}
        <div class="fatchip-computop-payment-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {if ! $data.birthday }

        {* The main form field table *}
        {block name="frontend_checkout_payment_fatchip_computop_birthday_label"}
            Birthday: {$Birthday} <BR>
            <p class="none">
                <label for="fatchip_computop_easycredit_birthday">{s name='birthdate'}Bitte geben Sie Ihr Geburtsdatum an:{/s}</label>
            </p>
        {/block}

        <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_birthday_day_input"}
                <select name="FatchipComputopPaymentData[fatchip_computop_easycredit_birthday]"
                        id="fatchip_computop_easycredit_birthday"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">--</option>
                    {section name="birthdate" start=1 loop=32 step=1}
                        {$isSelected = $smarty.section.birthdate.index == $data.birthday}
                        <option value="{$smarty.section.birthdate.index}" {if $isSelected}selected{/if}>
                            {$smarty.section.birthdate.index}
                        </option>
                    {/section}
                </select>
            {/block}
        </div>

        <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_birthday_birthday_month_input"}
                <select name="FatchipComputopPaymentData[fatchip_computop_easycredit_birthmonth]"
                        id="fatchip_computop_easycredit_birthmonth"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">-</option>
                    {section name="birthmonth" start=1 loop=13 step=1}
                        {$isSelected = $smarty.section.birthmonth.index == $data.birthmonth}
                        <option value="{$smarty.section.birthmonth.index}" {if $isSelected}selected{/if}>
                            {$smarty.section.birthmonth.index}
                        </option>
                    {/section}
                </select>
            {/block}
        </div>

        <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_birthday_year_input"}
                <select name="FatchipComputopPaymentData[fatchip_computop_easycredit_birthyear]"
                        id="fatchip_computop_easycredit_birthyear"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">----</option>
                    {section name="birthyear" loop=2000 max=100 step=-1}
                        {$isSelected = $smarty.section.birthyear.index == $data.birthyear}
                        <option value="{$smarty.section.birthyear.index}" {if $isSelected}selected{/if}>
                            {$smarty.section.birthyear.index}
                        </option>
                    {/section}
                </select>
            {/block}
        </div>
        {/if}
        {* A box for displaying validation errors *}
        <div class="fatchip-computop-payment-validation-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>
    </div>