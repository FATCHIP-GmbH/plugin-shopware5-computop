{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_actions_confirm"}
    {$smarty.block.parent}
    {if $sBasket.content}
        <div class="button--container">
            <a href="{url controller='FatchipFCSPaypalExpress' action='gateway'}">
                <img src="{$fatchipFCSPaymentPaypalButtonUrl}">
            </a>
        </div>
    {/if}
{/block}
