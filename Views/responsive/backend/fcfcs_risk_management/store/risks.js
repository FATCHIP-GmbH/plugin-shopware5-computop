//{namespace name="backend/fcfcs_risk_management/main"}
//{block name="backend/risk_management/store/risks" append}
Ext.define('Shopware.apps.RiskManagement.store.fcfcs__Risks', {

    override: 'Shopware.apps.RiskManagement.store.Risks',
    
    constructor: function() 
    {
      var me = this;
      
      if(!me.fatchip_firstcash__isExtended())
      {
        me.data.push({ description: '{s name="risks_store/comboBox/firstcashTrafficLight"}FirstCash Risikoampel IST{/s}', value: 'FATCHIP_FIRSTCASH__TRAFFIC_LIGHT_IS' });
        me.data.push({ description: '{s name="risks_store/comboBox/firstcashTrafficLightNot"}FirstCash Risikoampel IST NICHT{/s}', value: 'FATCHIP_FIRSTCASH__TRAFFIC_LIGHT_IS_NOT' });
      }
      
      me.callParent(arguments);
    },
            
    fatchip_firstcash__isExtended: function()
    {
      var me = this;
      
      for (var i = 0; i < me.data.length; i++)
      {
        if (me.data[i].value.indexOf('FATCHIP_FIRSTCASH__') == 0)
        {
          return true;
        }
      }
      
      return false;
    }
});
//{/block}
