{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_error_messages'}
    {$smarty.block.parent}
    <div>
        {if $CTError}
            {include file="frontend/_includes/messages.tpl" content="{$CTError.CTErrorMessage}:{$CTError.CTErrorCode}" type="error" bold=false}
        {/if}
    </div>
{/block}

{block name='frontend_checkout_confirm_left_payment_method'}
    {$smarty.block.parent}
    {if $sUserData.additional.payment.name === 'fatchip_computop_lastschrift'}
        <p class="payment--method-info">
            <strong class="payment--title">Bank:</strong>
            <span class="payment--description">{$sUserData.additional.user.fatchipct_lastschriftbank}</span>
        </p>
        <p class="payment--method-info">
            <strong class="payment--title">IBAN:</strong>
            {if $FatchipCTPaymentIbanAnon == 1}
                <span class="payment--description">{$sUserData.additional.user.fatchipct_lastschriftiban|truncate:18:"XXXXX":true}</span>
            {else}
                <span class="payment--description">{$sUserData.additional.user.fatchipct_lastschriftiban}</span>
            {/if}
        </p>
    {/if}
{/block}