/**
 * Loads the panel for managing elements.
 * 
 * @class PV.panel.Elements
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-elements
 */
PV.panel.Elements = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-elements'
		,title: _('menu_elements_tab')
        ,bodyStyle: ''
        ,defaults: { collapsible: false ,autoHeight: true }
        ,items: [{
            html: '<h2>'+_('pv_elements')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
            ,id: 'pv-elements-header'
        },{            
            html: '<p>'+_('elements_desc')+'</p>'
            ,border: false

        },{
            xtype: 'pv-tree-element'    
        	
		}]
    });
    PV.panel.Elements.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Elements,MODx.FormPanel);
Ext.reg('pv-panel-elements',PV.panel.Elements);
