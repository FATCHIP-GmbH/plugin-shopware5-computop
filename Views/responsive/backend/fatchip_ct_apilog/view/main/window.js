/**
 * $Id: $
 */

//{namespace name=backend/fatchip_ct_apilog/main}
//{block name="backend/fatchip_ct_apilog/view/main/window"}
Ext.define('Shopware.apps.FatchipFCSApilog.view.main.Window', {
  extend: 'Enlight.app.Window',
  title: '{s name=window_title}Apilog{/s}',
  cls: Ext.baseCSSPrefix + 'log-window',
  alias: 'widget.log-main-window-api',
  border: false,
  autoShow: true,
  height: 550,
  layout: 'border',
  width: 1000,
  stateful: true,
  stateId: 'shopware-log-window',
  /**
   * Initializes the component and builds up the main interface
   *
   * @return void
   */
  initComponent: function() {
    var me = this;
    me.items = [
      {
        xtype: 'fatchipFCSApilogMainList',
        logStore: me.logStore
      }
    ];

    me.callParent(arguments);
  }
});
//{/block}