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
        <input type="button"  id="formSubmit">Weiter<i class="icon--arrow-right"></i></input>
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

{block name="frontend_index_header_javascript_jquery" append}
    <script>
        // SW < 5.3: $(document).ready
        // -> put all js stuff into less compiler or use true SW Jquery Plugin
        document.asyncReady(function() {

        $(document).ready("#formSubmit").click(function() {
            var customerType="private";
            var salutation = 'mr';
            var firstname = 'Stefan';
            var lastname = 'Müller';
            var email = 'stefan.mueller@fatchip.de';
            var phone = '012345678';
            var birthdayDay = '12';
            var birthdayMonth = '12';
            var birthdayYear = '1977';
            var street = 'Speyerer Str. 13';
            var zip = '10779';
            var city = 'Berlin';
            var countryID = '2';
            var differentShipping = '1';
            var salutation2 = 'mr';
            var firstname2 = 'Liefer';
            var lastname2 = 'LieferNN';
            var company2 = '';
            var department2 = '';
            var street2 = 'Liefer Str. 13';
            var zip2 = '14167';
            var city2 = 'Lieferstadt';
            var countryShippingID = '2';


            var frm = $('<form>', {
                'action': "{url controller='FatchipCTAmazonRegister' action='saveRegister' forceSecure}?sTarget=FatchipCTAmazonCheckout&sTargetAction=shippingPayment",
                'method': 'post'
            });

            // SW 5.0 - 5.1
            frm.append(
                '<input type="hidden" name="register[personal][customer_type]" value="'+customerType+'"/>'+
                '<input type="hidden" name="register[personal][salutation]" value="'+salutation+'"/>'+
                '<input type="hidden" name="register[personal][firstname]" value="'+firstname+'"/>'+
                '<input type="hidden" name="register[personal][lastname]" value="'+lastname+'"/>'+
                // SW > 5.2
                '<input type="hidden" name="register[personal][accountmode]" value="1"/>'+

                '<input type="hidden" name="register[personal][skipLogin]" value="1"/>'+
                '<input type="hidden" name="register[personal][email]" value="'+email+'"/>'+
                '<input type="hidden" name="register[personal][emailConfirmation]" value="'+email+'"/>'+
                '<input type="hidden" name="register[personal][phone]" value="'+phone+'"/>'+
                    // has to be removed for > SW 5.2 ??
                '<input type="hidden" name="register[personal][birthday]" value="'+birthdayDay+'"/>'+

                '<input type="hidden" name="register[personal][birthmonth]" value="'+birthdayMonth+'"/>'+
                '<input type="hidden" name="register[personal][birthyear]" value="'+birthdayYear+'"/>'+
                    // SW > 5.2
                '<input type="hidden" name="register[personal][birthday][day]" value="'+birthdayDay+'"/>'+
                '<input type="hidden" name="register[personal][birthday][month]" value="'+birthdayMonth+'"/>'+
                '<input type="hidden" name="register[personal][birthday][year]" value="'+birthdayYear+'"/>'+

                '<input type="hidden" name="register[billing][street]" value="'+street+'"/>'+
                '<input type="hidden" name="register[billing][city]" value="'+city+'"/>'+
                '<input type="hidden" name="register[billing][zipcode]" value="'+zip+'"/>'+
                '<input type="hidden" name="register[billing][country]" value="'+countryID+'"/>'+
                '<input type="hidden" name="register[billing][shippingAddress]" value="'+differentShipping+'"/>'+
                '<input type="hidden" name="register[billing][customer_type]" value="'+customerType+'"/>'+
                    // SW > 5.2
                '<input type="hidden" name="register[billing][accountmode]" value="1"/>'+
                '<input type="hidden" name="register[billing][phone]" value="'+phone+'"/>'+
                '<input type="hidden" name="register[billing][birthday][day] value="'+birthdayDay+'"/>'+
                '<input type="hidden" name="register[billing][birthday][month]" value="'+birthdayMonth+'"/>'+
                '<input type="hidden" name="register[billing][birthday][year]" value="'+birthdayYear+'"/>'+
                '<input type="hidden" name="register[billing][additional][customer_type]" value="'+customerType+'"/>'+

                '<input type="hidden" name="register[shipping][salutation]" value="'+salutation2+'"/>'+
                '<input type="hidden" name="register[shipping][firstname]" value="'+firstname2+'"/>'+
                '<input type="hidden" name="register[shipping][lastname]" value="'+lastname2+'"/>'+
                '<input type="hidden" name="register[shipping][company]" value="'+company2+'"/>'+
                '<input type="hidden" name="register[shipping][department]" value="'+department2+'"/>'+
                '<input type="hidden" name="register[shipping][street]" value="'+street2+'"/>'+
                '<input type="hidden" name="register[shipping][city]" value="'+city2+'"/>'+
                '<input type="hidden" name="register[shipping][zipcode]" value="'+zip2+'"/>'+
                '<input type="hidden" name="register[shipping][country]" value="'+countryShippingID+'"/>'+
                '<input type="hidden" name="register[shipping][phone]" value="'+phone+'"/>'
            );

            $(document.body).append(frm);
            CSRF.updateForms();
            frm.submit();

        });
        });
</script>
{/block}