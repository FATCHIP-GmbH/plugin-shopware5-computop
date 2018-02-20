{extends file="parent:frontend/checkout/ajax_cart.tpl"}

{block name='frontend_checkout_ajax_cart_button_container'}
    {$smarty.block.parent}
    <div class="button--container">
        <a href="{url controller='FatchipCTPaypalExpress' action='gateway'}">
            <img src="https://www.paypal.com/de_DE/i/btn/btn_xpressCheckout.gif" />
        </a>
    </div>
{/block}

