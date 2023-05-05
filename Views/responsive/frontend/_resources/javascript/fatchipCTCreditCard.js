$.plugin("fatchipCTCreditCardIFrame", {
    defaults: {
        fatchipCTUniqueID: false,
        fatchipCTCreditcardIFrameUrl: false,
        fatchipCTErrorMessage: false,
        fatchipCTErrorCode: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();

        window.top.location.href = me.opts.fatchipCTCreditcardIFrameUrl + "?sUniqueID=" + me.opts.fatchipCTUniqueID + "&CTError[CTErrorMessage]=" + me.opts.fatchipCTErrorMessage + "&CTError[CTErrorCode]=" + me.opts.fatchipCTErrorCode;
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});

$.plugin("fatchipCTCreditCardPaynow", {

    init: function () {
        "use strict";

        $("button[form=\"confirm--form\"]").on("click", function () {
            //event.preventDefault();
            var submitUrl = "https://www.computop-paygate.com/paynow.aspx";
            $("#confirm--form").prop("action", submitUrl);
            var expiryYear = $("select#CCExpiryYear option:selected").val();
            var expiryMonth = $("select#CCExpiryMonth option:selected").val();
            var expiry = expiryYear + expiryMonth;
            $("#CCExpiry ").val(expiry);
        });
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }

});

$.plugin("fatchipCTCCNrValidator", {
    defaults: {
        CCRegexp: /^[0-9 ]+$/,
        errorMessageClass: "register--error-msg",
        CCNRErrorMessage: "Dieses Feld darf nur Ziffern enthalten"
    },
    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();

        me.$el.bind("keyup change", function () {
            $("#fatchipctiban--message").remove();
            if (me.$el.val() && !me.opts.CCRegexp.test(me.$el.val())) {
                me.$el.addClass("has--error");
                $("<div>", {
                    "html": "<p>" + me.opts.CCNRErrorMessage + "</p>",
                    "id": "fatchipctiban--message",
                    "class": me.opts.errorMessageClass
                }).insertAfter(me.$el);

            } else {
                me.$el.removeClass("has--error");
                $("#fatchipctiban--message").remove();
            }
        });
    },
    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});

$.plugin("fatchipCTCCBrandDetect", {
    defaults: {
        brands: ['visa', 'amex', 'master', 'diners', 'maestro', 'jcb'],
        activeBrands: [],
        unsupportedBrands: ['diners', 'maestro', 'jcb', 'comfort'],
        visaRegexp1: /^(4|8)/,
        visaRegexp6: /^(564102)/,
        amexRegexp1: /^(7)/,
        amexRegexp2: /^(34|37)/,
        masterRegexp2: /^(23|24|25|26|51|52|53|54|55)/,
        masterRegexp3: /^(223|224|225|226|227|228|229|270|271)/,
        masterRegexp4: /^(2221|2222|2223|2224|2225|2226|2227|2228|2229|2720|5817)/,
        masterRegexp5: /^(56123|56124)/,
        masterRegexp6: /^(563077|566573|566609|566621|566920|566941|567753|588822|588973|589437|589718)/,
        dinersRegexp2: /^(36|65)/,
        dinersRegexp3: /^(300|301|302|303|304|305|644|645|646|647|648|649)/,
        dinersRegexp4: /^(3095)/,
        dinersRegexp5: /^(60110|60111|60112|60113|60114|60115|60116|60117|60118|60119)/,
        maestroRegexp2: /^(50|56|57|58|59|61|62|63|66|67|68|69)/,
        maestroRegexp3: /^(600|601|602|603|604|605|606|609|640|641|642|643)/,
        maestroRegexp4: /^(7744)/,
        maestroRegexp6: /^(561243|700600|706980|707145|708252|709900|724365|800003|822951|849686|927001|990015)/,
        jcbRegexp3: /^(353|354|355|356|357|358)/,
        jcbRegexp4: /^(3528|3529)/,
        comfortRegexp6: /^(564191)/,
        errorMessageClass: "register--error-msg",
        visaChosenMessage: "Visa",
        amexChosenMessage: "Amex",
        masterChosenMessage: "Master",
        dinersChosenMessage: "Diners",
        maestroChosenMessage: "Maestro",
        jcbChosenMessage: "JCB",
        comfortChosenMessage: "Comfort Card",
        unsupportedMessage: "Dieser Kartentyp wird nicht unterstützt!",
        inactiveMessage: "Dieser Kartentyp wird vom Händler nicht akzeptiert!",
    },
    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        me.opts.activeBrands = me.opts.activeBrands.split(" ");

        me.$el.bind("keyup change", function () {
            if (me.$el.val()) {
                for (var j = me.$el.val().length; j > 0; j--) {
                    for (var i = 0; i < me.opts.brands.length; i++) {
                        var regExpName = 'me.opts.' + me.opts.brands[i] + 'Regexp' + j;
                        var regExp = eval(regExpName);
                        if (typeof regExp !== 'undefined') {
                            // var msg = eval('me.opts.' + me.opts.brands[i] + 'ChosenMessage');
                            if (regExp.test(me.$el.val())) {
                                $("#payment_logo_fatchip_computop_creditcard").remove();

                                // set select field to supported card
                                var select = '';
                                if (me.opts.brands[i] === 'visa') {
                                    select = 'VISA';
                                } else if (me.opts.brands[i] === 'amex') {
                                    select = 'AMEX';
                                } else if (me.opts.brands[i] === 'master') {
                                    select = 'MasterCard';
                                }
                                $("#CCBrand").val(select);

                                $("<div>", {
                                    "id": "payment_logo_fatchip_computop_creditcard",
                                    "class": 'payment_logo_fatchip_computop_creditcard_' + me.opts.brands[i]
                                }).insertAfter(me.$el);
                                $("#fatchipctunsupported--message").remove();
                                $("#fatchipctinactive--message").remove();
                                // Show message for unsupported cards
                                if (($.inArray(me.opts.brands[i], me.opts.unsupportedBrands) > -1)) {
                                    me.$el.addClass("has--error");
                                    $("<div>", {
                                        "html": "<p>" + me.opts.unsupportedMessage + "</p>",
                                        "id": "fatchipctunsupported--message",
                                        "class": me.opts.errorMessageClass
                                    }).insertAfter(me.$el);
                                    return;
                                }
                                // Show message for supported but inactive cards
                                if (($.inArray(me.opts.brands[i], me.opts.activeBrands)) < 0) {
                                    me.$el.addClass("has--error");
                                    $("<div>", {
                                        "html": "<p>" + me.opts.inactiveMessage + "</p>",
                                        "id": "fatchipctinactive--message",
                                        "class": me.opts.errorMessageClass
                                    }).insertAfter(me.$el);
                                    return;
                                }
                                return;
                            }
                        }
                    }
                }
            }
        });
    },
    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }

});

$("#fatchipCTCreditCardPaynow").fatchipCTCreditCardPaynow();
$("#fatchipCTCreditCardIFrame").fatchipCTCreditCardIFrame();
$("#CCNr").fatchipCTCCNrValidator();
$(".computop-brand-detect").fatchipCTCCBrandDetect();
$("#CCCVC").fatchipCTCCNrValidator();

