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

    window.top.location.href = me.opts.fatchipCTCreditcardIFrameUrl + "?CTError[CTErrorMessage]=" + me.opts.fatchipCTErrorMessage + "&CTError[CTErrorCode]=" + me.opts.fatchipCTErrorCode;
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
    console.log("preventing default2");
    var action = $("#confirm--form").prop("action");

    $("button[form=\"confirm--form\"]").on("click", function (event) {
      event.preventDefault();


      var agbElement = document.getElementById('sAGB');
      if (agbElement) {
        $(agbElement).removeClass('has--error');
        if (!$(agbElement).is(':checked')) {
          $(agbElement).addClass('has--error');
          $(window).scrollTop($('#sAGB').offset().top);
          return false;
        }
        var submitUrl = "https://www.computop-paygate.com/paynow.aspx";
        var action = $("#confirm--form").prop("action");
        $("#confirm--form").prop("action", submitUrl);
        var expiryYear = $("select#CCExpiry option:selected").val();
        var expiryMonth = $("select#CCExpiryMonth option:selected").val();
        var expiry = expiryYear + expiryMonth;
        $("select#CCExpiry option:selected").val(expiry)
        $("#confirm--form").submit();
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
