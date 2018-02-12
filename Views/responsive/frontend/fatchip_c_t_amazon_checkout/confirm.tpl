{extends file="parent:frontend/checkout/confirm.tpl"}

{* Disable BillingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_billing_address_actions"}
{/block}

{* Disable ShippingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_shipping_address_actions"}
{/block}

{* Disable PaymentMean Selection Action Buttons *}
{block name='frontend_checkout_confirm_left_payment_method_actions'}
{/block}

