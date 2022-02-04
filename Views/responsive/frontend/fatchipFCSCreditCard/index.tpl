{extends file="parent:frontend/register/index.tpl"}

{* enable step box SW 5.0 *}
{block name='frontend_index_navigation_categories_top'}
    {include file="frontend/register/steps.tpl" sStepActive="address"}
{/block}

{* disable login box SW 5.0 *}
{block name='frontend_register_index_login'}
{/block}

{* disable sidebar SW 5.0 *}
{* Sidebar left *}
{block name='frontend_index_content_left'}
{/block}

{* disable advantage box SW 5.0 *}
{block name='frontend_register_index_advantages'}
{/block}

{* change register Steps to 1 Ihre Adresse, 2 Versandart, 3 Prüfen und Bestellen *}

{* First Step - Address *}
{block name='frontend_register_steps_basket'}
    <li class="steps--entry step--basket">
        <span class="icon">1</span>
        <span class="text"><span class="text--inner">{s name='AmazonPaymentDispatch' namespace='frontend/FatchipFCSPayment/translations'}Adresse und Zahlart{/s}</span></span>
    </li>
{/block}

{* Second Step - Payment *}
{block name='frontend_register_steps_register'}
    <li class="steps--entry step--register">
        <span class="icon">2</span>
        <span class="text"><span class="text--inner">{s name='AmazonDispatch' namespace='frontend/FatchipFCSPayment/translations'}Versandart{/s}</span></span>
    </li>
{/block}

{* Third Step - Confirmation *}
{block name='frontend_register_steps_confirm'}
    <li class="steps--entry step--confirm">
        <span class="icon">3</span>
        <span class="text"><span class="text--inner">{s name='AmazonCheckConfirm' namespace='frontend/FatchipFCSPayment/translations'}Prüfen und Bestellen{/s}</span></span>
    </li>
{/block}

{* fouth Step - Confirmation *}
{* Replace Register content with Amazon Widget SW 5.0 *}

{block name='frontend_register_index_registration'}
    {if $fatchipFCSURL}
        <div id="fatchipFCSCreditCardIFrame" hidden
             data-fatchipFCSUniqueID='{$fatchipFCSUniqueID}'
             data-fatchipFCSCreditcardIFrameUrl='{$fatchipFCSURL}'
             data-fatchipFCSErrorMessage='{$fatchipFCSErrorMessage}'
             data-fatchipFCSErrorCode='{$fatchipFCSErrorCode}'
        >
        </div>
    {else}
        <iframe src="{$fatchipFCSIframeURL}" style="width:100%; height:750px;" frameBorder="0"></iframe>
    {/if}
{/block}
