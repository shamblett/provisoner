/**
 * Loads the panel for managing users.
 * 
 * @class PV.panel.Packages
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-users
 */
PV.panel.Users = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-users'
		,title: _('menu_users_tab')
        ,bodyStyle: ''
        ,padding: 10
        ,defaults: { collapsible: false ,autoHeight: true }
        ,items: [{
            html: '<h2>'+_('users')+'</h2>'
            ,border: false
            ,cls: 'modx-page-header'
            ,id: 'pv-users-header'
        },{            
              html: '<p>'+_('users_desc')+'</p>'
              ,border: false
        },{
              xtype: 'pv-grid-user'
                         
        	}]
    });
    PV.panel.Users.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Users,MODx.FormPanel);
Ext.reg('pv-panel-users',PV.panel.Users);
