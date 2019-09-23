{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name='frontend_account_payment_error_messages'}
    <div>
        {if $CTError}
            {include file="frontend/_includes/messages.tpl" content="{$CTError.CTErrorMessage}:{$CTError.CTErrorCode}" type="error" bold=false}
        {/if}
    </div>
    {$smarty.block.parent}
{/block}

{block name="frontend_index_content"}
    {* TODO: only load if any klarna payment method is active*}
    <div id="fatchipCTKlarnaInformation" hidden data-access-token='{$FatchipCTKlarnaAccessToken}'></div>
    <!--suppress JSUnresolvedVariable -->
    <script>
        window.callFatchipCTLoadKlarna = null;

        window.klarnaAsyncCallback = () => {
            console.log('async callback');

            window.Klarna = Klarna;

            if (window.callFatchipCTLoadKlarna !== null) {
                // window.fatchipCTLoadKlarna was called, but window.Klarna object did not exist
                // so call window.fatchipCTLoadKlarna again
                console.log('recall fatchipCTFetchAccessToken');
                window.fatchipCTFetchAccessToken(window.callFatchipCTLoadKlarna);
            }
        };
    </script>
    <script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
    <script>
        window.fatchipCTLoadKlarna = (paymentName, accessToken) => {
            console.log('fatchipCTLoadKlarna');

            if (!window.Klarna) {
                window.callFatchipCTLoadKlarna = paymentName;

                return;
            }

            window.callFatchipCTLoadKlarna = null;

            if (!accessToken || accessToken.length === 0) {
                console.log('no token');
                return;
            }

            console.log('Klarna.Payments.init');

            window.Klarna.Payments.init({
                client_token: accessToken
            });

            const payTypeTranslations = {
                pay_now:
                    'pay_now',
                pay_later:
                    'pay_later',
                slice_it:
                    'pay_over_time'
            };

            if (! window.Klarna) return;
            console.log(paymentName);
            Klarna.Payments.load({
                container: '#fatchip-computop-payment-klarna-form-' + paymentName,
                payment_method_category: payTypeTranslations[paymentName]
            }, res => {
                console.debug(res);
            });
        };

        window.fatchipCTFetchAccessToken = paymentName => {
            const data = {
                paymentName: paymentName
            };

            let url = '{url controller="FatchipCTAjax" action="ctGetOrCreateAccessToken" forceSecure}';
            let delimiter = '?';

            for (let propertyKey in data) {
                url += delimiter + propertyKey + '=' + data[propertyKey];
                if (Object.keys(data) > 1) delimiter = '&';
            }

            fetch(url).then(
                response => {
                    return response.json();

                }).then(
                accessToken => {
                    window.fatchipCTLoadKlarna(paymentName, accessToken);
                });
        };
    </script>
    {$smarty.block.parent}
{/block}
