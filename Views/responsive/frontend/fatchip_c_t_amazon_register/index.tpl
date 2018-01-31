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
            display: none;
        {if $payoneAmazonReadOnly}
            displayMode: "Read";
        {/if}
        }
    </style>

    {* Error Messages Javascript*}
    <div id="amazonErrors" style="display:none">
        <div class="alert is--error is--rounded">
            <div class="alert--icon">
                <i class="icon--element icon--cross"></i>
            </div>
            <div id="AmazonErrorContent" class="alert--content">
            </div>
        </div>
    </div>

    <div id="amazonContentWrapper" class="content confirm--content content-main--inner" style="margin-top:2%;margin-bottom: 0px; padding-bottom: 1%;">

        <div id="debug">Amazon LGN:<BR>{$fatchipCTResponse|var_dump}</div>
        <!-- Place this code in your HTML where you would like the address widget to appear. -->
        <div id="fatchipCTAddressBookWidgetDiv"  style="float:left;margin-right:5%;"></div>
    </div>
    {* Submit button *}
    <div>
        <button type="submit" onClick="postAddressData()"  name="Submit">Weiter<i class="icon--arrow-right"></i></button>
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

                    // Computop "Step 14" is done now do the SOD call with ordereference
                    // afterwards display the addressbook widget
                    var call = '{url controller="FatchipCTAjax" action="ctSetOrderDetails" forceSecure}';
                    $.ajax({
                        url: call ,
                        type: 'post',
                        data: { referenceId: fatchipCTAmazonReferenceId}
                    })
                    .success(function(response){
                        var responseData = $.parseJSON(response);
                        if (responseData.status == "error"){
                            $('#amazonErrors').show();
                            $('#AmazonErrorContent').html(responseData.errormessage);
                        } else {
                            console.log("onAmazonPayReady SOD:");
                            console.log(responseData.data);
                            $('#fatchipCTAddressBookWidgetDiv').show();
                        }
                    })

                    // ToDO: only if above is successfull
                    // Commeted out, this is done anyway on initial onaddresselect
                    /* var call = '{url controller="FatchipCTAjax" action="ctGetOrderDetails" forceSecure}';
                    $.ajax({
                        url: call ,
                        type: 'post',
                        data: { referenceId: fatchipCTAmazonReferenceId}
                    })
                        .success(function(response){
                            var responseData = $.parseJSON(response);
                            if (responseData.status == "error"){
                                $('#amazonErrors').show();
                                $('#AmazonErrorContent').html(responseData.errormessage);
                            } else {
                                console.log("onAmazonPayReady GOD:");
                                console.log(responseData.data);
                                $('#fatchipCTAddressBookWidgetDiv').show();
                            }
                        })
                        */

                },
                onAddressSelect: function (orderReference) {

                    var call = '{url controller="FatchipCTAjax" action="ctGetOrderDetails" forceSecure}';
                    $.ajax({
                        url: call ,
                        type: 'post',
                        data: { referenceId: fatchipCTAmazonReferenceId}
                    })
                        .success(function(response){
                            var responseData = $.parseJSON(response);
                            if (responseData.status == "error"){
                                $('#amazonErrors').show();
                                $('#AmazonErrorContent').html(responseData.errormessage);
                            } else {
                                console.log("onAddressSelect GOD:");
                                console.log(responseData.data);
                                $('#fatchipCTAddressBookWidgetDiv').show();
                            }
                        })

                    var call = '{url controller="FatchipCTAjax" action="ctSetOrderDetails" forceSecure}';
                    $.ajax({
                        url: call ,
                        type: 'post',
                        data: { referenceId: fatchipCTAmazonReferenceId}
                    })
                        .success(function(response){
                            var responseData = $.parseJSON(response);
                            if (responseData.status == "error"){
                                $('#amazonErrors').show();
                                $('#AmazonErrorContent').html(responseData.errormessage);
                            } else {
                                console.log("onAddressSelect SOD:");
                                console.log(responseData.data);
                                $('#fatchipCTAddressBookWidgetDiv').show();
                            }
                        })


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

        function postAddressData()
        {
            $.post
            (
                "{url controller="Register" action="saveRegister" forceSecure}",

                {
                    'personal' {
                        'choices[]': [ "Jon", "Susan" ]
                    }

                }
            );

        }


    </script>
    <script async="async"
            src='https://static-eu.payments-amazon.com/OffAmazonPayments/de/sandbox/lpa/js/Widgets.js'>
    </script>
{/block}