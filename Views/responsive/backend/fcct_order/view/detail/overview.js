//{namespace name=backend/fcct_order/main}
//{block name="backend/order/view/detail/overview" append}
Ext.define('Shopware.apps.Order.view.detail.fcctOverview',
  {
    override: 'Shopware.apps.Order.view.detail.Overview',

    initComponent: function()
    {
      var swVersion = Ext.shopwareRevision;
      var me = this;
      me.callParent(arguments);

      if (swVersion < 201607011315){
        if(/fatchip_computop_/.test(me.record.raw.payment.name))
        {
          me.items.insert(2, me.createFcctShippingCostContainer());
        }
        else
        {
          me.items.insert(2, me.createFcctNoCTOrderContainer());
        }

      }

    },

    /**
     * Creates the Ext.panel.Panel for the COMPUTOP shipping costs status
     */
    createFcctShippingCostContainer: function()
    {
      var me = this;
      var fcctShowShippingCosts = true;

      for (var i = 0; i < me.record.raw.details.length; i++)
      {
        if (me.record.raw.details[i].articleNumber === "SHIPPING")
        {
          fcctShowShippingCosts = false;
        }
      }

      if(fcctShowShippingCosts)
      {
        return Ext.create('Ext.panel.Panel', {
          title: '{s name=overview/title}Computop: Versandkosten{/s}',
          bodyPadding: 10,
          flex: 1,
          paddingRight: 5,
          margin: '0 0 10 0',
          height: 100,
          items: [
            {
              xtype: 'container',
              renderTpl: me.createFcctShippingCostTemplate(),
              renderData: me.record.raw.attribute
            }
          ]
        });
      }
      else
      {
        return Ext.create('Ext.panel.Panel', {
          title: '{s name=overview/title}Computop: Versandkosten{/s}',
          bodyPadding: 10,
          flex: 1,
          paddingRight: 5,
          margin: '0 0 10 0',
          height: 100,
          items: [
            {
              xtype: 'container',
              renderTpl: me.createFcctShippingCostTemplateExtraPosition()
            }
          ]
        });
      }
    },

    /**
     * Creates the Ext.panel.Panel for the Computop shipping costs status
     */
    createFcctNoCTOrderContainer: function()
    {
      var me = this;

      return Ext.create('Ext.panel.Panel', {
        title: '{s name=overview/title}Computop: Versandkosten{/s}',
        bodyPadding: 10,
        flex: 1,
        paddingRight: 5,
        margin: '0 0 10 0',
        height: 100,
        items: [
          {
            xtype: 'container',
            renderTpl: me.createFcctNoComputopOrderTemplate()
          }
        ]
      });
    },


    /**
     * Creates the XTemplate for the ShippingCost information panel
     *
     * @return [Ext.XTemplate] generated Ext.XTemplate
     */
    createFcctShippingCostTemplate:function ()
    {
      var labelCaptured = '{s name=overview/captured}Bisher eingezogenen: {/s}';
      var labelDebited = '{s name=overview/debited}Bisher gutgeschrieben: {/s}';

      return new Ext.XTemplate(
        '{literal}<tpl for=".">',
        '<div class="customer-info-pnl">',
        '<div class="base-info">',
        '<p>',
        '<span>' + labelCaptured + '{fatchipctShipcaptured}</span>',
        '</p>',
        '<p>',
        '<span>' + labelDebited + '{fatchipctShipdebit}</span>',
        '</p>',
        '</div>',
        '</div>',
        '</tpl>{/literal}'
      );
    },

    /**
     * Creates the XTemplate for the ShippingCost information panel
     *
     * @return [Ext.XTemplate] generated Ext.XTemplate
     */
    createFcctShippingCostTemplateExtraPosition:function ()
    {
      var labelExtraPositon = '{s name=overview/extraPosition}Die Versandkosten sind als eigener Artikel in der Positionsliste verfügbar.{/s}';

      return new Ext.XTemplate(
        '{literal}<tpl for=".">',
        '<div class="customer-info-pnl">',
        '<div class="base-info">',
        '<p>',
        '<span>' + labelExtraPositon + '</span>',
        '</p>',
        '</div>',
        '</div>',
        '</tpl>{/literal}'
      );
    },

    /**
     * Creates the XTemplate for the ShippingCost information panel
     *
     * @return [Ext.XTemplate] generated Ext.XTemplate
     */
    createFcctNoComputopOrderTemplate:function ()
    {
      var labelNotComputop = '{s name=overview/notComputop}Diese Bestellung wurde nicht mit einer Computop Zahlart durchgeführt.{/s}';

      return new Ext.XTemplate(
        '{literal}<tpl for=".">',
        '<div class="customer-info-pnl">',
        '<div class="base-info">',
        '<p>',
        '<span>' + labelNotComputop + '</span>',
        '</p>',
        '</div>',
        '</div>',
        '</tpl>{/literal}'
      );
    }

  });
//{/block}
