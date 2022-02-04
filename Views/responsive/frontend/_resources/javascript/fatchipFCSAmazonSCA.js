;(function($, document, window) {
    'use strict';

    function registerPlugin() {
        StateManager.addPlugin('#confirm--form', 'fatchipFCSAmazonSCA');
    }

    function updatePlugin() {
        StateManager.updatePlugin('#confirm--form', 'fatchipFCSAmazonSCA');
    }

    $.plugin('fatchipFCSAmazonSCA', {
        defaults: {

        },
        init: function() {
            var me = this;

            var data = $('#fatchipFCSAmazonInformation').data();

            me.validateData(data);
            me.registerEventListeners();
        },
        update: function() {
            var me = this;
        },
        destroy: function() {
            var me = this;
            me._destroy();
        },
        registerEventListeners: function() {
            var me = this;

            me._on(me.$el, 'submit', function(e) {
                e.preventDefault();
                me.initConfirmationFlow();
            });
        },
        validateData: function(data) {
            var me = this;

            //TODO: add validation and redirect to cart if values are missing
            me.data = data;
        },
        initConfirmationFlow: function() {
            var me = this;

            var orderReferenceId = me.data.fatchipctamazonorderreferenceid;
            var sellerId = me.data.fatchipctamazonsellerid;

            OffAmazonPayments.initConfirmationFlow(sellerId, orderReferenceId, function(confirmationFlow) {
                me.setConfirmOrder(confirmationFlow);
            });
        },
        setConfirmOrder: function(confirmationFlow) {
            var me = this;

            var requestUrl = me.data.fatchipctamazonscourl;
            var cartErrorUrl = me.data.fatchipctcarterrorurl;

            $.ajax({
                    url: requestUrl,
                    success: function (data) {
                        confirmationFlow.success();
                    },
                    error: function (data) {
                        confirmationFlow.error();

                        window.location.href = cartErrorUrl;
                    },
                    timeout: 30000
                }
            );
        }
    });

    $(function() {
        if($('body').hasClass('is--ctl-fatchipctamazoncheckout') && $('body').hasClass('is--act-confirm')) {
            registerPlugin();

            /* update on ajax changes
            $.subscribe('plugin/swListingActions/updateListing', function () {
                updatePlugin();
            });
             */
        }
    });
})(jQuery, document, window);
