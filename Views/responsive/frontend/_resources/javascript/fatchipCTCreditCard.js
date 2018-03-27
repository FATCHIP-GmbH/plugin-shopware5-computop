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
$("#fatchipCTCreditCardIFrame").fatchipCTCreditCardIFrame();