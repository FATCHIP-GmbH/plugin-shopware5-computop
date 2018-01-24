    {* The main container for filling in the birthday field *}
    <div class="fatchip-computop-payment-klarna-form payment--form-group">
        {* A box for displaying general errors *}
        <div class="fatchip-computop-payment-error-box alert is--error is--rounded" style="display: none;">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div class="alert--content error-content"></div>
        </div>

        {if !$sUserData.billingaddress.phone}
            {block name="frontend_checkout_payment_fatchip_computop_telephone_label"}
                <p class="none">
                    <label for="fatchip_computop__klarna_telephone">{s name='klarnaTelephoneLabel'}Telefonnummer{/s}</label>
                </p>
            {/block}

            {block name="frontend_checkout_payment_payone_telephone_input"}
                <input name="FatchipComputopPaymentData[fatchip_computop__klarna_telephone]" type="text"
                       id="fatchip_computop__klarna_telephone"
                       class="payment--field is--required{if $error_flags.fatchip_computop__klarna_telephone} has--error{/if}"
                       placeholder="{s name='telephoneNumber'}Telefonnummer{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                       {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                       value="{$sUserData.billingaddress.phone}"
                        />
            {/block}
        {/if}

        {if !$sUserData.additional.user.birthday && $sUserData.additional.user.birthday != '0000-00-00' }
            {* The main form field table *}
            {block name="frontend_checkout_payment_fatchip_computop_birthday_label"}
                <p class="none">
                    <label for="fatchip_computop_klarna_birthday">{s name='birthdate'}Bitte geben Sie Ihr Geburtsdatum an:{/s}</label>
                </p>
            {/block}

            <div class="select-field">
                {block name="frontend_checkout_payment_fatchip_computop_birthday_day_input"}
                    <select name="FatchipComputopPaymentData[fatchip_computop_klarna_birthday]"
                            id="fatchip_computop_klarna_birthday"
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
                    <select name="FatchipComputopPaymentData[fatchip_computop_klarna_birthmonth]"
                            id="fatchip_computop_klarna_birthmonth"
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
                    <select name="FatchipComputopPaymentData[fatchip_computop_klarna_birthyear]"
                            id="fatchip_computop_klarna_birthyear"
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
