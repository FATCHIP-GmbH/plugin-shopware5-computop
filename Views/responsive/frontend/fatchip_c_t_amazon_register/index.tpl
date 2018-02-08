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
            /*display: none;*/
        }

        #fatchipCTWalletWidgetDiv {
            min-width: 300px;
            width: 100%;
            max-width: 550px;
            min-height: 228px;
            height: 240px;
            max-height: 400px;
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

        <!-- Hidden fiv to store amazonreferenceId -->

        <div id="fatchipCTWalletWidgetDiv" style="float:left;"></div>
    </div>
    <div id="fatchipCTAmazonInformation" hidden
         data-fatchipCTAmazonOrderReferenceId=''
         data-fatchipCTAmazonSODUrl='{url controller="FatchipCTAjax" action="ctSetOrderDetails" forceSecure}'
         data-fatchipCTAmazonGODUrl='{url controller="FatchipCTAjax" action="ctGetOrderDetails" forceSecure}'
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
                    //$('#fatchipCTAddressBookWidgetDiv').show();

                },
                onAddressSelect: function (orderReference) {

                    $("#fatchipCTAmazonInformation").trigger("onAmazonAddressSelect");
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

{block name="frontend_index_header_javascript_jquery"}
    {$smarty.block.parent}
    <script>



        // SW < 5.3: $(document).ready
        // -> put all js stuff into less compiler or use true SW Jquery Plugin
        //document.ready(function() {
/*
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
                '<input type="hidden" name="register[billing][birthday][day]" value="'+birthdayDay+'"/>'+
                '<input type="hidden" name="register[billing][birthday][month]" value="'+birthdayMonth+'"/>'+
                '<input type="hidden" name="register[billing][birthday][year]" value="'+birthdayYear+'"/>'+
                    // SW > 5.2 check this, shouldnt be neccessary ->Register::getPostData
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
            // needed for SW > 5.2 ??
            //CSRF.updateForms();
            frm.submit();

        });
        //});
        */
</script>
{/block}