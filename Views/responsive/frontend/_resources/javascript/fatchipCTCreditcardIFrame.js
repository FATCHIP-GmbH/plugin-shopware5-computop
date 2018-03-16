$.plugin('fatchipCTCreditcardIFrame', {
    defaults: {
      fatchipCTCreditcardIFrameUrl: false
    },

    init: function () {
        var me = this;
      me.applyDataAttributes();
      console.log(me.opts.fatchipCTCreditcardIFrameUrl);
      window.top.location.href = me.opts.fatchipCTCreditcardIFrameUrl;
    },

    destroy: function () {
        var me = this;

        me.$el.removeClass(me.opts.activeCls);
        me._destroy();
    }
});

$('#fatchipCTCreditcardIFrame').fatchipCTCreditcardIFrame();
