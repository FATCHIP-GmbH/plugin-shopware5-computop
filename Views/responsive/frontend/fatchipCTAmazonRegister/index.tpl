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
        <span class="text"><span class="text--inner">{s name='AmazonPaymentDispatch' namespace='frontend/FatchipCTPayment/translations'}Adresse und Zahlart{/s}</span></span>
    </li>
{/block}

{* Second Step - Payment *}
{block name='frontend_register_steps_register'}
    <li class="steps--entry step--register{if $sStepActive=='paymentShipping'} is--active{/if}">
        <span class="icon">2</span>
        <span class="text"><span class="text--inner">{s name='AmazonDispatch' namespace='frontend/FatchipCTPayment/translations'}Versandart{/s}</span></span>
    </li>
{/block}

{* Third Step - Confirmation *}
{block name='frontend_register_steps_confirm'}
    <li class="steps--entry step--confirm{if $sStepActive=='finished'} is--active{/if}">
        <span class="icon">3</span>
        <span class="text"><span class="text--inner">{s name='AmazonCheckConfirm' namespace='frontend/FatchipCTPayment/translations'}Prüfen und Bestellen{/s}</span></span>
    </li>
{/block}

{* Replace Register content with Amazon Widget SW 5.0 *}
{block name='frontend_register_index_registration'}
    <style type="text/css">
        #fatchipCTAddressBookWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 500px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
        }
        #fatchipCTWalletWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 500px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
            display: none;
        }
        #AmazonErrors {
            display: none;
            padding-left: 50px;
        }
        #RegistrationErrors {
            padding-left: 50px;
        }
    </style>

    <div id="AmazonErrors">
        <div class="alert content is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
            </div>
        </div>
    </div>

    {* Error Messages php *}
    {if $errorMessage}
        <div id="RegistrationErrors">
            <div class="alert content is--error is--rounded">
                <div class="alert--icon">
                    <i class="icon--element icon--cross"></i>
                </div>
                <div id="AmazonErrorContent" class="alert--content">
                    {$errorMessage}
                    <ul class="alert--list">
                        {foreach from=$errorFields item=error_field}
                            <li class="list--entry">{$error_field}</li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div>
    {/if}

    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="width:100%;height: 100%; margin-top:5%;margin-bottom: 30px; padding-bottom: 4%;">

        <!-- <div id="debug">Amazon LGN:<BR>{$fatchipCTResponse|var_dump}</div> -->
        <div id="fatchipCTAddressBookWidgetDiv"  style="float:left;margin-right:5%;"></div>
        <div id="fatchipCTWalletWidgetDiv" style="float:left;"></div>
    </div>
    <div id="fatchipCTAmazonInformation" hidden
         data-fatchipCTAmazonOrderReferenceId=''
         data-fatchipCTAmazonSODUrl='{url controller="FatchipCTAjax" action="ctSetOrderDetails" forceSecure}'
         data-fatchipCTAmazonGODUrl='{url controller="FatchipCTAjax" action="ctGetOrderDetails" forceSecure}'
         data-fatchipCTAmazonShippingCheckUrl='{url controller="FatchipCTAjax" action="ctIsShippingCountrySupported" forceSecure}'
         data-fatchipCTAmazonRegisterUrl='{url controller="FatchipCTAmazonRegister" action="saveRegister" forceSecure}?sTarget=FatchipCTAmazonCheckout&sTargetAction=shippingPayment'
         data-fatchipCTAmazonisPhoneMandatory = '{$fatchipCTAmazonisPhoneMandatory}'
         data-fatchipCTAmazonisBirthdayMandatory = '{$fatchipCTAmazonisBirthdayMandatory}'
         data-fatchipCTAmazonBirthdaySingleField = '{config name="birthdaySingleField"}'
    ></div>

    {* Submit button *}
    <div class="register--action">
        <button onclick="$('#fatchipCTAmazonInformation').trigger('fatchipCTAmazonButtonClick');"
                id="fatchipCTAmazonButton" class="btn is--primary is--large right is--icon-right" name="Submit"
                disabled="disabled">Weiter<i class="icon--arrow-right"></i>
        </button>
    </div>

    <script>
        window.onAmazonLoginReady = function() {
            amazon.Login.setClientId("{$fatchipCTPaymentConfig.amazonClientId}");
        };

        window.onAmazonPaymentsReady = function () {
            new OffAmazonPayments.Widgets.AddressBook({
                sellerId: "{$fatchipCTPaymentConfig.amazonSellerId}",
                scope: 'profile payments:widget payments:shipping_address payments:billing_address',
                onOrderReferenceCreate: function (orderReference) {
                    fatchipCTAmazonReferenceId = orderReference.getAmazonOrderReferenceId();
                    var el = document.querySelector('#fatchipCTAmazonInformation');
                    el.setAttribute('data-fatchipCTAmazonOrderReferenceId', fatchipCTAmazonReferenceId );
                    $("#fatchipCTAmazonInformation").trigger("onAmazonOrderRef");
                    $('#fatchipCTAddressBookWidgetDiv').show();
                    $('#fatchipCTWalletWidgetDiv').show();

                },
                onAddressSelect: function (orderReference) {
                    $('#fatchipCTAmazonButton').attr("disabled", "disabled");
                    // Button will be re-enabled after success in jsquery Plugin
                    $("#fatchipCTAmazonInformation").trigger("onAmazonAddressSelect");
                },
                design: {
                    designMode: 'responsive'
                },
                onReady: function(billingAgreement) {
                },
                onError: function (error) {
                    console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                }
            }).bind("fatchipCTAddressBookWidgetDiv");

            new OffAmazonPayments.Widgets.Wallet({
                sellerId: "{$fatchipCTPaymentConfig.amazonSellerId}",
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
            }).bind("fatchipCTWalletWidgetDiv");
        };
    </script>
    <script async="async"
        {if $fatchipCTPaymentConfig.amazonLiveMode === 'Live'}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/lpa/js/Widgets.js'
        {else}
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'
        {/if}
        >
    </script>
{/block}