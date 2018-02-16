/**
 * $Id: $
 */

//{namespace name=backend/fatchip_ct_apilog/main}
/**
 * Shopware UI - Log view list
 *
 * This grid contains all logs and its information.
 */
//{block name="backend/fatchip_ct_apilog/view/log/list"}
Ext.define('Shopware.apps.FatchipCTApilog.view.log.List', {
  /**
   * Extend from the standard ExtJS 4
   * @string
   */
  extend: 'Ext.grid.Panel',
  border: 0,
  ui: 'shopware-ui',
  /**
   * Alias name for the view. Could be used to get an instance
   * of the view through Ext.widget('fatchipCTApilogMainList')
   * @string
   */
  alias: 'widget.fatchipCTApilogMainList',
  /**
   * The window uses a border layout, so we need to set
   * a region for the grid panel
   * @string
   */
  region: 'center',
  /**
   * The view needs to be scrollable
   * @string
   */
  autoScroll: true,
  /**
   * Sets up the ui component
   * @return void
   */
  initComponent: function() {
    var me = this;
    me.registerEvents();
    me.selModel = me.createSelectionModel();
    me.store = me.logStore;

    me.columns = me.getColumns();
    me.toolbar = me.getToolbar(me);

    me.dockedItems = [];
    me.dockedItems.push(me.toolbar);
    me.dockedItems.push({
      dock: 'bottom',
      xtype: 'pagingtoolbar',
      displayInfo: true,
      store: me.store,
      width: '50%'
    });



    me.callParent(arguments);
  },
  /**
   *  Creates the columns
   *
   *  @return array columns Contains all columns
   */
  getColumns: function() {
    var me = this;
    var columns = [{
        header: '{s name=fatchip_ct_apilog/grid/column_id}ID{/s}',
        dataIndex: 'id',
        flex: 1
      },         {
        header: '{s name=fatchip_ct_apilog/grid/column_request}Typ{/s}',
        dataIndex: 'request',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_payment_name}Zahlart{/s}',
        dataIndex: 'paymentName',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_response}Status{/s}',
        dataIndex: 'response',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_date}Datum{/s}',
        dataIndex: 'creationDate',
        flex: 1,
        xtype: 'datecolumn',
        renderer: me.renderDate
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_mode}TransID{/s}',
        dataIndex: 'transId',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_merchant}PayID{/s}',
        dataIndex: 'payId',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_portal_id}XID{/s}',
        dataIndex: 'xId',
        flex: 1
      },
      /*{
        header: '{s name=fatchip_ct_apilog/grid/column_request_details}Request{/s}',
        dataIndex: 'requestDetails',
        flex: 1
      }, {
        header: '{s name=fatchip_ct_apilog/grid/column_response_details}Response{/s}',
        dataIndex: 'responseDetails',
        flex: 1
      }
      */
    ];
    return columns;
  },
  
  /**
   * Renders the date
   *
   * @param value
   * @return [date] value Contains the date
   */
  renderDate: function(value) {
    return Ext.util.Format.date(value) + ' ' + Ext.util.Format.date(value, 'H:i:s');
  },
  
  /**
   * Defines additional events which will be
   * fired from the component
   *
   * @return void
   */
  registerEvents: function() {
    this.addEvents('selectColumn');
  },
  
  createSelectionModel: function() {
    var me = this;

    return Ext.create('Ext.selection.RowModel', {
      listeners: {
        selectionchange: function(view, selected) {
          if(selected[0])
          {
            me.detail = Ext.create('Shopware.apps.FatchipCTApilog.view.main.Detailwindow', {
              itemSelected: selected[0].data.id
            }).show();
          }
        }
      }
    });
  },
  
  getToolbar: function(me)
  {

    var items = [
      '-',
      , {
        xtype: 'textfield',
        name: 'searchApi',
        id: 'searchFieldApi',
        dock: 'top',
        fieldLabel: '{s name=toolbar/searchField}Freitext{/s}'
      }, {
        xtype: 'button',
        name: 'searchbtnapi',
        text: '{s name=toolbar/searchButton}Suchen{/s}',
        id: 'searchBtnApi',
        width: '50px',
        dock: 'top',
        handler: function(btn, event) {
          var value = Ext.getCmp('searchFieldApi').getValue();
          var stori = me.store;

          data = stori.load({
            action: 'search',
            pageSize: 20,
            filters: [{
                property: 'search',
                value: value
              }]
          });
        }
      },
      '-',
      {
        xtype: 'button',
        name: 'resetApiBtn',
        text: '{s name=toolbar/searchReset}Suche zurücksetzen{/s}',
        id: 'resetApiBtn',
        dock: 'top',
        handler: function(btn, event) {
          var stori = me.store;
          Ext.getCmp('searchFieldApi').setValue('');
          data = stori.load({
            action: 'search',
            pageSize: 20
          });
        }
      }
    ];
    return Ext.create('Ext.toolbar.Toolbar', {
      dock: 'top',
      ui: 'shopware-ui',
      items: items
    });
  }
});
//{/block}