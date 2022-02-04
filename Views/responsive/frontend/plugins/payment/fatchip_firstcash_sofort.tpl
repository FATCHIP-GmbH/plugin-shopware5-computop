    <div class="fatchip-firstcash-payment-sofort-form payment--form-group">
        {block name="frontend_checkout_payment_fatchip_firstcash_sofort_issuer_label"}
            <p class="none">
                <label for="fatchip_firstcash_sofort_issuer">{s name='bankchoose' namespace='frontend/FatchipFCSPayment/translations'}Wählen Sie Ihre Bank{/s}:</label>
            </p>
        {/block}

        <div class="select-field">
        {block name="frontend_checkout_payment_fatchip_firstcash_sofort_issuer_input"}
                <select name="FatchipComputopPaymentData[fatchip_firstcash_sofort_issuer]"
                        id="fatchip_firstcash_sofort_issuer"
                        class="is--required"
                        {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                >
                    <option disabled="disabled" value="">--</option>
                    {foreach from=$FatchipFCSPaymentData.sofortIssuerList item=sofortIssuer}
                        <option value="{$sofortIssuer->getIssuerId()}"
                                {if $sofortIssuer->getIssuerId() == $FatchipFCSPaymentData.sofortIssuer}selected="selected"{/if}>
                            {$sofortIssuer->getName()}
                        </option>
                    {/foreach}
                </select>
        {/block}
        </div>
    </div>
