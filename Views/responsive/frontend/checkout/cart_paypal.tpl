{extends file="parent:frontend/checkout/cart.tpl"}

{block name="frontend_checkout_actions_confirm"}
    {$smarty.block.parent}
    {if $sBasket.content}
        <div class="button--container">
            <a href="{url controller='FatchipCTPaypalExpress' action='gateway'}">
                <img src="https://www.paypal.com/{$Locale}/i/btn/btn_xpressCheckout.gif" />
            </a>
        </div>
    {/if}
{/block}
