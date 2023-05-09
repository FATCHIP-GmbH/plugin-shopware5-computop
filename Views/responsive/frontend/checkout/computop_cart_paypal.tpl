{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_actions_confirm"}
    {$smarty.block.parent}
    {if $sBasket.content}
        <div class="button--container">
            <a href="{url controller='FatchipCTPaypalExpress' action='gateway' shipping=$sShippingcosts paymentId=$sPayment['id'] dispatch=$sDispatch['id']}">
                <img src="{$fatchipCTPaymentPaypalButtonUrl}">
            </a>
        </div>
    {/if}
{/block}
