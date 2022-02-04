$.plugin("fatchipFCSCreditCardIFrame", {
    defaults: {
        fatchipFCSUniqueID: false,
        fatchipFCSCreditcardIFrameUrl: false,
        fatchipFCSErrorMessage: false,
        fatchipFCSErrorCode: false
    },

    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();

        window.top.location.href = me.opts.fatchipFCSCreditcardIFrameUrl + "?sUniqueID=" + me.opts.fatchipFCSUniqueID + "&CTError[CTErrorMessage]=" + me.opts.fatchipFCSErrorMessage + "&CTError[CTErrorCode]=" + me.opts.fatchipFCSErrorCode;
    },

    destroy: function () {
        "use strict";
        var me = this;
        me._destroy();
    }
});

$.plugin("fatchipFCSCreditCardPaynow", {

    init: function () {
        "use strict";

        $("button[form=\"confirm--form\"]").on("click", function () {
            //event.preventDefault();

            var submitUrl = "https://www.firstcash-paygate.com/paynow.aspx";
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

$.plugin("fatchipFCSCCNrValidator", {
    defaults: {
        ibanbicReg: /^[0-9 ]+$/,
        errorMessageClass: "register--error-msg",
        moptIbanErrorMessage: "Dieses Feld darf nur Ziffern enthalten"
    },
    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();

        me.$el.bind("keyup change", function () {
            $("#fatchipctiban--message").remove();
            if (me.$el.val() && !me.opts.ibanbicReg.test(me.$el.val())) {
                me.$el.addClass("has--error");
                $("<div>", {
                    "html": "<p>" + me.opts.moptIbanErrorMessage + "</p>",
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

$("#fatchipFCSCreditCardPaynow").fatchipFCSCreditCardPaynow();
$("#fatchipFCSCreditCardIFrame").fatchipFCSCreditCardIFrame();
$("#CCNr").fatchipFCSCCNrValidator();
$("#CCCVC").fatchipFCSCCNrValidator();