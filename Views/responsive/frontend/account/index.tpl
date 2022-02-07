{extends file="parent:frontend/account/index.tpl"}

{block name="frontend_account_index_payment_method_content"}
    {if $sUserData.additional.payment.name === 'fatchip_firstcash_lastschrift'}
        <div class="panel--body is--wide">
            <p>
                <strong>{$sUserData.additional.payment.description}</strong><br/>

                {if !$sUserData.additional.payment.esdactive && {config name="showEsd"}}
                    {s name="AccountInfoInstantDownloads"}{/s}
                {/if}
            </p>

            <strong>Bank:</strong>
            {$sUserData.additional.user.fatchipfcs_lastschriftbank}
            <br/>
            <strong>IBAN:</strong>
            {if $FatchipFCSPaymentIbanAnon == 1}
                {$sUserData.additional.user.fatchipfcs_lastschriftiban|truncate:18:"XXXXX":true}
            {else}
                {$sUserData.additional.user.fatchipfcs_lastschriftiban}
            {/if}
            <br/>
        </div>
     {else}
        {$smarty.block.parent}
    {/if}
{/block}
