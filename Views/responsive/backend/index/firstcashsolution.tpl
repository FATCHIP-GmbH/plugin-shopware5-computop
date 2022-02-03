{extends file="parent:backend/index/header.tpl"}
{block name="backend/base/header/css"}
    {$smarty.block.parent}
    <style type="text/css">
        .computop-icon {
            background:url({link file="backend/_resources/images/firstcashsolutionicon.png"}) no-repeat 0 0 !important;
        }
    </style>
{/block}
