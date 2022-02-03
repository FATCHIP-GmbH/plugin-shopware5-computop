{* The main container for filling in the birthday field *}
<div id="fatchip-firstcash-payment-klarna-form-slice_it">
</div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipCTPaymentType = "slice_it";
    </script>
{/if}
