{* The main container for filling in the birthday field *}
<div id="fatchip-firstcash-payment-klarna-form-direct_bank_transfer">
</div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipCTPaymentType = "direct_bank_transfer";
    </script>
{/if}
