$.plugin('fatchipCTCreditcardIFrame', {
    defaults: {
        fatchipCTCreditcardIFrameUrl: false,
        fatchipCTErrorMessage: false,
        fatchipCTErrorCode: false
    },

    init: function () {
        var me = this;
        me.applyDataAttributes();
        console.log(me.opts.fatchipCTCreditcardIFrameUrl);
        console.log('fatchipCTErrorMessage:');
        console.log(me.opts.fatchipCTErrorMessage);
        console.log('fatchipCTErrorCode:');
        console.log(me.opts.fatchipCTErrorCode);

        window.top.location.href = me.opts.fatchipCTCreditcardIFrameUrl + '?CTError[CTErrorMessage]=' + me.opts.fatchipCTErrorMessage + '&CTError[CTErrorCode]='+ me.opts.fatchipCTErrorCode;
    },

    destroy: function () {
        var me = this;

        me.$el.removeClass(me.opts.activeCls);
        me._destroy();
    }
});

$('#fatchipCTCreditcardIFrame').fatchipCTCreditcardIFrame();
