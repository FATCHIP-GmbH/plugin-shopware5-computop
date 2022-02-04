/**
 * $Id: $
 */

/**
 * this model is used for the detail view of log entries
 */
//{block name="backend/fatchip_fcs_apilog/model/grid2cols"}
Ext.define('Shopware.apps.FatchipFCSApilog.model.Grid2cols', {
  /**
    * Extends the standard ExtJS 4
    * @string
    */
  extend: 'Ext.data.Model',
  /**
    * The fields used for this model
    * @array
    */
  fields: [
  //{block name="backend/fatchip_fcs_apilog/model/grid2cols/fields"}{/block}
  'name',
  'value'
  ]
  
});
//{/block}