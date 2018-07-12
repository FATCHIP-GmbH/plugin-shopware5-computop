<div class="fatchip-computop-payment-klarna-form payment--form-group">
    {block name="frontend_checkout_payment_fatchip_computop_laschtrift_bank_label"}
        <p class="none">
            <label for="fatchip_computop_lastschrift_bank">{s name='LastschriftBankLabel'}Kreditinstitut{/s}</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_lastschrift_bank_input"}
        <input name="FatchipComputopPaymentData[fatchip_computop_lastschrift_bank]" type="text"
               id="fatchip_computop_lastschrift_bank"
               class="payment--field is--required{if $error_flags.fatchip_computop__lastschrift_bank} has--error{/if}"
               placeholder="{s name='lastschriftBank'}Kreditinstitut{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
               value="{$FatchipCTPaymentData.lastschriftbank}"
                />
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_laschtrift_kontoinhaber_label"}
        <p class="none">
            <label for="fatchip_computop_lastschrift_bank">{s name='LastschriftKontoinhaberLabel'}Kontoinhaber{/s}</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_lastschrift_kontoinhaber_input"}
        <input name="FatchipComputopPaymentData[fatchip_computop_lastschrift_kontoinhaber]" type="text"
               id="fatchip_computop_lastschrift_kontoinhaber"
               class="payment--field is--required{if $error_flags.fatchip_computop__lastschrift_bank} has--error{/if}"
               placeholder="{s name='lastschriftKontoinhaber'}Kontoinhaber{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
               {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
               value="{$FatchipCTPaymentData.lastschriftkontoinhaber}"
                />
    {/block}
    {block name="frontend_checkout_payment_fatchip_computop_laschtrift_iban_label"}
        <p class="none">
            <label for="fatchip_computop_lastschrift_bank">{s name='LastschriftIbanLabel'}IBAN{/s}</label>
        </p>
    {/block}

    {block name="frontend_checkout_payment_fatchip_computop_lastschrift_iban_input"}
        {if $FatchipCTPaymentIbanAnon === 0}
            <input name="FatchipComputopPaymentData[fatchip_computop_lastschrift_iban]" type="text"
                   id="fatchip_computop_lastschrift_iban"
                   class="payment--field is--required{if $error_flags.fatchip_computop__lastschrift_iban} has--error{/if}"
                   placeholder="{s name='lastschriftIban'}IBAN{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                   value="{$FatchipCTPaymentData.lastschriftiban}"
                    />
        {else}
            <input name="FatchipComputopPaymentData[fatchip_computop_lastschrift_iban]" type="hidden"
                   id="fatchip_computop_lastschrift_iban"
                   class="payment--field is--required{if $error_flags.fatchip_computop__lastschrift_iban} has--error{/if}"
                   placeholder="{s name='lastschriftIban'}IBAN{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}{/if}
                   value="{$FatchipCTPaymentData.lastschriftiban}"
            />
            <input name="FatchipComputopPaymentData[fatchip_computop_lastschrift_iban_anon]" type="text"
                   id="fatchip_computop_lastschrift_iban_anon"
                   class="payment--field is--required{if $error_flags.fatchip_computop__lastschrift_iban} has--error{/if}"
                   placeholder="{s name='lastschriftIban'}IBAN{/s}{s name="RequiredField" namespace="frontend/register/index"}{/s}"
                   {if $payment_mean.id == $form_data.payment}{/if}
                   value="{$FatchipCTPaymentData.lastschriftiban|truncate:18:"XXXXX":true}"
            />
        {/if}
    {/block}
</div>
