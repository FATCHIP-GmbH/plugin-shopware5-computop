$.plugin("fatchipCTShippingPayment", {
    defaults: {
        submit: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log("fatchipCTShippingPayment INIT:");
    },

    preventFormSubmit: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();
        console.log("entered fatchipCTShippingPayment preventFormSubmit");
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

$("#shippingPaymentForm").fatchipCTShippingPayment();
