{* The main container for filling in the birthday field *}
<div id="fatchip-computop-payment-klarna-form-pay_later"></div>

{if $payment_mean.id == $form_data.payment}
    <script>
        console.log('call fatchipCTFetchAccessToken from pay_later');
        window.fatchipCTFetchAccessToken('pay_later');
    </script>
{/if}
