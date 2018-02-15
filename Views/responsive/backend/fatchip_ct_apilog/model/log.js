/**
 * $Id: $
 */

/**
 * model for api log entries
 */
//{block name="backend/fatchip_ct_apilog/model/log"}
Ext.define('Shopware.apps.FatchipCTApilog.model.Log', {
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
  //{block name="backend/fatchip_ct_apilog/model/log/fields"}{/block}
  'id',
  'paymentName',
  'request',
  'response',
  'payId',
  'transId',
  'xId',
  {
    name: 'creationDate',
    type: 'date',
    dateFormat:'Y-m-d'
  },
  'requestDetails',
  'responseDetails',
  'responseArray',
  'requestArray'
  ]
  ,
  /**
    * Configure the data communication
    * @object
    */
  proxy: {
    type: 'ajax',
    /**
        * Configure the url mapping for the different
        * @object
        */
    api: {
      //read out all articles
      read: '{url controller="FatchipCTApilog" action="getApilogs"}',
      destroy: '{url controller="FatchipCTApilog" action="deleteLogs"}',
      detail: '{url controller="FatchipCTApilog" action="getDetailLog"}'
    },
    /**
        * Configure the data reader
        * @object
        */
    reader: {
      type: 'json',
      root: 'data',
      //total values, used for paging
      totalProperty: 'total'
    }
  }
});
//{/block}