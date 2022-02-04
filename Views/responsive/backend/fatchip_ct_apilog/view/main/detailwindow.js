/**
 * $Id: $
 */

//{namespace name=backend/fatchip_ct_apilog/main}
//{block name="backend/fatchip_ct_apilog/view/main/detailwindow"}
Ext.define('Shopware.apps.FatchipFCSApilog.view.main.Detailwindow', {
	extend: 'Enlight.app.Window',
    title: '{s name=window_detail_title}Apilog Details{/s}',
    cls: Ext.baseCSSPrefix + 'detail-window',
    alias: 'widget.fatchipFCSApilogMainDetailWindow',
    border: false,
    autoShow: true,
    layout: 'border',
    height: '90%',
    width: 800,

    stateful: true,
    stateId:'shopware-detail-window',

    /**
     * Initializes the component and builds up the main interface
     *
     * @return void
     */
    initComponent: function() {
        var me = this;
        me.title = '{s name=api_log_details_for}Apilog Details zu ID {/s}' + me.itemSelected;
        me.items = [{
            xtype: 'fatchipFCSApilogMainDetail',
            itemSelected: me.itemSelected
        }];

        me.callParent(arguments);
    }
});
//{/block}