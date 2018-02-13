{extends file="parent:frontend/checkout/finish.tpl"}

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}
    <script async="async"
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
    </script>
    <script>
        window.onAmazonLoginReady = function () {
            amazon.Login.logout();
            console.log("Amazon Logout");
        };
    </script>
{/block}