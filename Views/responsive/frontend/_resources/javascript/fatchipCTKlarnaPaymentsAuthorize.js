;(function ($, window) {
    'use strict';

    const data = $('#fatchipCTKlarnaInformation').data();

    function registerPlugin() {
        console.log('register');
        StateManager.addPlugin('#shippingPaymentForm', 'fatchipCTKlarnaPaymentsAuthorize');
    }

    function updatePlugin() {
        console.log('updatePlugin');
        StateManager.updatePlugin('#shippingPaymentForm', 'fatchipCTKlarnaPaymentsAuthorize');
    }

    $.plugin('fatchipCTKlarnaPaymentsAuthorize', {
        defaults: {},

        init: function () {
            console.log('init');
            const me = this;

            me.registerEventListeners();
        },

        update: function () {
            // const me = this;
        },

        destroy: function () {
            const me = this;

            me._destroy();
        },

        registerEventListeners: function () {
            const me = this;

            me._on(me.$el, 'submit', e => {
                e.preventDefault();
                me.authorize();
            });
        },

        authorize: function () {
            console.log('authorize');

            const authorizeData = {
                purchase_country: data['purchaseCountry'],
                purchase_currency: data['purchaseCurrency'],
                locale: data['locale'],
                billing_address: {
                    street_address: data['billingAddress-StreetAddress'],
                    city: data['billingAddress-City'],
                    given_name: data['billingAddress-GivenName'],
                    postal_code: data['billingAddress-PostalCode'],
                    family_name: data['billingAddress-FamilyName'],
                    email: data['billingAddress-Email'],
                    country: data['billingAddress-Country']
                }
            };

            console.log(authorizeData);

            window.Klarna.Payments.authorize({
                    payment_method_category: window.fatchipCTKlarnaPaymentType
                },
                authorizeData,
                res => {
                    console.log('authorize result');
                    console.log(res);
                });
        },

        // setConfirmOrder: function(confirmationFlow) {
        //     const me = this;
        //
        //     const requestUrl = me.data.fatchipctamazonscourl;
        //     const cartErrorUrl = me.data.fatchipctcarterrorurl;
        //
        //     $.ajax({
        //             url: requestUrl,
        //             success: function (data) {
        //                 confirmationFlow.success();
        //             },
        //             error: function (data) {
        //                 confirmationFlow.error();
        //
        //                 window.location.href = cartErrorUrl;
        //             },
        //             timeout: 30000
        //         }
        //     );
        // }
    });

    $(function () {
        console.log('anonymous function');

        if (data) {
            registerPlugin();

            // update on ajax changes
            $.subscribe('plugin/swShippingPayment/onInputChanged', function () {
                updatePlugin();
            });
        }
    });
})(jQuery, window);
