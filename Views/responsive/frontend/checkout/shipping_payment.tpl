{extends file="parent:frontend/checkout/shipping_payment.tpl"}

{block name='frontend_account_payment_error_messages'}
    <div>
        {if $FCSError}
            {include file="frontend/_includes/messages.tpl" content="{$FCSError.CTErrorMessage}:{$FCSError.CTErrorCode}" type="error" bold=false}
        {/if}
    </div>
    {$smarty.block.parent}
{/block}

{block name="frontend_index_content"}
    {* TODO: only load if any klarna payment method is active*}
    <div id="fatchipFCSKlarnaInformation" hidden
         data-payment-type="{$paymentType}"
         data-billing-address--street-address="{$billingAddressStreetAddress}"
         data-billing-address--city="{$billingAddressCity}"
         data-billing-address--given-name="{$billingAddressGivenName}"
         data-billing-address--postal-code="{$billingAddressPostalCode}"
         data-billing-address--family-name="{$billingAddressFamilyName}"
         data-billing-address--email="{$billingAddressEmail}"
         data-purchase-currency="{$purchaseCurrency}"
         data-locale="{$locale}"
         data-billing-address--country="{$billingAddressCountry}"
         data-get-access-token--URL="{url controller="FatchipFCSKlarnaPayments" action="getAccessToken" forceSecure}"
         data-store-authorization-token--URL="{url controller="FatchipFCSKlarnaPayments" action="storeAuthorizationToken" forceSecure}"
    ></div>
    <script>
        window.fatchipFCSKlarnaPaymentType = null;

        window.klarnaAsyncCallback = () => {
            window.Klarna = Klarna;
        };
    </script>
    <script src="https://x.klarnacdn.net/kp/lib/v1/api.js" async></script>
    <script>
    </script>
    {$smarty.block.parent}
{/block}
