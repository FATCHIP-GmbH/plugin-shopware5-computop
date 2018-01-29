{extends file="parent:frontend/register/index.tpl"}



{* enable step box *}
{block name='frontend_index_navigation_categories_top'}
        {include file="frontend/register/steps.tpl" sStepActive="address"}
{/block}

{* Replace Register content with Amazon Widget *}
{block name='frontend_register_index_registration'}
{/block}

{* disable login box *}
{block name='frontend_register_index_login'}
{/block}

{* disable advantage box *}
{block name='frontend_register_index_advantages'}
{/block}
