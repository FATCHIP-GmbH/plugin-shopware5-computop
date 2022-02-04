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
    <li class="steps--entry step--basket{if $sStepActive=='address'} is--active{/if}">
        <span class="icon">1</span>
        <span class="text"><span class="text--inner">{s name='AmazonPaymentDispatch' namespace='frontend/FatchipFCSPayment/translations'}Adresse und Zahlart{/s}</span></span>
    </li>
{/block}

{* Second Step - Payment *}
{block name='frontend_register_steps_register'}
    <li class="steps--entry step--register{if $sStepActive=='paymentShipping'} is--active{/if}">
        <span class="icon">2</span>
        <span class="text"><span class="text--inner">{s name='AmazonDispatch' namespace='frontend/FatchipFCSPayment/translations'}Versandart{/s}</span></span>
    </li>
{/block}

{* Third Step - Confirmation *}
{block name='frontend_register_steps_confirm'}
    <li class="steps--entry step--confirm{if $sStepActive=='finished'} is--active{/if}">
        <span class="icon">3</span>
        <span class="text"><span class="text--inner">{s name='AmazonCheckConfirm' namespace='frontend/FatchipFCSPayment/translations'}Prüfen und Bestellen{/s}</span></span>
    </li>
{/block}

{* Replace Register content with Amazon Widget SW 5.0 *}
{block name='frontend_register_index_registration'}
    <style type="text/css">
        #fatchipFCSAddressBookWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 500px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
        }
        #fatchipFCSWalletWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 500px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
            display: none;
        }
    </style>

    {* ToDo merge PHP and js error to one Block, beautify errors *}
    {* Error Messages Javascript *}
    <div id="AmazonErrors" style="display:none">
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
            </div>
        </div>
    </div>

    {* Error Messages php *}
    {if $errorMessage}
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
                {$errorMessage}
                <ul>
                    {foreach from=$errorFields item=error_field}
                        <li>{$error_field}</li>
                    {/foreach}
                </ul>
            </div>
        </div>
    {/if}

    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="width:100%;height: 100%; margin-top:5%;margin-bottom: 30px; padding-bottom: 4%;">

        <!-- <div id="debug">Amazon LGN:<BR>{$fatchipFCSResponse|var_dump}</div> -->
        <div id="fatchipFCSAddressBookWidgetDiv"  style="float:left;margin-right:5%;"></div>
        <div id="fatchipFCSWalletWidgetDiv" style="float:left;"></div>
    </div>
    <div id="fatchipFCSAmazonInformation" hidden
         data-fatchipFCSAmazonOrderReferenceId=''
         data-fatchipFCSAmazonSODUrl='{url controller="FatchipFCSAjax" action="ctSetOrderDetails" forceSecure}'
         data-fatchipFCSAmazonGODUrl='{url controller="FatchipFCSAjax" action="ctGetOrderDetails" forceSecure}'
         data-fatchipFCSAmazonShippingCheckUrl='{url controller="FatchipFCSAjax" action="ctIsShippingCountrySupported" forceSecure}'
         data-fatchipFCSAmazonRegisterUrl='{url controller="FatchipFCSAmazonRegister" action="saveRegister" forceSecure}?sTarget=FatchipFCSAmazonCheckout&sTargetAction=shippingPayment'
    ></div>

    {* Submit button *}
    <div class="register--action">
        <button onclick="$('#fatchipFCSAmazonInformation').trigger('fatchipFCSAmazonButtonClick');"
                id="fatchipFCSAmazonButton" class="btn is--primary is--large right is--icon-right" name="Submit"
                disabled="disabled">Weiter<i class="icon--arrow-right"></i>
        </button>
    </div>

    <script>
        window.onAmazonLoginReady = function() {
            amazon.Login.setClientId("{$fatchipFCSPaymentConfig.amazonClientId}");
        };

        window.onAmazonPaymentsReady = function () {
            new OffAmazonPayments.Widgets.AddressBook({
                sellerId: "{$fatchipFCSPaymentConfig.amazonSellerId}",
                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                onOrderReferenceCreate: function (orderReference) {
                    fatchipFCSAmazonReferenceId = orderReference.getAmazonOrderReferenceId();
                    var el = document.querySelector('#fatchipFCSAmazonInformation');
                    el.setAttribute('data-fatchipFCSAmazonOrderReferenceId', fatchipFCSAmazonReferenceId );
                    $("#fatchipFCSAmazonInformation").trigger("onAmazonOrderRef");
                    $('#fatchipFCSAddressBookWidgetDiv').show();
                    $('#fatchipFCSWalletWidgetDiv').show();

                },
                onAddressSelect: function (orderReference) {
                    $('#fatchipFCSAmazonButton').attr("disabled", "disabled");
                    // Button will be re-enabled after success in jsquery Plugin
                    $("#fatchipFCSAmazonInformation").trigger("onAmazonAddressSelect");
                },
                design: {
                    designMode: 'responsive'
                },
                onReady: function(billingAgreement) {
                },
                onError: function (error) {
                    console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                }
            }).bind("fatchipFCSAddressBookWidgetDiv");

            new OffAmazonPayments.Widgets.Wallet({
                sellerId: "{$fatchipFCSPaymentConfig.amazonSellerId}",
                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                onPaymentSelect: function (orderReference) {

                },
                design: {
                    designMode: 'responsive'
                },
                onError: function (error) {
                    console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                    // See "Handling Errors" for more information.
                }
            }).bind("fatchipFCSWalletWidgetDiv");
        };
    </script>
    <script async="async"
        {if $fatchipFCSPaymentConfig.amazonLiveMode === 'Live'}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'>
        {else}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
        {/if}
    </script>
{/block}