;(function ($, window) {
    'use strict';

    const data = $('#fatchipCTKlarnaInformation').data();

    let pluginRegistered = false;

    // no Klarna payment activated
    if (!data) {
        return;
    }

    reset();

// update on ajax changes
    $.subscribe('plugin/swShippingPayment/onInputChanged', function () {
        reset();
    });

    function reset() {
        if (!window.fatchipCTPaymentType) {
            destroyPlugin();

            return;
        }

        if (pluginRegistered) {
            updatePlugin();
        } else {
            registerPlugin();
            pluginRegistered = true;
        }

        fatchipCTFetchAccessToken(window.fatchipCTPaymentType);

        delete window.fatchipCTPaymentType;
    }

    function fatchipCTLoadKlarna(paymentType, accessToken) {

        if (!accessToken || accessToken.length === 0) {
            console.log('no token');
            return;
        }

        window.Klarna.Payments.init({
            client_token: accessToken
        });

        const payTypeTranslations = {
            pay_now:
                'pay_now',
            pay_later:
                'pay_later',
            slice_it:
                'pay_over_time'
        };

        window.fatchipCTKlarnaPaymentType = payTypeTranslations[paymentType];

        if (!window.Klarna) {
            return;
        }

        Klarna.Payments.load({
            container: '#fatchip-computop-payment-klarna-form-' + paymentType,
            payment_method_category: payTypeTranslations[paymentType]
        }, function (res) {
        });
    }

    function fatchipCTFetchAccessToken(paymentType) {
        const url = data['getAccessToken-Url'];
        const parameter = {paymentType: paymentType};

        $.ajax({method: "POST", url: url, data: parameter}).done(function (response) {
            fatchipCTLoadKlarna(paymentType, JSON.parse(response));
        });
    }

    function registerPlugin() {
        StateManager.addPlugin('#shippingPaymentForm', 'fatchipCTKlarnaPayments', null, null);
    }

    function updatePlugin() {
        StateManager.updatePlugin('#shippingPaymentForm', 'fatchipCTKlarnaPayments');
    }

    function destroyPlugin() {
        StateManager.destroyPlugin('#shippingPaymentForm', 'fatchipCTKlarnaPayments');
        StateManager.removePlugin('#shippingPaymentForm', 'fatchipCTKlarnaPayments', null);
    }

    $.plugin('fatchipCTKlarnaPayments', {
        defaults: {},

        init: function () {
            const me = this;

            me.registerEventListeners();
        },

        update: function () {
        },

        destroy: function () {
            const me = this;

            me._destroy();
        },

        registerEventListeners: function () {
            const me = this;

            me.authorizationToken = null;

            me._on(me.$el, 'submit', function (event) {
                if (!me.authorizationToken) {
                    event.preventDefault();

                    me.authorize(event);
                }
            });
        },

        authorize: function (event) {
            const authorizeData = {
                purchase_country: data['billingAddress-Country'],
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

            event.target[0].disabled = true;
            window.Klarna.Payments.authorize({
                    payment_method_category: window.fatchipCTKlarnaPaymentType
                },
                authorizeData,
                function (res) {
                    const storeAuthorizationTokenUrl = data['storeAuthorizationToken-Url'];
                    const parameter = {'authorizationToken': res['authorization_token']};

                    if (res['approved'] && res['authorization_token']) {
                        // store authorization_token
                        $.ajax({method: "POST", url: storeAuthorizationTokenUrl, data: parameter}).done(function () {
                            event.target.submit();
                        });
                    } else {
                        event.target[0].disabled = false;
                    }
                });
        },
    });
})(jQuery, window);
