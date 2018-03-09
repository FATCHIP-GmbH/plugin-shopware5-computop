{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_product_table"}
    {if $FatchipComputopEasyCreditInformation}
        <div class='panel has--border'>
            <div class="panel--title primary is--underline">
                Easycredit Konditionen:
            </div>
            <div class="panel--body is--wide">
                Bestellwert: {$FatchipComputopEasyCreditInformation.bestellwert|number_format:2:",":"."} <BR>
                + Zinsen: {$FatchipComputopEasyCreditInformation.anfallendeZinsen|number_format:2:",":"."} <BR>
                = Gesamtbetrag: {$FatchipComputopEasyCreditInformation.gesamtsumme|number_format:2:",":"."} <BR>
                Ihre monatliche Rate: {$FatchipComputopEasyCreditInformation.betragRate|number_format:2:",":"."} <BR>
                letzte Rate: {$FatchipComputopEasyCreditInformation.betragLetzteRate|number_format:2:",":"."} <BR>
                Sollzinssatz p.a. fest für die gesamte Laufzeit: {$FatchipComputopEasyCreditInformation.nominalzins|number_format:2:",":"."}% <BR>
                effektiver Jahreszins: {$FatchipComputopEasyCreditInformation.effektivzins|number_format:2:",":"."}% <BR>
                Tilgunsplan: {$FatchipComputopEasyCreditInformation.tilgungsplanText} <BR>
                <a href="{$FatchipComputopEasyCreditInformation.urlVorvertraglicheInformationen}">Vorvertragliche Informationen</a>
            </div>
        </div>
        <div class="clear">&nbsp;</div>
    {/if}
    {$smarty.block.parent}
{/block}


{* disable changing quantities and delete basket items on confirm page *}
{* checked in
   - SW 5.0
   - SW 5.1
   - SW 5.2
   - SW 5.3 - checked
   - SW 5.4
*}
{block name='frontend_checkout_cart_item_quantity_selection'}
    {if !$sBasketItem.additional_details.laststock || ($sBasketItem.additional_details.laststock && $sBasketItem.additional_details.instock > 0)}
        <form name="basket_change_quantity{$sBasketItem.id}" class="select-field" method="post" action="{url action='changeQuantity' sTargetAction=$sTargetAction}">
            <select name="sQuantity" data-auto-submit="false" disabled>
                {section name="i" start=$sBasketItem.minpurchase loop=$sBasketItem.maxpurchase+1 step=$sBasketItem.purchasesteps}
                    <option value="{$smarty.section.i.index}" {if $smarty.section.i.index==$sBasketItem.quantity}selected="selected"{/if}>
                        {$smarty.section.i.index}
                    </option>
                {/section}
            </select>
            <input type="hidden" name="sArticle" value="{$sBasketItem.id}" />
        </form>
    {else}
        {s name="CartColumnQuantityEmpty" namespace="frontend/checkout/cart_item"}{/s}
    {/if}
{/block}

{* Remove product from basket *}
{block name='frontend_checkout_cart_item_delete_article'}
    <div class="panel--td column--actions">
        <form action="{url action='deleteArticle' sDelete=$sBasketItem.id sTargetAction=$sTargetAction}"
              method="post">
            <button type="submit" disabled class="btn is--small column--actions-link"
                    title="{"{s name='CartItemLinkDelete'}{/s}"|escape}">
                <i class="icon--cross"></i>
            </button>
        </form>
    </div>
{/block}