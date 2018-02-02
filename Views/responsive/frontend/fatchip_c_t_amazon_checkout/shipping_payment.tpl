{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content content--confirm product--table" data-ajax-shipping-payment="true">
        {include file="frontend/fatchip_c_t_amazon_checkout/fatchip_shipping_payment_core.tpl"}
    </div>
{/block}