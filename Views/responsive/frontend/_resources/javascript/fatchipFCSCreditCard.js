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

        window.top.location.href = me.opts.fatchipFCSCreditcardIFrameUrl + "?sUniqueID=" + me.opts.fatchipFCSUniqueID + "&FCSError[CTErrorMessage]=" + me.opts.fatchipFCSErrorMessage + "&FCSError[CTErrorCode]=" + me.opts.fatchipFCSErrorCode;
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

$.plugin("fatchipFCSCCNrValidator", {
    defaults: {
        ibanbicReg: /^[0-9 ]+$/,
        errorMessageClass: "register--error-msg",
        IbanErrorMessage: "Dieses Feld darf nur Ziffern enthalten"
    },
    init: function () {
        "use strict";
        var me = this;
        me.applyDataAttributes();

        me.$el.bind("keyup change", function () {
            $("#fatchipfcsiban--message").remove();
            if (me.$el.val() && !me.opts.ibanbicReg.test(me.$el.val())) {
                me.$el.addClass("has--error");
                $("<div>", {
                    "html": "<p>" + me.opts.IbanErrorMessage + "</p>",
                    "id": "fatchipfcsiban--message",
                    "class": me.opts.errorMessageClass
                }).insertAfter(me.$el);

            } else {
                me.$el.removeClass("has--error");
                $("#fatchipfcsiban--message").remove();
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
$("#FCSCCNr").fatchipFCSCCNrValidator();
$("#FCSCCCVC").fatchipFCSCCNrValidator();
