{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    {if $sBasket.content}
        <div class="button--container">
            <a href="{url controller='FatchipCTPaypalExpress' action='gateway'}">
                <img src="{$fatchipCTPaymentPaypalButtonUrl}">
            </a>
        </div>
    {/if}
{/block}

