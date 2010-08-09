/**
 * Loads a grid of Packages.
 * 
 * @class PV.grid.Package
 * @extends MODx.grid.Grid
 * @param {Object} config An object of options.
 * @xtype pv-grid-package
 */
PV.grid.Package = function(config) {
    config = config || {};
    this.exp = new Ext.grid.RowExpander({
        tpl : new Ext.Template(
            '<p style="padding: .7em 1em .3em;"><i>{readme}</i></p>'
        )
    });
    Ext.applyIf(config,{
        title: _('packages')
        ,id: 'pv-grid-package'
        ,url: PV.config.connector_url
		,baseParams: {
            action: 'packages/getlist'
		}
        ,fields: ['signature', 'localinstall','created','updated','installed','state','workspace','provider','disabled','source','manifest','attributes','readme','menu']
        ,plugins: [this.exp]
        ,pageSize: 20
		,preventRender: true
        ,columns: [this.exp,{
               header: _('package_signature') ,dataIndex: 'signature' }
            ,{ header: _('localinstall') ,dataIndex: 'localinstall',renderer: this._local }
        ]
        ,primaryKey: 'signature'
        ,paging: true
        ,autosave: false
        ,tools: [{
            id: 'plus'
            ,qtip: _('expand_all')
            ,handler: this.expandAll
            ,scope: this
        },{
            id: 'minus'
            ,hidden: true
            ,qtip: _('collapse_all')
            ,handler: this.collapseAll
            ,scope: this
        }]
    });
    PV.grid.Package.superclass.constructor.call(this,config);
};
Ext.extend(PV.grid.Package,MODx.grid.Grid,{
    console: null
    
    ,update: function(btn,e) {        
        MODx.Ajax.request({
            url: this.config.url
            ,params: {
                action: 'update'
                ,signature: this.menu.record.signature
            }
            
        });
    }
    
    ,_local: function(d,c) {
        switch(d) {
            case true:
                c.css= 'green'
				return _('local_installed');
            default:
                c.css = 'red';
				return _('not_local_installed');
        }
    }
    
});
Ext.reg('pv-grid-package',PV.grid.Package);
