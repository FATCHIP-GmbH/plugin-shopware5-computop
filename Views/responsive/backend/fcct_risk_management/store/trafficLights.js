//{namespace name=backend/fcct_risk_management/main}
//{block name="backend/risk_management/app" append}
Ext.define('Shopware.apps.RiskManagement.store.TrafficLights', {
  
  extend: 'Ext.data.Store',

	fields: [
		{ name: 'description', type: 'string' },
		{ name: 'value', type: 'string' }
	],

	data: [
		{ description: '{s name=trafficLights_store/comboBox/green}grün{/s}', value: "GREEN" },
		{ description: '{s name=trafficLights_store/comboBox/yellow}gelb{/s}', value: "YELLOW" },
		{ description: '{s name=trafficLights_store/comboBox/red}rot{/s}', value: "RED" }
	]
});
//{/block}
