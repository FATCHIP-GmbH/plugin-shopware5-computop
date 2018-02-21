{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    {if $sBasket.content}
        <div class="button--container">
            <a href="{url controller='FatchipCTPaypalExpress' action='gateway'}">
                <img src="https://www.paypalobjects.com/{$Locale}/i/btn/btn_xpressCheckout.gif">
            </a>
        </div>
    {/if}
{/block}

