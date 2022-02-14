{extends file="parent:frontend/checkout/confirm.tpl"}

{block name="frontend_checkout_confirm_product_table"}
    {if $FatchipFirstCashEasyCreditInformation}
        <div class='panel has--border'>
            <div class="panel--title primary is--underline">
                {s name='easycreditConditions' namespace='frontend/checkout/CTEasycredit'}Easycredit Konditionen{/s}:
            </div>
            <div class="panel--body is--wide">
                <table>
                    <tr>
                        <td width="40%">{s name='easycreditAmount' namespace='frontend/FatchipFCSPayment/translations'}Kaufbetrag{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.bestellwert|number_format:2:",":"."}</td>
                    </tr>
                    <tr>
                        <td width="40%">+ {s name='easycreditInterest' namespace='frontend/FatchipFCSPayment/translations'}Zinsen{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.anfallendeZinsen|number_format:2:",":"."}</td>
                    </tr>
                    <tr>
                        <td width="40%"><b>= {s name='easycreditTotal' namespace='frontend/FatchipFCSPayment/translations'}Gesamtbetrag{/s}:</b></td>
                        <td width="40%"><b>{$FatchipFirstCashEasyCreditInformation.gesamtsumme|number_format:2:",":"."}</b></b></td>
                    </tr>
                    <tr>
                        <td width="40%">{s name='easycreditMonthlyRate' namespace='frontend/FatchipFCSPayment/translations'}Ihre monatliche Rate{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.betragRate|number_format:2:",":"."}</td>
                    </tr>
                    <tr>
                        <td width="40%">{s name='easycreditLastRate' namespace='frontend/FatchipFCSPayment/translations'}letzte Rate{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.betragLetzteRate|number_format:2:",":"."}</td>
                    </tr>
                    <tr>
                        <td width="40%">{s name='easycreditInterestRatePA' namespace='frontend/FatchipFCSPayment/translations'}Sollzinssatz p.a. fest für die gesamte Laufzeit{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.nominalzins|number_format:2:",":"."}%</td>
                    </tr>
                    <tr>
                        <td width="40%">{s name='easycreditInterestRateEffective' namespace='frontend/FatchipFCSPayment/translations'}effektiver Jahreszins{/s}:</td>
                        <td width="40%">{$FatchipFirstCashEasyCreditInformation.effektivzins|number_format:2:",":"."}%</td>
                    </tr>
                    <tr>
                        <td width="40%" colspan="2">{$FatchipFirstCashEasyCreditInformation.tilgungsplanText}</td>
                    </tr>
                    <tr>
                        <td width="40%"><a href="{$FatchipFirstCashEasyCreditInformation.urlVorvertraglicheInformationen}">
                                {s name='AmazonPaymentDispatch' namespace='frontend/FatchipFCSPayment/translations'}Vorvertragliche Informationen{/s}
                            </a>
                        </td>
                    </tr>

                </table>
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
        <form name="basket_change_quantity{$sBasketItem.id}" class="select-field" method="post"
              action="{url action='changeQuantity' sTargetAction=$sTargetAction}">
            <select name="sQuantity" data-auto-submit="false" disabled>
                {section name="i" start=$sBasketItem.minpurchase loop=$sBasketItem.maxpurchase+1 step=$sBasketItem.purchasesteps}
                    <option value="{$smarty.section.i.index}"
                            {if $smarty.section.i.index==$sBasketItem.quantity}selected="selected"{/if}>
                        {$smarty.section.i.index}
                    </option>
                {/section}
            </select>
            <input type="hidden" name="sArticle" value="{$sBasketItem.id}"/>
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