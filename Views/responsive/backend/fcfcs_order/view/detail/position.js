//{namespace name=backend/fcfcs_order/main}
//{block name="backend/order/view/detail/position" append}
Ext.define('Shopware.apps.Order.view.detail.fcctPosition', {
  override: 'Shopware.apps.Order.view.detail.Position',

  createGridToolbar: function() {
    var me = this;
    var toolbar = me.callParent(arguments);

    me.fcctCapturePositionsButton = Ext.create('Ext.button.Button', {
      id: 'btnCapture',
      iconCls: 'sprite-money-coin',
      text: '{s name=position/capture}Positionen einziehen{/s}',
      action: 'fcctCapturePositions',
      handler: function() {
        me.fireEvent('fcctCapturePositions', me.record, me.orderPositionGrid, {
          callback: function(order) {
            me.fireEvent('updateForms', order, me.up('window'));
          }
        });
      }
    });

    me.fcctDebitPositionsButton = Ext.create('Ext.button.Button', {
      id: 'btnDebit',
      iconCls: 'sprite-money-coin',
      text: '{s name=position/debit}Positionen gutschreiben{/s}',
      action: 'fcctDebitPositions',
      handler: function() {
        me.fireEvent('fcctDebitPositions', me.record, me.orderPositionGrid, {
          callback: function(order) {
            me.fireEvent('updateForms', order, me.up('window'));
          }
        });
      }
    });



    toolbar.items.add(me.fcctCapturePositionsButton);
    toolbar.items.add(me.fcctDebitPositionsButton);

    Ext.Ajax.request({
      url: '{url controller="FatchipFCSOrder" action="fatchipFCSTGetButtonState"}',
      params: { id:  me.record.get('id')},
      success: function(response) {
        var response = Ext.JSON.decode(response.responseText);
        if(response.success) {
          if (response.isOrderCapturable) {
            toolbar.items.get('btnCapture').enable();
          } else {
            toolbar.items.get('btnCapture').disable();
          }
          if (response.isOrderRefundable) {
            toolbar.items.get('btnDebit').enable();
          } else {
            toolbar.items.get('btnDebit').disable();
          }

        }
      }
    });

    return toolbar;
  },

  registerEvents: function() {
    var me = this;
    me.callParent(arguments);

    this.addEvents('fcctCapturePositions', 'fcctDebitPositions');
  },

  getColumns:function (grid) {
    var me = this;
    columns = me.callParent(arguments);

    columns.push({
        header: '{s name=position/captured}Eingezogen{/s}',
        dataIndex: 'fcctcaptured',
        flex:1
      },
      {
        header: '{s name=position/debited}Gutgeschrieben{/s}',
        dataIndex: 'fcctdebit',
        flex:1
      }
    );
    return columns;
  }


});
//{/block}
