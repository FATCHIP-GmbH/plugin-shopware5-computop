<div class="fatchip-computop-payment-creditcard-form payment--form-group">
    {if $fatchipCTCreditCardMode}
        {block name="frontend_checkout_payment_fatchip_computop_creditcard_cardnumber_label"}
            <p class="none">
                <label for="fatchip_computop_creditcard_cardnumber">{s name='CreditcardCardnumberLabel'}Kartennummer{/s}</label>
            </p>
        {/block}

        {block name="frontend_checkout_payment_fatchip_computop_creditcard_cardnumber_input"}
            <input name="FatchipComputopPaymentData[fatchip_computop_creditcard_cardnumber]" type="text"
                   id="fatchip_computop_creditcard_cardnumber"
                   class="payment--field is--required{if $error_flags.fatchip_computop__creditcard_cardnumber} has--error{/if}"
                   placeholder="{s name='creditcardCardnumber'}Kartennummer{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                   value="{$FatchipCTPaymentData.creditcardcardnumber}"
            />
        {/block}

        {* The main form field table *}
        {block name="frontend_checkout_payment_fatchip_computop_creditcard_expirationdate_label"}
            <p class="none">
                <label for="fatchip_computop_creditcard_expirationdate">{s name='CreditcardExpirationdate'}Kartenablaufdatum:{/s}</label>
            </p>
        {/block}
        <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_creditcard_expirationdate_month_input"}
                <select name="FatchipComputopPaymentData[fatchip_computop_creditcard_expirationdatemonth]"
                        id="fatchip_computop_creditcard_expirationdatemonth"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">-</option>
                    {section name="expirationdatemonth" start=1 loop=13 step=1}
                        <option value="{if $smarty.section.expirationdatemonth.index < 10}0{/if}">
                            {if $smarty.section.expirationdatemonth.index < 10}0{/if}{$smarty.section.expirationdatemonth.index}
                        </option>
                    {/section}
                </select>
            {/block}
        </div>
        <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_creditcard_expirationdate_year_input"}
                {assign var=thisyear value=$smarty.now|date_format:"%Y"}
                <select name="FatchipComputopPaymentData[fatchip_computop_creditcard_expirationdateyear]"
                        id="fatchip_computop_creditcard_expirationdateyear"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">----</option>
                    {section name="expirationdateyear" start=$thisyear loop=$thisyear + 15 step=1}
                        {$isSelected = $smarty.section.expirationdateyear.index == $FatchipCTPaymentData.expirationdateyear}
                        <option value="{$smarty.section.expirationdateyear.index}" {if $isSelected}selected{/if}>
                            {$smarty.section.expirationdateyear.index}
                        </option>
                    {/section}
                </select>
            {/block}
        </div>
        <div>
            {block name="frontend_checkout_payment_fatchip_computop_creditcard_cvc_label"}
                <p class="none">
                    <label for="fatchip_computop_creditcard_cvc">{s name='CreditcardCvc'}CVC Code{/s}</label>
                </p>
            {/block}

            {block name="frontend_checkout_payment_fatchip_computop_creditcard_cvc_input"}
                <input name="FatchipComputopPaymentData[fatchip_computop_creditcard_cvc]" type="text"
                       id="fatchip_computop_creditcard_cvc"
                       class="payment--field is--required{if $error_flags.fatchip_computop__creditcard_cvc} has--error{/if}"
                       placeholder="{s name='creditcardCvc'}CVC Code{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                       {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                       value="{$FatchipCTPaymentData.creditcardcvc}"
                />
            {/block}
        </div>
        <div id="fatchipCTCreditCard" hidden
             data-fatchipCTCCNr'123'
             data-fatchipCTCCCVC='456'
             data-fatchipCTCCExpiryYear='2018'
             data-fatchipCTCCExpiryMonth='04'
             data-fatchipCTCCBrand='Visa'
             data-fatchipCTCCPaymentId={$payment_mean.id}
        ></div>
    {/if}
</div>
