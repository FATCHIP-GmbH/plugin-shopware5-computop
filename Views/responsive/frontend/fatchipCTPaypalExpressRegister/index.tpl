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
    <div id="fatchipCTPaypalExpressInformation" hidden
         data-fatchipCTPaypalExpressRegisterUrl='{url controller="FatchipCTPaypalExpressRegister" action="saveRegister" forceSecure}?sTarget=FatchipCTPaypalExpressCheckout&sTargetAction=shippingPayment'
         data-shippingFirstname='{$fatchipAddrShippingFirstname}'
         data-shippingLastname='{$fatchipAddrShippingLastname}'
         data-firstname='{$fatchipAddrFirstName}'
         data-lastname='{$fatchipAddrLastName}'
         data-email='{$fatchipCTResponse->getEmail()}'
         data-street='{$fatchipCTResponse->getAddrStreet()}'
         data-additionalAddressLine1='{$fatchipCTResponse->getAddrStreet2()}'
         data-zip='{$fatchipCTResponse->getAddrZip()}'
         data-city='{$fatchipCTResponse->getAddrCity()}'
         data-countryCodeBillingID='{$fatchipAddrCountryCodeID}'
         // never set in computop response, but may be required for shop registration
         data-phone='{$fatchipAddrPhone}'
         data-birthday='{$fatchipAddrBirthday}'
         data-birthdayDay='{$fatchipAddrBirthdayDay}'
         data-birthdayMonth='{$fatchipAddrBirthdayMonth}'
         data-birthdayYear='{$fatchipAddrBirthdayYear}'
         data-birthdaySingleField = {config name="birthdaySingleField"}
         data-showBirthday = {config name="showBirthdayField"}
         data-requireBirthday = {config name="requireBirthdayField"}
    >
    </div>
{/block}