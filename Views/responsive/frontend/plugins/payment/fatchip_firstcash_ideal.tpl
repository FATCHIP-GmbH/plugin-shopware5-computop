    <div class="fatchip-firstcash-payment-ideal-form payment--form-group">
            {block name="frontend_checkout_payment_fatchip_firstcash_ideal_issuer_label"}
                <p class="none">
                    <label for="fatchip_firstcash_ideal_issuer">{s name='bankchoose' namespace='frontend/FatchipFCSPayment/translations'}Wählen Sie Ihre Bank{/s}:</label>
                </p>
            {/block}

            <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_firstcash_ideal_issuer_input"}
                    <select name="FatchipFirstCashPaymentData[fatchip_firstcash_ideal_issuer]"
                            id="fatchip_firstcash_ideal_issuer"
                            class="is--required"
                            {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                    >
                        <option disabled="disabled" value="">--</option>
                        {foreach from=$FatchipFCSPaymentData.idealIssuerList item=idealIssuer}
                            <option value="{$idealIssuer->getIssuerId()}"
                                    {if $idealIssuer->getIssuerId() == $FatchipFCSPaymentData.idealIssuer}selected="selected"{/if}>
                                {$idealIssuer->getName()}
                            </option>
                        {/foreach}
                    </select>
            {/block}
            </div>
    </div>
