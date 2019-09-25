{* The main container for filling in the birthday field *}
<div id="fatchip-computop-payment-klarna-form-pay_now"></div>

{if $payment_mean.id == $form_data.payment}
    <script>
        console.log('call fatchipCTFetchAccessToken from pay_now');
        window.fatchipCTFetchAccessToken('pay_now');
    </script>
{/if}
