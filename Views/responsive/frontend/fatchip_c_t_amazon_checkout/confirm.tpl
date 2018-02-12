{extends file="parent:frontend/checkout/confirm.tpl"}

{* Disable BillingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_billing_address_actions"}
{/block}

{* Disable ShippingAddress Action Buttons *}
{block name="frontend_checkout_confirm_left_shipping_address_actions"}
{/block}

{block name='frontend_checkout_confirm_left_payment_method_headline'}
    <div class="panel--title is--underline payment--title">
        {s name="ConfirmHeaderPaymentShipping" namespace="frontend/checkout/confirm_left"}{/s}
    </div>
{/block}

{* Change PaymentMean Selection Action Button to FatchipCTAmazonCheckout Controller *}
{block name='frontend_checkout_confirm_left_payment_method_actions'}
    <div class="panel--actions payment--actions">
        <a href="{url controller=FatchipCTAmazoncheckout action=shippingPayment sTarget=checkout}" class="btn is--small btn--change-payment">
            {s name="ConfirmLinkChangePayment" namespace="frontend/checkout/confirm_left"}{/s}
        </a>
    </div>
{/block}

