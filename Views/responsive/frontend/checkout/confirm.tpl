{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_error_messages'}
    {$smarty.block.parent}
    <div>
        {if $CTError}
            {include file="frontend/_includes/messages.tpl" content="{$CTError.CTErrorMessage}:{$CTError.CTErrorCode}" type="error" bold=false}
        {/if}
    </div>
{/block}