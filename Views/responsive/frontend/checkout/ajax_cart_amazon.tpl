{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    <div class="button--container">
        <div id="LoginWithAmazon">
            RemoveMe: <BR>
            smarty.server.REQUEST_SCHEME: {$smarty.server.REQUEST_SCHEME}<BR>
            smarty.server.HTTP_REFERER: {$smarty.server.HTTP_REFERER}<BR>
        </div>
        <div class="clear"></div>
        <script>
            // Todo clear old credentials find  a better handling

            window.onAmazonLoginReady = function () {
                amazon.Login.setClientId("{$fatchipCTPaymentConfig.amazonClientId}");
            };
            window.onAmazonPaymentsReady = function () {
                var authRequest;
                OffAmazonPayments.Button('LoginWithAmazon', "{$fatchipCTPaymentConfig.amazonSellerId}",
                    {
                        type: "{$fatchipCTPaymentConfig.amazonButtonType}",
                        color: "{$fatchipCTPaymentConfig.amazonButtonColor}",
                        size: "{$fatchipCTPaymentConfig.amazonButtonSize}",
                        language: "{$Locale|replace:"_":"-"}",

                        authorization: function () {
                            loginOptions = {
                                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                                {if $smarty.server.REQUEST_SCHEME === 'https' && $smarty.server.HTTP_REFERER|strpos:'https://'=== 0}
                                    popup: true
                                {else}
                                    popup: false
                                {/if}
                            };
                            var shopReturnUrl = '{url controller='FatchipCTAmazonRegister' action='login'}';

                            {if $smarty.server.REQUEST_SCHEME === 'https' && $smarty.server.HTTP_REFERER|strpos:'https://'=== 0}
                                // Do nothing
                                console.log('Normal Mode');
                            {else}
                                // replace with https return Url
                                shopReturnUrl = shopReturnUrl.replace("http://", "https://");
                                console.log('Cookie Mode');
                            {/if}

                            console.log(shopReturnUrl);
                            authRequest = amazon.Login.authorize(loginOptions, shopReturnUrl);
                        },
                        onError: function (error) {
                            alert("The following error occurred: "
                                + error.getErrorCode()
                                + ' - ' + error.getErrorMessage());
                        }
                    });
            }
        </script>
        <script async="async"
                src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
        </script>
    </div>
{/block}

