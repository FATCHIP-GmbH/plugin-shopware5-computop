<div class="fatchip-firstcash-payment-klarna-form payment--form-group">
    {block name="frontend_checkout_payment_fatchip_firstcash_laschtrift_bank_label"}
        <p class="none">
            <label for="fatchip_firstcash_lastschrift_bank">{s name='Bank' namespace='frontend/FatchipFCSPayment/translations'}Kreditinstitut{/s}</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_lastschrift_bank_input"}
        <input name="FatchipFirstCashPaymentData[fatchip_firstcash_lastschrift_bank]" type="text"
               id="fatchip_firstcash_lastschrift_bank"
               class="payment--field is--required{if $error_flags.fatchip_firstcash__lastschrift_bank} has--error{/if}"
               placeholder="{s name='Bank' namespace='frontend/FatchipFCSPayment/translations'}Kreditinstitut{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
               value="{$FatchipFCSPaymentData.lastschriftbank}"
                />
    {/block}
    {block name="frontend_checkout_payment_fatchip_firstcash_laschtrift_kontoinhaber_label"}
        <p class="none">
            <label for="fatchip_firstcash_lastschrift_bank">{s name='AccountHolderLabel' namespace='frontend/FatchipFCSPayment/translations'}Kontoinhaber{/s}</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_lastschrift_kontoinhaber_input"}
        <input name="FatchipFirstCashPaymentData[fatchip_firstcash_lastschrift_kontoinhaber]" type="text"
               id="fatchip_firstcash_lastschrift_kontoinhaber"
               class="payment--field is--required{if $error_flags.fatchip_firstcash__lastschrift_bank} has--error{/if}"
               placeholder="{s name='AccountHolderPlaceholder' namespace='frontend/FatchipFCSPayment/translations'}Kontoinhaber{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
               value="{$FatchipFCSPaymentData.lastschriftkontoinhaber}"
                />
    {/block}
    {block name="frontend_checkout_payment_fatchip_firstcash_laschtrift_iban_label"}
        <p class="none">
            <label for="fatchip_firstcash_lastschrift_bank">IBAN</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_firstcash_lastschrift_iban_input"}
        {if $FatchipFCSPaymentIbanAnon === 0}
            <input name="FatchipFirstCashPaymentData[fatchip_firstcash_lastschrift_iban]" type="text"
                   id="fatchip_firstcash_lastschrift_iban"
                   class="payment--field is--required{if $error_flags.fatchip_firstcash__lastschrift_iban} has--error{/if}"
                   placeholder="IBAN{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                   value="{$FatchipFCSPaymentData.lastschriftiban}"
                    />
        {else}
            <input name="FatchipFirstCashPaymentData[fatchip_firstcash_lastschrift_iban]" type="hidden"
                   id="fatchip_firstcash_lastschrift_iban"
                   class="payment--field is--required{if $error_flags.fatchip_firstcash__lastschrift_iban} has--error{/if}"
                   placeholder="IBAN{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}{/if}
                   value="{$FatchipFCSPaymentData.lastschriftiban}"
            />
            <input name="FatchipFirstCashPaymentData[fatchip_firstcash_lastschrift_iban_anon]" type="text"
                   id="fatchip_firstcash_lastschrift_iban_anon"
                   class="payment--field is--required{if $error_flags.fatchip_firstcash__lastschrift_iban} has--error{/if}"
                   placeholder="IBAN{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}{/if}
                   value="{$FatchipFCSPaymentData.lastschriftiban|truncate:18:"XXXXX":true}"
            />
        {/if}
    {/block}
</div>
