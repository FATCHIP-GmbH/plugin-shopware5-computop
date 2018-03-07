{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    {if $sBasket.content}
    <div class="button--container">
        <div id="LoginWithAmazon">
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
                                popup: true
                            };
                            var shopReturnUrl = "{url controller='FatchipCTAmazonRegister' action='login'}";
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
    {/if}
{/block}