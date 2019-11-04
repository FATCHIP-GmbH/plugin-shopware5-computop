{extends file="parent:frontend/checkout/cart.tpl"}

{block name='frontend_checkout_cart_panel'}
    {if $amznError == 'Declined'}
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
                {s name="AmazonErrorDeclined" namespace="frontend/checkout/CTErrors"}Es ist ein Fehler aufgetreten: Die Zahlung wurde seitens Amazon zurückgewiesen.{/s}
            </div>
        </div>
    {elseif $amznError == 'SCO'}
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
                {s name="AmazonErrorSCO" namespace="frontend/checkout/CTErrors"}Es ist ein Fehler aufgetreten: Authentifizierung nicht erfolgreich.{/s}
            </div>
        </div>
    {elseif $amznError}
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
                {s name="AmazonErrorGeneric" namespace="frontend/checkout/CTErrors"}Es ist ein Fehler aufgetreten: Die Zahlung konnte nicht verarbeitet werden.{/s}
            </div>
        </div>
    {/if}

    {$smarty.block.parent}
{/block}

{block name="frontend_checkout_actions_confirm"}
    {$smarty.block.parent}

    {if $sBasket.content}
    <div class="button--container right">
        <div id="LoginWithAmazon">
            <!-- RemoveMe: <BR>
            smarty.server.REQUEST_SCHEME: {$smarty.server.REQUEST_SCHEME}<BR>
            smarty.server.HTTP_REFERER: {$smarty.server.HTTP_REFERER}<BR>
            -->
        </div>
        <div class="clear"></div>
        <script>
            // Todo clear old credentials find  a better handling
            window.onAmazonLoginReady = function () {
                {if $performAmazonLogout}
                amazon.Login.logout();
                window.location = '{url controller="checkout" action="cart" amznError="$amznError"}';
                {/if}

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
                            var shopReturnUrl = "{url controller='FatchipCTAmazonRegister' action='login'}";

                            {if $smarty.server.REQUEST_SCHEME === 'https' && $smarty.server.HTTP_REFERER|strpos:'https://'=== 0}
                            // Do nothing
                            {else}
                            // replace with https return Url
                            shopReturnUrl = shopReturnUrl.replace("http://", "https://");
                            {/if}

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
            {if $fatchipCTPaymentConfig.amazonLiveMode === 'Live'}
                src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'>
            {else}
                src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
            {/if}
        </script>
    </div>
    {/if}
{/block}
