{extends file="parent:frontend/register/index.tpl"}

{* enable step box *}
{block name='frontend_index_navigation_categories_top'}
        {include file="frontend/register/steps.tpl" sStepActive="address"}
{/block}

{* disable login box *}
{block name='frontend_register_index_login'}
{/block}

{* disable advantage box *}
{block name='frontend_register_index_advantages'}
{/block}

{* Replace Register content with Amazon Widget *}
{block name='frontend_register_index_registration'}

    <style type="text/css">

        /*
        Please include the min-width, max-width, min-height
        and max-height if you plan to use a relative CSS unit
        measurement to make sure the widget renders in the
        optimal size allowed.
        */
        #fatchipCTAddressBookWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 550px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
        {if $payoneAmazonReadOnly}
            displayMode: "Read";
        {/if}
        }
    </style>

    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="margin-top:2%;margin-bottom: 0px; padding-bottom: 1%;">
        <!-- Place this code in your HTML where you would like the address widget to appear. -->
        <div id="fatchipCTAddressBookWidgetDiv"  style="float:left;margin-right:5%;"></div>
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
                    console.log("entering onOrderRefCreate");
                    fatchipCTAmazonReferenceId = orderReference.getAmazonOrderReferenceId();
                },
                onAddressSelect: function (orderReference) {
                },
                design: {
                    designMode: 'responsive'
                },
                onReady: function(billingAgreement) {
                    var billingAgreementId = billingAgreement.
                    getAmazonBillingAgreementId();
                },
                onError: function (error) {
                    // Your error handling code.
                    // During development you can use the following
                    // code to view error messages:
                    // console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                    // See "Handling Errors" for more information.
                    console.log(error.getErrorCode() + ': ' + error.getErrorMessage());
                }
            }).bind("fatchipCTAddressBookWidgetDiv");
        };
    </script>
    <script async="async"
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
    </script>
{/block}