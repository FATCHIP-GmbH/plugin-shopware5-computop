/**
 * $Id: $
 */

/**
 * apilogs store
 */
//{block name="backend/fatchip_fcs_apilog/store/logs"}
Ext.define('Shopware.apps.FatchipFCSApilog.store.Logs', {
  /**
   * Extend for the standard ExtJS 4
   * @string
   */
  extend: 'Ext.data.Store',
  /**
   * Auto load the store after the component
   * is initialized
   * @boolean
   */
  autoLoad: false,
  storeId: 'logsStore',
  /**
   * Amount of data loaded at once
   * @integer
   */
  pageSize: 20,
  remoteFilter: true,
  remoteSort: true,
  /**
   * Define the used model for this store
   * @string
   */
  model: 'Shopware.apps.FatchipFCSApilog.model.Log',
  proxy: {
    type: 'ajax',
    /**
     * Configure the url mapping for the different
     * @object
     */
    api: {
      //read out all articles
      read: '{url controller="FatchipFCSApilog" action="getApilogs"}',
      destroy: '{url controller="FatchipFCSApilog" action="deleteLogs"}',
      detail: '{url controller="FatchipFCSApilog" action="getDetailLog"}',
      search: '{url controller="FatchipFCSApilog" action="getSearchResult"}'
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
    },
    sortOnLoad: true,
    sorters: {
      property: 'creationDate',
      direction: 'DESC'
    }
  }
  // Default sorting for the store

});
//{/block}