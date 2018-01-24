//{namespace name=backend/mopt_risk_management/main}
//{block name="backend/risk_management/store/risks" append}
Ext.define('Shopware.apps.RiskManagement.store.fcct__Risks', {

    override: 'Shopware.apps.RiskManagement.store.Risks',
    
    constructor: function() 
    {
      var me = this;
      
      if(!me.fatchip_computop__isExtended())
      {
        me.data.push({ description: '{s name=risks_store/comboBox/computopTrafficLight}Computop Risikoampel IST{/s}', value: 'FATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS' });
        me.data.push({ description: '{s name=risks_store/comboBox/computopTrafficLightNot}Computop Risikoampel IST NICHT{/s}', value: 'FATCHIP_COMPUTOP__TRAFFIC_LIGHT_IS_NOT' });
      }
      
      me.callParent(arguments);
    },
            
    fatchip_computop__isExtended: function()
    {
      var me = this;
      
      for (var i = 0; i < me.data.length; i++)
      {
        if (me.data[i].value.indexOf('FATCHIP_COMPUTOP__') == 0)
        {
          return true;
        }
      }
      
      return false;
    }
});
//{/block}
