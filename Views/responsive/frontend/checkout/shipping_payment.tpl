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
    <script>console.log('load klarna js');</script>
    <div id="fatchipCTKlarnaInformation" hidden
         data-accessToken="{$FatchipComputopKlarnaAccessToken}"
    ></div>
    {* TODO: only load if any klarna payment method is active*}
    <script>
        window.klarnaAsyncCallback = function () {
            console.log('async callback');

            window.Klarna = Klarna;

            if ('{$FatchipComputopKlarnaAccessToken}'.length === 0) {
                return;
            }

            Klarna.Payments.init({
                client_token: '{$FatchipComputopKlarnaAccessToken}'
            });
        };
    </script>
    <script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
    {$smarty.block.parent}
{/block}
