{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_information_wrapper"}
    {$smarty.block.parent}

    {if $fatchipFCSCreditCardMode}
        <div id="fatchipFCSCreditCardPaynow"
             class="fatchip-firstcash-payment-creditcard-form payment--form-group panel has--border">
            <h2 class="panel--title is--underline">{s name='KreditkartePaynow' namespace='frontend/checkout/CTCreditCard'}Kreditkarte{/s}</h2>

            <div class="panel--body is--wide">
                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cardnumber_label"}
                    <div class="select-field">
                        {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_brand_input"}
                            <select name="CCBrand"
                                    id="CCBrand"
                                    class="is--required"
                                    {if $payment_mean.id == $form_data.payment}required="required"
                                    aria-required="true"{/if}
                                    >
                                {if $creditCardSilentModeBrandsVisa == 1}<option value="VISA">Visa</option>{/if}
                                {if $creditCardSilentModeBrandsMaster == 1}<option value="MasterCard">MasterCard</option>{/if}
                                {if $creditCardSilentModeBrandsAmex == 1}<option value="AMEX">American Express</option>{/if}
                            </select>
                        {/block}
                    </div>
                {/block}

                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cardholder_label"}
                    <p class="none">
                        <label for="CreditCardHolder">{s name="CreditcardCardHolderLabel" namespace="frontend/FatchipFCSPayment/translations"}Karteninhaber{/s}</label>
                    </p>
                {/block}

                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cardholder_input"}
                    <input name="CreditCardHolder" type="text"
                           id="CreditCardHolder"
                           class="payment--field is--required{if $error_flags.fatchip_firstcash__creditcard_cardholder} has--error{/if}"
                           placeholder="{s name="CreditcardCardHolderPlaceholder" namespace="frontend/FatchipFCSPayment/translations"}Karteninhaber{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                           {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                           value="{$FatchipFCSPaymentData.CreditCardHolder}"
                    />
                {/block}

                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cardnumber_label"}
                    <p class="none">
                        <label for="FCSCCNr">{s name="CreditcardCardnumberLabel" namespace="frontend/FatchipFCSPayment/translations"}Kartennummer{/s}</label>
                    </p>
                {/block}

                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cardnumber_input"}
                    <input name="FCSCCNr" type="text"
                           id="FCSCCNr"
                           class="payment--field is--required{if $error_flags.fatchip_firstcash__creditcard_cardnumber} has--error{/if}"
                           placeholder="{s name='CreditcardCardnumberPlaceholder' namespace='frontend/FatchipFCSPayment/translations'}Kartennummer{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                           {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                           value="{$FatchipFCSPaymentData.creditcardcardnumber}"
                            />
                {/block}

                {* The main form field table *}
                {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_expirationdate_label"}
                    <p class="none">
                        <label for="fatchip_firstcash_creditcard_expirationdate">{s name='CreditcardExpirationdate' namespace='frontend/FatchipFCSPayment/translations'}Kartenablaufdatum{/s}:</label>
                    </p>
                {/block}
                <div class="select-field">
                    {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_expirationdate_month_input"}
                        <select name="FCSCCExpiryMonth"
                                id="FCSCCExpiryMonth"
                                class="is--required"
                                {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                                >
                            <option disabled="disabled" value="">-</option>
                            {section name="expirationdatemonth" start=1 loop=13 step=1}
                                <option value="{if $smarty.section.expirationdatemonth.index < 10}0{$smarty.section.expirationdatemonth.index}{else}{$smarty.section.expirationdatemonth.index}{/if}">
                                    {if $smarty.section.expirationdatemonth.index < 10}0{/if}{$smarty.section.expirationdatemonth.index}
                                </option>
                            {/section}
                        </select>
                    {/block}
                </div>
                <div class="select-field">
                    {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_expirationdate_year_input"}
                        {assign var=thisyear value=$smarty.now|date_format:"%Y"}
                        <select name="FCSCCExpiryYear"
                                id="FCSCCExpiryYear"
                                class="is--required"
                                {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                                >
                            <option disabled="disabled" value="">----</option>
                            {section name="expirationdateyear" start=$thisyear loop=$thisyear + 15 step=1}
                                {$isSelected = $smarty.section.expirationdateyear.index == $FatchipFCSPaymentData.expirationdateyear}
                                <option value="{$smarty.section.expirationdateyear.index}"
                                        {if $isSelected}selected{/if}>
                                    {$smarty.section.expirationdateyear.index}
                                </option>
                            {/section}
                        </select>
                    {/block}
                </div>
                <div>
                    {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cvc_label"}
                        <p class="none">
                            <label for="CCCVC">{s name='CreditcardCvc' namespace='frontend/FatchipFCSPayment/translations'}CVC Code{/s}</label>
                        </p>
                    {/block}

                    {block name="frontend_checkout_payment_fatchip_firstcash_creditcard_cvc_input"}
                        <input name="CCCVC" type="text"
                               id="CCCVC"
                               class="payment--field is--required{if $error_flags.fatchip_firstcash__creditcard_cvc} has--error{/if}"
                               placeholder="{s name='CreditcardCvc' namespace='frontend/FatchipFCSPayment/translations'}CVC Code{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                               value="{$FatchipFCSPaymentData.creditcardcvc}"
                                />
                        <input type="hidden" name="MerchantID" id="MerchantID"
                               value="{$fatchipFCSCreditCardSilentParams.MerchantID}">
                        <input type="hidden" name="FCSCCExpiry" id="FCSCCExpiry" value=" ">
                        <input type="hidden" name="CreditCardHolder" id="CreditCardHolder" value=" ">
                        <input type="hidden" name="Len" id="Len" value="{$fatchipFCSCreditCardSilentParams.Len}">
                        <input type="hidden" name="Data" id="Data" value="{$fatchipFCSCreditCardSilentParams.Data}">
                    {/block}
                </div>
            </div>
        </div>
    {/if}
{/block}

