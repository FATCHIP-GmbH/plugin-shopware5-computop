//{namespace name=backend/fcfcs_order/main}
//{block name="backend/order/view/detail/position" append}
Ext.define('Shopware.apps.Order.view.detail.fcfcsPosition', {
  override: 'Shopware.apps.Order.view.detail.Position',

  createGridToolbar: function() {
    var me = this;
    var toolbar = me.callParent(arguments);

    me.fcfcsCapturePositionsButton = Ext.create('Ext.button.Button', {
      id: 'fcsbtnCapture',
      iconCls: 'sprite-money-coin',
      text: '{s name=position/capture}Positionen einziehen{/s}',
      action: 'fcfcsCapturePositions',
      handler: function() {
        me.fireEvent('fcfcsCapturePositions', me.record, me.orderPositionGrid, {
          callback: function(order) {
            me.fireEvent('updateForms', order, me.up('window'));
          }
        });
      }
    });

    me.fcfcsDebitPositionsButton = Ext.create('Ext.button.Button', {
      id: 'fcsbtnDebit',
      iconCls: 'sprite-money-coin',
      text: '{s name=position/debit}Positionen gutschreiben{/s}',
      action: 'fcfcsDebitPositions',
      handler: function() {
        me.fireEvent('fcfcsDebitPositions', me.record, me.orderPositionGrid, {
          callback: function(order) {
            me.fireEvent('updateForms', order, me.up('window'));
          }
        });
      }
    });

    toolbar.items.add(me.fcfcsCapturePositionsButton);
    toolbar.items.add(me.fcfcsDebitPositionsButton);

    Ext.Ajax.request({
      url: '{url controller="FatchipFCSOrder" action="fatchipFCSTGetButtonState"}',
      params: { id:  me.record.get('id')},
      success: function(response) {
        var response = Ext.JSON.decode(response.responseText);
        if(response.success) {
          if (response.isOrderCapturable) {
            toolbar.items.get('fcsbtnCapture').enable();
          } else {
            toolbar.items.get('fcsbtnCapture').disable();
          }
          if (response.isOrderRefundable) {
            toolbar.items.get('fcsbtnDebit').enable();
          } else {
            toolbar.items.get('fcsbtnDebit').disable();
          }

        }
      }
    });

    return toolbar;
  },

  registerEvents: function() {
    var me = this;
    me.callParent(arguments);

    this.addEvents('fcfcsCapturePositions', 'fcfcsDebitPositions');
  },

  getColumns:function (grid) {
    var me = this;
    columns = me.callParent(arguments);

    columns.push({
        header: '{s name=position/captured}Eingezogen{/s}',
        dataIndex: 'fcfcscaptured',
        flex:1
      },
      {
        header: '{s name=position/debited}Gutgeschrieben{/s}',
        dataIndex: 'fcfcsdebit',
        flex:1
      }
    );
    return columns;
  }


});
//{/block}
