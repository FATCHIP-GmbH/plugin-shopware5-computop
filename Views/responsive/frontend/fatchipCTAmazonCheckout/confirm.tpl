{extends file="parent:frontend/checkout/confirm.tpl"}

{* change register Steps to 1 Ihre Adresse, 2 Versandart, 3 Prüfen und Bestellen *}
{* Step box *}
{block name='frontend_index_navigation_categories_top'}
    {* Step box *}
    <div class="steps--container container">
        <div class="steps--content panel--body center">
            {block name='frontend_register_steps'}
                <ul class="steps--list">

                    {* First Step - Address *}
                    {block name='frontend_register_steps_basket'}
                        <li class="steps--entry step--basket{if $sStepActive=='address'} is--active{/if}">
                            <span class="icon">1</span>
                            <span class="text"><span class="text--inner">{s name='AmazonPaymentDispatch' namespace='frontend/FatchipFCSPayment/translations'}Adresse und Zahlart{/s}</span></span>
                        </li>
                    {/block}

                    {* Spacer *}
                    {block name='frontend_register_steps_spacer1'}
                        <li class="steps--entry steps--spacer">
                            <i class="icon--arrow-right"></i>
                        </li>
                    {/block}

                    {* Second Step - Payment *}
                    {block name='frontend_register_steps_register'}
                        <li class="steps--entry step--register{if $sStepActive=='paymentShipping'} is--active{/if}">
                            <span class="icon">2</span>
                            <span class="text"><span class="text--inner">{s name='AmazonDispatch' namespace='frontend/FatchipFCSPayment/translations'}Versandart{/s}</span></span>
                        </li>
                    {/block}

                    {* Spacer *}
                    {block name='frontend_register_steps_spacer2'}
                        <li class="steps--entry steps--spacer">
                            <i class="icon--arrow-right"></i>
                        </li>
                    {/block}

                    {* Third Step - Confirmation *}
                    {block name='frontend_register_steps_confirm'}
                        <li class="steps--entry step--confirm is--active">
                            <span class="icon">3</span>
                            <span class="text"><span class="text--inner">{s name='AmazonCheckConfirm' namespace='frontend/FatchipFCSPayment/translations'}Prüfen und Bestellen{/s}</span></span>
                        </li>
                    {/block}
                </ul>
            {/block}
        </div>
    </div>
{/block}

{* SW 5.0, 5.1 Disable BillingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_billing_address_actions"}
{/block}

{* SW 5.2 - 5.3, 5.4? Change PaymentMean Selection Action Button to FatchipFCSAmazonCheckout Controller *}
{* for shippingAddress != billingAddress *}
{* Billing: *}
{block name="frontend_checkout_confirm_information_addresses_billing_panel_actions"}
    <div class="panel--actions is--wide">
    </div>
{/block}

{* SW 5.2 - 5.3, 5.4? Change PaymentMean Selection Action Button to FatchipFCSAmazonCheckout Controller *}
{* for shippingAddress = billingAddress *}
{* Billing and Shipping: *}
{block name="frontend_checkout_confirm_information_addresses_equal_panel_shipping"}
{/block}

{* SW 5.0 , 5.1 Disable ShippingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_shipping_address_actions"}
{/block}

{* SW 5.2 - 5.3, 5.4? Change PaymentMean Selection Action Button to FatchipFCSAmazonCheckout Controller *}
{* for shippingAddress != billingAddress *}
{block name="frontend_checkout_confirm_information_addresses_shipping_panel_actions"}
<div class="panel--actions is--wide">
</div>
{/block}


{* SW 5.0 - 5.4 Change PaymentMean Selection Action Button to FatchipFCSAmazonCheckout Controller *}
{block name='frontend_checkout_confirm_left_payment_method_actions'}
    <div class="panel--actions is--wide payment--actions">
        {* Action buttons *}
        <a href="{url controller=FatchipFCSAmazonCheckout action=shippingPayment sTarget=checkout}" class="btn is--small btn--change-payment">
            Ändern
        </a>
    </div>
{/block}

{block name="frontend_index_footer"}
    <div id="fatchipFCSAmazonInformation" hidden
         data-fatchipFCSAmazonOrderReferenceId='{$fatchipFCSAmazonReferenceID}'
         data-fatchipFCSAmazonSCOUrl='{url controller="FatchipFCSAjax" action="ctAmznSetOrderDetailsAndConfirmOrder" forceSecure referenceId=$fatchipFCSAmazonReferenceID}'
         data-fatchipFCSAmazonSellerId='{$fatchipFCSPaymentConfig.amazonSellerId}'
         data-fatchipFCSCartErrorUrl='{url controller="checkout" action="cart" forceSecure amznLogout="true" amznError="SCO"}'
    ></div>

    <script async="async"
        {if $fatchipFCSPaymentConfig.amazonLiveMode === 'Live'}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'>
        {else}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
        {/if}
    </script>

    {$smarty.block.parent}
{/block}
