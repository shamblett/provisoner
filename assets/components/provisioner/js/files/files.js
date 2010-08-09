/**
 * Loads the panel for managing files.
 * 
 * @class PV.panel.Files
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-files
 */
PV.panel.Files = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-files'
		,title: _('menu_files_tab')
        ,bodyStyle: ''
        ,defaults: { collapsible: false ,autoHeight: true }
        ,width : 700
        ,items: [{
            html: '<h2>'+_('pv_files')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
            ,id: 'pv-files-header'
        },{            
            xtype: 'portal'
            ,items: [{
                columnWidth: .47
                ,items: [{
                    title: _('pv_files')
                    ,layout: 'form'
                     ,collapsible: false
                    ,items: [{
                        html: '<p>'+_('files_desc')+'</p>'
                        ,border: false
                    },{
                        xtype: 'pv-tree-file'
                    }]
                }]
        	}]
		}]
    });
    PV.panel.Files.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Files,MODx.FormPanel);
Ext.reg('pv-panel-files',PV.panel.Files);
