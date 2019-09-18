{* The main container for filling in the birthday field *}
<div id="fatchip-computop-payment-klarna-form-pay_over_time">
</div>

{if $payment_mean.id == $form_data.payment}
    <script>
        window.fatchipCTLoadKlarna('pay_over_time');
    </script>
{/if}
