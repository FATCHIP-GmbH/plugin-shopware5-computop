{* The main container for filling in the birthday field *}
<div id="fatchip-firstcash-payment-klarna-form-direct_debit">
</div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipFCSPaymentType = "direct_debit";
    </script>
{/if}
