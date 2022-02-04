var url = document.location.pathname + 'FatchipFCSIdeal/GetIdealIssuerList';

Ext.Ajax.request({
    url: url,
    success: function (response) {
        var data = Ext.decode(response.responseText);
        if (data.success) {
            data.success = '<span style=\"color: green;font-weight: bold;\">true</span>';
        } else {
            data.success = '<span style=\"color: red;font-weight: bold;\">false</span>';
        }
        var title = '<span style=\"font-weight: bold;\">' + btn.text + '</span>';
        var text = '';
        Ext.iterate(data, function (key, value) {
            text += '<strong>' + key + ':</strong> ' + value + '<br>';
        });
        Shopware.Notification.createStickyGrowlMessage({
            title: title,
            text: text,
            width: 440,
            log: false
        });
    }
});