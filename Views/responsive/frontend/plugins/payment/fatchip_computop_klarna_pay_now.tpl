{* The main container for filling in the birthday field *}
<div id="fatchip-firstcash-payment-klarna-form-pay_now"></div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipCTPaymentType = "pay_now";
    </script>
{/if}
