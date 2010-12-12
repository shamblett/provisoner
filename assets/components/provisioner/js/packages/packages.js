/**
 * Loads the panel for managing packages.
 * 
 * @class PV.panel.Packages
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-packages
 */
PV.panel.Packages = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-packages'
		,title: _('menu_packages_tab')
        ,bodyStyle: ''
        ,padding: 10
        ,defaults: { collapsible: false ,autoHeight: true }
        ,items: [{
            html: '<h2>'+_('packages')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
            ,id: 'pv-packages-header'
        },{            
            html: '<p>'+_('packages_desc')+'</p>'
            ,border: false

        },{
             xtype: 'pv-grid-package'
                  
		}]
    });
    PV.panel.Packages.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Packages,MODx.FormPanel);
Ext.reg('pv-panel-packages',PV.panel.Packages);
