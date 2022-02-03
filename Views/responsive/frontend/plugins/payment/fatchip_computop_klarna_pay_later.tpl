{* The main container for filling in the birthday field *}
<div id="fatchip-firstcash-payment-klarna-form-pay_later"></div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipCTPaymentType = 'pay_later';
    </script>
{/if}
