{extends file="parent:frontend/register/index.tpl"}

{* enable step box SW 5.0 *}
{block name='frontend_index_navigation_categories_top'}
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
{/block}

{* Second Step - Payment *}
{block name='frontend_register_steps_register'}
{/block}

{* Third Step - Confirmation *}
{block name='frontend_register_steps_confirm'}
{/block}

{* Replace Register content with Amazon Widget SW 5.0 *}
{block name='frontend_register_index_registration'}
    <div id="fatchipFCSPaypalExpressInformation" hidden
         data-fatchipFCSPaypalExpressRegisterUrl='{url controller="FatchipFCSPaypalExpressRegister" action="saveRegister" forceSecure}?sTarget=FatchipFCSPaypalExpressCheckout&sTargetAction=shippingPayment'
         data-firstname='{$fatchipAddrFirstName}'
         data-lastname='{$fatchipAddrLastName}'
         data-email='{$fatchipFCSResponse->getEmail()}'
         data-phone='0' // never set, as precaution use 0 to defeat sw field validation
         data-street='{$fatchipFCSResponse->getAddrStreet()}'
         data-zip='{$fatchipFCSResponse->getAddrZip()}'
         data-city='{$fatchipFCSResponse->getAddrCity()}'
         data-countryCodeBillingID='{$fatchipAddrCountryCodeID}'
    ></div>
{/block}