    <div class="fatchip-computop-payment-sofort-form payment--form-group">

        {if !$FatchipCTPaymentData.issuer}
            {block name="frontend_checkout_payment_fatchip_computop_sofort_issuer_label"}
                <p class="none">
                    <label for="fatchip_computop_sofort_issuer">{s name='sofortIssuerLabel'}Wählen Sie Ihre Bank{/s}</label>
                </p>
            {/block}

            <div class="select-field">
            {block name="frontend_checkout_payment_fatchip_computop_sofort_issuer_input"}
                    <select name="FatchipComputopPaymentData[fatchip_computop_sofort_issuer]"
                            id="fatchip_computop_sofort_issuer"
                            class="is--required"
                            {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}
                    >
                        <option disabled="disabled" value="">--</option>
                        {foreach from=$FatchipCTPaymentData.sofortIssuerList item=sofortIssuer}
                            <option value="{$sofortIssuer->getIssuerId()}"
                                    {if $sofortIssuer->getIssuerId() == $FatchipCTPaymentData.sofortIssuer}selected="selected"{/if}>
                                {$sofortIssuer->getName()}
                            </option>
                        {/foreach}
                    </select>
            {/block}
            </div>
        {/if}
    </div>
