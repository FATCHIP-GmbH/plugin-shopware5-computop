$.plugin('fatchipCTCreditCard', {
    defaults: {
        fatchipCTCCNr: false,
        fatchipCTCCCVC: false,
        fatchipCTCCExpiry: false,
        fatchipCTCCBrand: false
    },

    init: function () {
        var me = this;
        me.applyDataAttributes();
        console.log('fatchipCTCCNr:');
        console.log(me.opts.fatchipCTCCNr);
        console.log('fatchipCTCCCVC:');
        console.log(me.opts.fatchipCTCCCVC);
        console.log('fatchipCTCCExpiry:');
        console.log(me.opts.fatchipCTCCExpiry);
        console.log('fatchipCTCCBrand:');
        console.log(me.opts.fatchipCTCCBrand);
    },

    destroy: function () {
        var me = this;
        me._destroy();
    }
});

$.plugin('fatchipCTSubmitCCForm', {
    defaults: {
        fatchipCTCCNr: false,
        fatchipCTCCCVC: false,
        fatchipCTCCExpiry: false,
        fatchipCTCCBrand: false
    },

    init: function () {
        var me = this;
        me.applyDataAttributes();
        console.log('fatchipCTSubmitCCForm INIT:');
        $("#shippingPaymentForm").on('submit', function (e) {
            console.log('before preventDefault');
            e.preventDefault();
            console.log('after preventDefault');

        });
    },


    destroy: function () {
        comnsole.log('destroy Plugin');
        var me = this;
        me._destroy();
    }
});

/*function poBindDispatchChange() {
    $("input[name='sDispatch']").on('change', function (e) {
        return true;
    });
}

$.subscribe("plugin/swShippingPayment/onInputChanged", function () {
    poBindDispatchChange();
});
*/

$('#fatchipCTCreditCard').fatchipCTCreditCard();
$('#shippingPaymentForm').fatchipCTSubmitCCForm();
