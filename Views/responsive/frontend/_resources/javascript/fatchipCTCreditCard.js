$.plugin("fatchipCTCreditCardIFrame", {
    defaults: {
        fatchipCTCreditcardIFrameUrl: false,
        fatchipCTErrorMessage: false,
        fatchipCTErrorCode: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log(me.opts.fatchipCTCreditcardIFrameUrl);
        console.log("fatchipCTErrorMessage:");
        console.log(me.opts.fatchipCTErrorMessage);
        console.log("fatchipCTErrorCode:");
        console.log(me.opts.fatchipCTErrorCode);

        window.top.location.href = me.opts.fatchipCTCreditcardIFrameUrl + "?CTError[CTErrorMessage]=" + me.opts.fatchipCTErrorMessage + "&CTError[CTErrorCode]=" + me.opts.fatchipCTErrorCode;
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});

$.plugin("fatchipCTInputChanged", {

    init: function () {
        "use strict";
        var me = this;
        console.log("onInputChanged");

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
        var me = this;
        console.log("preventing default");
        $("form[name=\"shippingPaymentForm\"] button").on("click", function (event) {
            event.preventDefault();
            var submitUrl = "https://www.computop-paygate.com/paynow.aspx";
            var action = $("form[name=\"shippingPaymentForm\"]").prop("action");
            $("form[name=\"shippingPaymentForm\"]").prop("action", submitUrl);
            var expiryYear = $("select#CCExpiry option:selected").val();
            var expiryMonth = $("select#CCExpiryMonth option:selected").val();
            var expiry = expiryYear + expiryMonth;
            $("select#CCExpiry option:selected").val(expiry)
            $("form[name=\"shippingPaymentForm\"]").submit();


        });
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});

$.subscribe("plugin/swShippingPayment/onInputChanged", function () {
    "use strict";
    $("#fatchipCTCreditCardPaynow").fatchipCTCreditCardPaynow();
});

$("#fatchipCTCreditCardPaynow").fatchipCTCreditCardPaynow();
$("#fatchipCTCreditCardIFrame").fatchipCTCreditCardIFrame();