/**
 * Loads the panel for Administring Provisioner
 *
 * @class PV.panel.Administration
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-administration
 */
PV.panel.Administration = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-administration'
        ,cls: 'modx-resource-tab'
        ,title: _('menu_administration_tab')
        ,url: PV.config.connector_url
        ,baseParams: {
            action: 'administration/login'
        }
        ,defaults: {
            collapsible: false ,
            autoHeight: true
        }
        ,labelWidth: 80
        ,layout : 'form'
        ,bodyStyle: 'padding: 15px 15px 15px 0;'
        ,header: false
        ,items: [{
            html: '<h2>'+_('menu_administration')+'</h2><br/>'
            ,id: 'pv-ct-panel-header'
            ,cls: 'modx-page-header'
            ,border: false
        },
        {
            id: 'pv-ct-account-fieldset'
            ,title: _('account_details')
            ,xtype: 'fieldset'
            ,width: 680
            ,items: [{
                xtype: 'textfield'
                ,name: 'account'
                ,id: 'pv-ct-account'
                ,fieldLabel: _('menu_account')
                ,description: _('administration_account_help')

            },{
                name: 'password'
                ,xtype: 'textfield'
                ,id: 'pv-ct-password'
                ,inputType: 'password'
                ,fieldLabel: _('menu_password')
                ,description: _('administration_password_help')

            },{
                name: 'url'
                ,xtype: 'textfield'
                ,width: 500
                ,id: 'pv-ct-url'
                ,fieldLabel: _('menu_url')
                ,description: _('administration_url_help')

            },{
                name: 'siteid'
                ,xtype: 'textfield'
                ,width: 250
                ,id: 'pv-ct-siteid'
                ,fieldLabel: _('menu_siteid')
                ,description: _('administration_rsi_help')
            
            }]

        },{
            id: 'pv-ct-site-fieldset'
            ,title: _('remote_site')
            ,xtype: 'fieldset'
            ,width: 680
            ,items: [{
                xtype: 'radiogroup'
                ,labelSeparator: ''
                ,width: 250
                ,items: [{
                    name: 'site'
                    ,id: 'pv-ct-site-revolution'
                    ,xtype: 'radio'
                    ,boxLabel: _('revolution')
                    ,inputValue: 'revolution'
                    ,checked: true
                },{
                    name: 'site'
                    ,id: 'pv-ct-site-evolution'
                    ,xtype: 'radio'
                    ,boxLabel: _('evolution')
                    ,inputValue: 'evolution'
                }]
            }]
            }, {
            id: 'pv-ct-action-fieldset'
            ,title: _('admin_action')
            ,xtype: 'fieldset'
            ,width: 680
            ,items: [{
                xtype: 'radiogroup'
                ,labelSeparator: ''
                ,width: 250
                ,items: [{
                    name: 'action'
                    ,id: 'pv-ct-action_login'
                    ,xtype: 'radio'
                    ,boxLabel: _('login')
                    ,inputValue: 'administration/login'
                    ,checked: true
                },{
                    name: 'action'
                    ,id: 'pv-ct-action_logout'
                    ,xtype: 'radio'
                    ,boxLabel: _('logout')
                    ,inputValue: 'administration/logout'
                }]
            }]
        
            }]
        ,
        buttons: [{
            text: _('button_go')
            ,handler: this.submit
            ,scope: this
        }]
        ,
        listeners: {
            'setup': {
                fn:this.setup,
                scope:this
            }
            ,
            'beforeSubmit': {
                fn:this.beforeSubmit,
                scope:this
            }
        }
    });
    PV.panel.Administration.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.Administration,MODx.FormPanel,{
    initialized: false
    ,
    setup: function() {
        /* do any post-render actions here */
        this.initialized = true;
        MODx.Ajax.request({
                    url: PV.config.connector_url+'?action=administration/status'
                    ,method: 'post'
                    ,scope: this
                    ,listeners: {
                        'success':{fn:function(r) {
                                    var state = r.object;
                                    if ( state.loggedin == 1 ) {
                                         Ext.getCmp("pv-ct-url").setRawValue(state.url);
                                         Ext.getCmp("pv-ct-account").setRawValue(state.account);
                                         if ( state.site != 1 ) {
                                             Ext.getCmp("pv-ct-siteid").setValue(state.siteid);
                                         }
                                         if ( state.site == 1 ) {
                                             Ext.getCmp("pv-ct-site-evolution").setValue(true);
                                         }
                                         Ext.getCmp("pv-ct-action_logout").setValue(true);
                                    }
                        },scope:this}
                    }
                });
        this.fireEvent('ready');
    }
    ,
    beforeSubmit: function(o) {
       /* Change the default waiting message */
        o.config.saveMsg = _('actioning');

        Ext.apply(o.form.baseParams,{
            
            });
    }
});
Ext.reg('pv-panel-administration',PV.panel.Administration);
