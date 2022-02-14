{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    {if $sBasket.content}
    <div class="button--container">
        <div id="FCSLoginWithAmazon">
        </div>
        <div class="clear"></div>
        <script>
            window.onAmazonLoginReady = function () {
                amazon.Login.setClientId("{$fatchipFCSPaymentConfig.amazonClientId}");
            };
            window.onAmazonPaymentsReady = function () {
                var authRequest;
                OffAmazonPayments.Button('FCSLoginWithAmazon', "{$fatchipFCSPaymentConfig.amazonSellerId}",
                    {
                        type: "{$fatchipFCSPaymentConfig.amazonButtonType}",
                        color: "{$fatchipFCSPaymentConfig.amazonButtonColor}",
                        size: "{$fatchipFCSPaymentConfig.amazonButtonSize}",
                        language: "{$Locale|replace:"_":"-"}",

                        authorization: function () {
                            loginOptions = {
                                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                                popup: true
                            };
                            var shopReturnUrl = "{url controller='FatchipFCSAmazonRegister' action='login'}";
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
            {if $fatchipFCSPaymentConfig.amazonLiveMode === 'Live'}
                src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'>
            {else}
                src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
            {/if}
        </script>
    </div>
    {/if}
{/block}