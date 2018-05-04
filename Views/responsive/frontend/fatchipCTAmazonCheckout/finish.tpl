{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}
    <script async="async"
        {if $fatchipCTPaymentConfig.amazonLiveMode === 'Live'}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'>
        {else}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
        {/if}
    </script>
    <script>
        window.onAmazonLoginReady = function () {
            amazon.Login.logout();
        };
    </script>
{/block}