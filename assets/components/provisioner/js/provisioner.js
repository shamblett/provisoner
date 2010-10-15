Ext.namespace('PV');
/**
 * The base Provisioner class
 *
 * @class PV
 * @extends Ext.Component
 * @param {Object} config An object of config properties
 * @xtype provisioner
 */
PV = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        base_url: MODx.config.assets_url+'components/provisioner/'
        ,connector_url: MODx.config.assets_url+'components/provisioner/connector.php'
    });
    PV.superclass.constructor.call(this,config);
    this.config = config;
};
Ext.extend(PV,Ext.Component,{
    config: {}
    ,panel: {} ,page: {} ,grid: {}, tree: {}
});
Ext.reg('provisioner',PV);
PV = new PV();


Ext.onReady(function() {
    MODx.load({
        xtype: 'pv-page-home'
    });
});

PV.page.Home = function(config) {
    config = config || {};
    Ext.applyIf(config,{
		html: '<h2>'+_('provisioner')+'</h2>'
		,cls: 'modx-page-header'
		,renderTo: 'pv-panel-header-div'
        ,components: [{
            xtype: 'pv-panel-home'
            ,renderTo: 'pv-panel-home-div'
        }]
    });
    PV.page.Home.superclass.constructor.call(this,config);
};
Ext.extend(PV.page.Home,MODx.Component);
Ext.reg('pv-page-home',PV.page.Home);


PV.panel.Home = function(config) {
    config = config || {};
    Ext.apply(config,{
        id: 'pv-panel-home'
        ,border: false
        ,defaults: { autoHeight: true}
        ,items: [{
                xtype: 'pv-panel-administration'
            },{
                xtype: 'pv-panel-resources'
            },{
                xtype: 'pv-panel-elements'
            },{
                xtype: 'pv-panel-files'
			},{
                xtype: 'pv-panel-packages'
			},{
                xtype: 'pv-panel-users'
            },{
                xtype: 'pv-panel-evoimport'
            }]
    });
    PV.panel.Home.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Home,MODx.Tabs);
Ext.reg('pv-panel-home',PV.panel.Home);
