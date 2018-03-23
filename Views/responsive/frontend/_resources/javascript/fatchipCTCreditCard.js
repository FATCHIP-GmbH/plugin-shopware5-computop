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

$.plugin("fatchipCTShippingPayment", {
    defaults: {
        submit: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log("fatchipCTSubmitCCForm INIT:");
    },

    preventFormSubmit: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log("fatchipCTSubmitCCForm INIT:");
        me.$el.on("submit", function (e) {
            console.log("before FatchipCTShippingPayment");
            e.preventDefault();
            console.log("after FatchipCTShippingPayment");

        });
    },

    destroy: function () {
        "use strict";
        console.log("destroy Plugin triggered");
        var me = this;
        me._destroy();
    }
});

function fatchipCTInputChanged() {
    "use strict";
    $("input[name=\"sDispatch\"]").on("change", function (e) {
        console.log("Dispatch changed");
        return true;
    });
}

$.subscribe("plugin/swShippingPayment/onInputChanged", function () {
    "use strict";
    fatchipCTInputChanged();
});

$("#fatchipCTCreditCard").fatchipCTCreditCard();
