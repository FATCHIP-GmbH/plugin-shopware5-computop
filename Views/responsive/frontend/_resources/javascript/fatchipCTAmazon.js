$.plugin('fatchipCTAmazon', {
    defaults: {
        fatchipCTAmazonOrderReferenceId: false,
        fatchipCTAmazonSODUrl: false,
        fatchipCTAmazonGODUrl: false,
        fatchipCTAmazonRegisterUrl: false,
        fatchipCTAmazonShippingCheckUrl: false,

        customerType: 'private',
        salutation: 'mr', // there is no way to know the gender
        firstname: false,
        lastname: false,
        email: false,
        phone: false,
        //birthdayDay: false,
        //birthdayMonth: false,
        //birthdayYear: false,
        street: false,
        zip: false,
        city: false,
        countryCodeBillingID: false,
        countryCodeBilling: false,
        differentShipping: '1',
        salutation2: 'mr',
        firstname2: false,
        lastname2: false,
        company2: '',
        department2: '',
        street2: false,
        zip2: false,
        city2: false,
        countryCodeShipping: false,
        countryCodeShippingID: false
    },

    init: function () {
        var me = this;
        me._on(me.$el, 'onAmazonOrderRef', function (event) {
            event.preventDefault();
            me.applyDataAttributes();
            $.ajax({
                type: 'POST',
                async: false,
                url: me.opts.fatchipCTAmazonSODUrl,
                data: {referenceId: me.opts.fatchipCTAmazonOrderReferenceId},
                dataType: "json"
            }).done(function (msg) {
                if (msg.status == 'success') {
                    $.ajax({
                        type: 'POST',
                        async: false,
                        url: me.opts.fatchipCTAmazonGODUrl,
                        data: {referenceId: me.opts.fatchipCTAmazonOrderReferenceId},
                        dataType: "json"
                    }).done(function (msg) {
                        if (msg.status == 'success') {
                        } else {
                        }
                    });
                } else {
                    // do nothing
                }
            });
        });

        me._on(me.$el, 'onAmazonAddressSelect', function (event) {
            event.preventDefault();
            me.applyDataAttributes();
            $('#AmazonErrors').hide();
            //$.loadingIndicator.open();
            // had to delay the SOD call a bit
            // because GOD returned  only partial
            // billing address data
            setTimeout(function () {
                $.ajax({
                    type: 'POST',
                    async: false,
                    url: me.opts.fatchipCTAmazonGODUrl,
                    data: {referenceId: me.opts.fatchipCTAmazonOrderReferenceId},
                    dataType: "json"
                }).done(function (msg) {
                    if (msg.status == 'success') {
                        me.updateAddressData(msg.data);

                        // check shipping country,
                        // disable button in case shipping Country is not supported
                        // and show error message in amazonError Div
                        $.ajax({
                            type: 'POST',
                            async: false,
                            url: me.opts.fatchipCTAmazonShippingCheckUrl,
                            data: {shippingCountryID: me.opts.countryCodeShippingID},
                            dataType: "json"
                        }).done(function (msg) {
                            if (msg.status == 'success') {
                                $('#fatchipCTAmazonButton').removeAttr("disabled");
                            } else {
                                $('#AmazonErrors').show();
                                $('#AmazonErrorContent').text(msg.errormessage);
                            }
                        });

                        $.ajax({
                            type: 'POST',
                            async: false,
                            url: me.opts.fatchipCTAmazonSODUrl,
                            data: {referenceId: me.opts.fatchipCTAmazonOrderReferenceId},
                            dataType: "json"
                        }).done(function (msg) {
                            if (msg.status == 'success') {
                            } else {
                                // nothing to Do
                            }
                        });
                    } else {
                        // nothing to do
                    }
                });
            }, 1000);
            //$.loadingIndicator.close();
        });

        me._on(me.$el, 'fatchipCTAmazonButtonClick', function (event) {
            event.preventDefault();
            /*
                        // these are nicer solutions than building the complete from
                        // but after posting to register controller the redirects in
                        // saveRegister will not work!

                        $.ajax({
                            type: 'POST',
                            async: true,
                            url: me.opts.fatchipCTAmazonRegisterUrl,
                            context: document.body,
                            data: {
                                    "register[personal][customer_type]": 'private',
                                    "register[personal][salutation]": 'mr',
                                    "register[personal][firstname]": me.opts.firstname,
                                    "register[personal][lastname]": me.opts.lastname,
                                    "register[personal][accountmode]": '1',
                                    "register[personal][skipLogin]": '1',
                                    "register[personal][email]": me.opts.email,
                                    "register[personal][emailConfirmation]": me.opts.email,
                                    "register[personal][phone]": me.opts.phone,

                                    "register[billing][street]": me.opts.street,
                                    "register[billing][city]": me.opts.city,
                                    "register[billing][zipcode]": me.opts.zip,
                                    "register[billing][country]": me.opts.countryBillingID,
                                    "register[billing][shippingAddress]": me.opts.differentShipping,
                                    "register[billing][customer_type]": me.opts.customerType,
                                    "register[billing][accountmode]": '1',
                                    "register[billing][phone]": me.opts.phone,
                                    "register[billing][additional][customer_type]": me.opts.customerType,

                                    "register[shipping][salutation]": me.opts.salutation2,
                                    "register[shipping][firstname]": me.opts.firstname2,
                                    "register[shipping][lastname]": me.opts.lastname2,
                                    "register[shipping][company]": me.opts.company2,
                                    "register[shipping][department]": me.opts.department2,
                                    "register[shipping][street]": me.opts.street2,
                                    "register[shipping][city]": me.opts.city2,
                                    "register[shipping][zipcode]": me.opts.zip2,
                                    "register[shipping][country]": me.opts.countryShippingID,
                                    "register[shipping][phone]": me.opts.phone
                            },
                        })
                        });
                        */

            /*
            $.post(me.opts.fatchipCTAmazonRegisterUrl,
                {
                    "register[personal][customer_type]": 'private',
                    "register[personal][salutation]": 'mr',
                    "register[personal][firstname]": me.opts.firstname,
                    "register[personal][lastname]": me.opts.lastname,
                    "register[personal][accountmode]": '1',
                    "register[personal][skipLogin]": '1',
                    "register[personal][email]": me.opts.email,
                    "register[personal][emailConfirmation]": me.opts.email,
                    "register[personal][phone]": me.opts.phone,

                    "register[billing][street]": me.opts.street,
                    "register[billing][city]": me.opts.city,
                    "register[billing][zipcode]": me.opts.zip,
                    "register[billing][country]": me.opts.countryBillingID,
                    "register[billing][shippingAddress]": me.opts.differentShipping,
                    "register[billing][customer_type]": me.opts.customerType,
                    "register[billing][accountmode]": '1',
                    "register[billing][phone]": me.opts.phone,
                    "register[billing][additional][customer_type]": me.opts.customerType,

                    "register[shipping][salutation]": me.opts.salutation2,
                    "register[shipping][firstname]": me.opts.firstname2,
                    "register[shipping][lastname]": me.opts.lastname2,
                    "register[shipping][company]": me.opts.company2,
                    "register[shipping][department]": me.opts.department2,
                    "register[shipping][street]": me.opts.street2,
                    "register[shipping][city]": me.opts.city2,
                    "register[shipping][zipcode]": me.opts.zip2,
                    "register[shipping][country]": me.opts.countryShippingID,
                    "register[shipping][phone]": me.opts.phone
                } );

        });
*/
            var frm = $('<form>', {
                'action': me.opts.fatchipCTAmazonRegisterUrl,
                'method': 'post'
            });

            // SW 5.0 - 5.1
            frm.append(
                '<input type="hidden" name="register[personal][customer_type]" value="' + me.opts.customerType + '"/>' +
                '<input type="hidden" name="register[personal][salutation]" value="' + me.opts.salutation + '"/>' +
                '<input type="hidden" name="register[personal][firstname]" value="' + me.opts.firstname + '"/>' +
                '<input type="hidden" name="register[personal][lastname]" value="' + me.opts.lastname + '"/>' +
                // SW > 5.2
                '<input type="hidden" name="register[personal][accountmode]" value="1"/>' +

                '<input type="hidden" name="register[personal][skipLogin]" value="1"/>' +
                '<input type="hidden" name="register[personal][email]" value="' + me.opts.email + '"/>' +
                '<input type="hidden" name="register[personal][emailConfirmation]" value="' + me.opts.email + '"/>' +
                '<input type="hidden" name="register[personal][phone]" value="' + me.opts.phone + '"/>' +

                '<input type="hidden" name="register[billing][street]" value="' + me.opts.street + '"/>' +
                '<input type="hidden" name="register[billing][city]" value="' + me.opts.city + '"/>' +
                '<input type="hidden" name="register[billing][zipcode]" value="' + me.opts.zip + '"/>' +
                '<input type="hidden" name="register[billing][country]" value="' + me.opts.countryCodeBillingID + '"/>' +
                '<input type="hidden" name="register[billing][shippingAddress]" value="' + me.opts.differentShipping + '"/>' +
                '<input type="hidden" name="register[billing][customer_type]" value="' + me.opts.customerType + '"/>' +
                // SW > 5.2
                '<input type="hidden" name="register[billing][accountmode]" value="1"/>' +
                '<input type="hidden" name="register[billing][phone]" value="' + me.opts.phone + '"/>' +

                // SW > 5.2 check this, shouldnt be neccessary ->Register::getPostData
                '<input type="hidden" name="register[billing][additional][customer_type]" value="' + me.opts.customerType + '"/>' +

                '<input type="hidden" name="register[shipping][salutation]" value="' + me.opts.salutation2 + '"/>' +
                '<input type="hidden" name="register[shipping][firstname]" value="' + me.opts.firstname2 + '"/>' +
                '<input type="hidden" name="register[shipping][lastname]" value="' + me.opts.lastname2 + '"/>' +
                '<input type="hidden" name="register[shipping][company]" value="' + me.opts.company2 + '"/>' +
                '<input type="hidden" name="register[shipping][department]" value="' + me.opts.department2 + '"/>' +
                '<input type="hidden" name="register[shipping][street]" value="' + me.opts.street2 + '"/>' +
                '<input type="hidden" name="register[shipping][city]" value="' + me.opts.city2 + '"/>' +
                '<input type="hidden" name="register[shipping][zipcode]" value="' + me.opts.zip2 + '"/>' +
                '<input type="hidden" name="register[shipping][country]" value="' + me.opts.countryCodeShippingID + '"/>' +
                '<input type="hidden" name="register[shipping][phone]" value="' + me.opts.phone + '"/>'
            );


            $(document.body).append(frm);
            // needed for SW > 5.2 ??
            if (typeof CSRF !== 'undefined' && typeof CSRF.updateForms !== 'undefined') {
                CSRF.updateForms();
            }
            frm.submit();
        });
    },

    updateAddressData: function (data) {
        var me = this;

        var sname = data.addrname.split(" ");
        var bname = data.bdaddrname.split(" ");

        me.opts.firstname = bname[0];
        me.opts.lastname = bname[1];
        me.opts.phone = data.phonenumber;
        me.opts.email = data.buyermail;
        me.opts.street = data.bdaddrstreet2;
        me.opts.zip = data.bdaddrzip;
        me.opts.city = data.bdaddrcity;
        me.opts.countryCodeBilling = data.bdaddrcountrycode;
        me.opts.countryCodeBillingID = data.bdaddrcountrycodeID;

        me.opts.firstname2 = sname[0];
        me.opts.lastname2 = sname[1];
        me.opts.street2 = data.AddrStreet2;
        me.opts.zip2 = data.AddrZip ? data.AddrZip : data.AddrZIP;
        me.opts.city2 = data.AddrCity;
        me.opts.countryCodeShipping = data.AddrCountryCode;
        me.opts.countryCodeShippingID = data.AddrCountryCodeID;
    },

    destroy: function () {
        var me = this;

        me.$el.removeClass(me.opts.activeCls);
        me._destroy();
    }
});

$('#fatchipCTAmazonInformation').fatchipCTAmazon();