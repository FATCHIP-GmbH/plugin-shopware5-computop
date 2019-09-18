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

        window.klarnaAsyncCallback = function () {
            console.log('async callback');

            window.Klarna = Klarna;

            if (window.callFatchipCTLoadKlarna !== null) {
                // window.fatchipCTLoadKlarna was called, but window.Klarna object did not exist
                // so call window.fatchipCTLoadKlarna again
                window.fatchipCTLoadKlarna(window.callFatchipCTLoadKlarna);
            }
        };
    </script>
    <script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
    <script>
        window.fatchipCTLoadKlarna = function(payment) {
            console.log('fatchipCTLoadKlarna');

            if (!window.Klarna) {
                window.callFatchipCTLoadKlarna = payment;

                return;
            }

            window.callFatchipCTLoadKlarna = null;

            if ('{$FatchipCTKlarnaAccessToken}'.length === 0) {
                console.log('no token');
                return;
            }

            console.log('Klarna.Payments.init');

            window.Klarna.Payments.init({
                client_token: '{$FatchipCTKlarnaAccessToken}'
            });

            if (! window.Klarna) return;
            console.log(payment);
            Klarna.Payments.load({
                container: '#fatchip-computop-payment-klarna-form-' + payment,
                payment_method_category: payment
            }, function (res) {
                console.debug(res);
            });
        };
    </script>
    {$smarty.block.parent}
{/block}
