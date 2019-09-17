console.log('plugin file start');

(function($, document, window) {
    console.log('js loaded');
    'use strict';

    function registerPlugin() {
        console.log('register plugin');
        StateManager.addPlugin('input.auto_submit[type=radio]', 'fatchipCTKlarna');
    }

    function updatePlugin() {
        StateManager.updatePlugin('input.auto_submit[type=radio]', 'fatchipCTKlarna');
    }

    $.plugin('fatchipCTKlarna', {
        defaults: {
            radioSelector: 'input.auto_submit[type=radio]',
        },
        init: function() {
            var me = this;

            console.log('plugin: init');

            me.registerEvents();
        },
        update: function() {
            var me = this;
            console.log('plugin: update');
        },
        destroy: function() {
            var me = this;
            console.log('plugin: destroy');
            me._destroy();
        },
        registerEvents: function() {
            var me = this;

            console.log('plugin: registerEventListeners');
            console.log(me);

            me._on(me.$el, 'change', function(e) {
                console.log('event');
            });
        },
    });

    $(function() {
        console.log('anonymous function call');

        if($('body').hasClass('is--act-shippingpayment')) {
            console.log('before register plugin');
            registerPlugin();
        }
    });
})(jQuery, document, window);