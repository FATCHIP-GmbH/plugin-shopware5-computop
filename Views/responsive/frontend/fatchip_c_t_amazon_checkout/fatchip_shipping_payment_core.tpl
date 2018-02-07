{* Error messages *}
{block name='frontend_account_payment_error_messages'}
    {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
{/block}

<div class="confirm--outer-container">
    <form id="shippingPaymentForm" name="shippingPaymentForm" method="post" action="{url controller='FatchipCTAmazonCheckout' action='saveShippingPayment' sTarget='checkout' sTargetAction='index'}" class="payment">

        {* Action top *}
        {block name='frontend_checkout_shipping_payment_core_buttons'}
            <div class="confirm--actions table--actions block">
                <button type="submit" class="btn is--primary is--icon-right is--large right main--actions">{s namespace='frontend/checkout/shipping_payment' name='NextButton'}{/s}<i class="icon--arrow-right"></i></button>
            </div>
        {/block}

        {* Payment and shipping information *}
        <div class="shipping-payment--information">

            {* Payment method *}
            <div class="confirm--inner-container block">


                {block name='frontend_checkout_shipping_payment_core_payment_fields'}
                    <style type="text/css">

                        #fatchipCTWalletWidgetDiv {
                            min-width: 300px;
                            width: 100%;
                            max-width: 550px;
                            min-height: 228px;
                            height: 240px;
                            max-height: 400px;
                        }
                    </style>

                    {* Error Messages Javascript*}
                    <div id="amazonErrors" style="display:none">
                        <div class="alert is--error is--rounded">
                            <div class="alert--icon">
                                <i class="icon--element icon--cross"></i>
                            </div>
                            <div id="AmazonErrorContent" class="alert--content">
                            </div>
                        </div>
                    </div>

                    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="margin-top:2%;margin-bottom: 0px; padding-bottom: 1%;">

                        <div id="debug">Amazon LGN:<BR>{$fatchipCTResponse|var_dump}</div>
                        <!-- Place this code in your HTML where you would like the address widget to appear. -->
                        <div id="fatchipCTAddressBookWidgetDiv"  style="float:left;margin-right:5%;"></div>
                        <div id="fatchipCTWalletWidgetDiv" style="float:left;"></div>
                    </div>

<!--                    <script>
                        window.onAmazonLoginReady = function() {
                            amazon.Login.setClientId("{$fatchipCTPaymentConfig.amazonClientId}");
                        };

                        window.onAmazonPaymentsReady = function () {
                            new OffAmazonPayments.Widgets.Wallet({
                                sellerId: "{$fatchipCTPaymentConfig.amazonSellerId}",
                                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                                onPaymentSelect: function (orderReference) {

                                },
                                design: {
                                    designMode: 'responsive'
                                },
                                onError: function (error) {
                                    console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                                    // See "Handling Errors" for more information.
                                }
                            }).bind("fatchipCTWalletWidgetDiv");
                        };

                    </script>
                    <script async="async"
                            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
                    </script>
-->
                    <input type="hidden" name="payment" value="{$fatchipCTAmazonpayID}" />

                {/block}
            </div>

            {* Shipping method *}
            {if $sDispatches}
                <div class="confirm--inner-container block">
                    {block name='frontend_checkout_shipping_payment_core_shipping_fields'}
                        {include file="frontend/checkout/change_shipping.tpl"}
                    {/block}
                </div>
            {/if}
        </div>
    </form>

    {* Cart values *}
    <div class="confirm--inner-container block">
        {block name='frontend_checkout_shipping_payment_core_footer'}
            {include file="frontend/checkout/cart_footer.tpl"}
        {/block}
    </div>

    {* Action bottom *}
    {block name='frontend_checkout_shipping_payment_core_buttons'}
        <div class="confirm--actions table--actions block">
            <button type="submit" form="shippingPaymentForm" class="btn is--primary is--icon-right is--large right main--actions">{s namespace='frontend/checkout/shipping_payment' name='NextButton'}{/s}<i class="icon--arrow-right"></i></button>
        </div>
    {/block}

    {* Benefit and services footer *}
    {block name="frontend_checkout_footer"}
        {include file="frontend/checkout/table_footer.tpl"}
    {/block}
</div>