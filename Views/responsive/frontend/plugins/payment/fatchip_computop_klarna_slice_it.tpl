{* The main container for filling in the birthday field *}
<div id="fatchip-computop-payment-klarna-form-slice_it">
</div>

{if $payment_mean.id == $form_data.payment}
    <script>
        console.log('call fatchipCTFetchAccessToken from slice_it');
        window.fatchipCTFetchAccessToken('slice_it');
    </script>
{/if}
