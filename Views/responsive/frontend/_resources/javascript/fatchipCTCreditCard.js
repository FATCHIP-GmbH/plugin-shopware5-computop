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

        me.$el.removeClass(me.opts.activeCls);
        me._destroy();
    }
});

$.plugin("fatchipCTCreditCard", {
    defaults: {
        fatchipCTCCNr: false,
        fatchipCTCCCVC: false,
        fatchipCTCCExpiry: false,
        fatchipCTCCBrand: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log("fatchipCTCCNr:");
        console.log(me.opts.fatchipCTCCNr);
        console.log("fatchipCTCCCVC:");
        console.log(me.opts.fatchipCTCCCVC);
        console.log("fatchipCTCCExpiry:");
        console.log(me.opts.fatchipCTCCExpiry);
        console.log("fatchipCTCCBrand:");
        console.log(me.opts.fatchipCTCCBrand);
        //me.preventFormSubmit();
    },

    // triggered by init, when payment choice changes to "Computop Kreditkarte"
    preventFormSubmit: function () {
        "use strict";
        console.log("triggered preventFormSubmit");
        //fatchipCTShippingPayment.preventFormSubmit();
        console.log("after preventFormSubmit");
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});


function fatchipCTInputChanged() {
    "use strict";
    $("input[name=\"sDispatch\"]").on("change", function () {
        console.log("Dispatch changed");
        if ("9" === $(this).val()) {
            console.log("Dispatch Treffer");
        }
        //var currentDispatch = $("input[name=\"sDispatch\"]").data("value");
        //console.log(currentDispatch);
        return "dispatch";
    });
    $("input[name=\"payment\"]").on("change", function () {
        console.log("payment changed");
        if ("7" === $(this).val()) {
            console.log("Payment Treffer");
        }
        var currentPayment = $("input[name=\"payment\"]").data("value");
        console.log(currentPayment);
        return "payment";
    });
}

$.subscribe("plugin/swShippingPayment/onInputChanged", function () {
    "use strict";
    fatchipCTInputChanged();
});

$("#fatchipCTCreditCard").fatchipCTCreditCard();
$("#fatchipCTCreditCardIFrame").fatchipCTCreditCardIFrame();