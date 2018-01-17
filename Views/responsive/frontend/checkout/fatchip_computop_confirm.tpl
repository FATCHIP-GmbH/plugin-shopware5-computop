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