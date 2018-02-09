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
            display: none;
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
    </style>

    {* Error Messages Javascript*}
    <div id="AmazonErrors" style="display:none">
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
            </div>
        </div>
    </div>

    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="margin-top:2%;margin-bottom: 0px; padding-bottom: 1%;">

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
    ></div>

    {* Submit button *}
    <div class="register--action">
        <button onclick="$('#fatchipCTAmazonInformation').trigger('fatchipCTAmazonButtonClick');"
                id="fatchipCTAmazonButton" class="btn is--primary is--large right is--icon-right" name="Submit"
                >Weiter<i class="icon--arrow-right"></i>
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
                    console.log("entering onOrderRefCreate:");
                    fatchipCTAmazonReferenceId = orderReference.getAmazonOrderReferenceId();
                    console.log(fatchipCTAmazonReferenceId);
                    var el = document.querySelector('#fatchipCTAmazonInformation');
                    el.setAttribute('data-fatchipCTAmazonOrderReferenceId', fatchipCTAmazonReferenceId );
                    console.log("double Check Reference:");
                    console.log(el.getAttribute('data-fatchipCTAmazonOrderReferenceId'));
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
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
    </script>
{/block}