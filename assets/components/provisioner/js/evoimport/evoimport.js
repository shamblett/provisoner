/**
 * Loads the panel for importing Evo sites.
 *
 * @class PV.panel.EvoImport
 * @extends MODx.FormPanel
 * @param {Object} config An object of configuration properties
 * @xtype pv-panel-evoimport
 */
PV.panel.EvoImport = function(config) {
    config = config || {};
    Ext.applyIf(config,{
        id: 'pv-panel-evoimport'
        ,title: _('menu_evoimport_tab')
        ,cls: 'modx-resource-tab'
        ,url: PV.config.connector_url
        ,baseParams: {
            action: 'evoimport/evoimport'
        }
        ,defaults: {
            collapsible: false ,
            autoHeight: true
        }
        ,labelWidth: 160
        ,layout : 'form'
        ,bodyStyle: 'padding: 15px 15px 15px 0;'
        ,padding: 10
        ,header: false
        ,items: [{
            html: '<h2>'+_('menu_evoimport')+'</h2><br/>'
            ,id: 'pv-ct-import-panel-header'
            ,cls: 'modx-page-header'
            ,border: false
        },{
            id: 'pv-ct-import-local-fieldset',
            title: _('desc_evoimport_localsite')
            ,xtype: 'fieldset'
            ,width: 680
            ,items: [{
                xtype: 'modx-combo-context'
                ,fieldLabel: _('evoimport_context')
                ,description: _('evoimport_context_help')
                ,name: 'pv-import-context'
                ,hiddenName: 'pv-import-context'
                ,id: 'pv-ct-import-context'
                ,value: 'web'

            },{
                xtype: 'checkbox'
                ,fieldLabel: _('evoimport_parentcategories')
                ,description: _('evoimport_cat_help')
                ,name: 'pv-import-parent-cat'
                ,id: 'pv-ct-import-cat'
                ,inputValue: 1
                ,checked: false

            },{
                xtype: 'textfield'
                ,fieldLabel: _('evoimport_timeout')
                ,description: _('evoimport_timeout_help')
                ,name: 'pv-import-timeout'
                ,id: 'pv-ct-import-timeout'
                ,value: 300
                ,boxMaxWidth: 40
            }]

        },{
            id: 'pv-ct-import-remote-fieldset',
            title: _('desc_evoimport_remotesite')
            ,xtype: 'fieldset'
            ,width: 680
            ,items: [{

                xtype: 'checkbox'
                ,fieldLabel: _('evoimport_snippets')
                ,description: _('evoimport_snippet_help')
                ,name: 'pv-import-snippets'
                ,id: 'pv-ct-import-snippets'
                ,inputValue: 1
                ,checked: false

            },{
                xtype: 'checkbox'
                ,fieldLabel: _('evoimport_chunks')
                ,description: _('evoimport_chunk_help')
                ,name: 'pv-import-chunks'
                ,id: 'pv-ct-import-chunks'
                ,inputValue: 1
                ,checked: false

            },{
                xtype: 'checkbox'
                ,fieldLabel: _('evoimport_plugins')
                ,description: _('evoimport_plugin_help')
                ,name: 'pv-import-plugins'
                ,id: 'pv-ct-import-plugins'
                ,inputValue: 1
                ,checked: false

            },{
                xtype: 'checkbox'
                ,name: 'pv-import-abort'
                ,id: 'pv-ct-import-abort'
                ,hidden: true
                ,inputValue: 1
                ,checked: true
            }]
        }]
        ,buttons: [{
            text: _('button_import')
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
            ,
            'success': {
                fn:this.success,
                scope:this
            }
            ,
            'failure': {
                fn:this.failure,
                scope:this
            }
        }
    });
    PV.panel.EvoImport.superclass.constructor.call(this,config);
};
Ext.extend(PV.panel.EvoImport,MODx.FormPanel,{
    initialized: false
    ,
    setup: function() {
        /* do any post-render actions here */
        this.initialized = true;
        this.fireEvent('ready');
    }
    ,
    beforeSubmit: function(o) {
        /* Change the default waiting message */
        o.config.saveMsg = _('evoimport_importing');
        Ext.apply(o.form.baseParams,{
            
            });

        /* Set the import timeout*/
        var timeoutCmp = Ext.getCmp('pv-ct-import-timeout');
        var timeout = timeoutCmp.getValue();
        this.form.timeout = timeout;
    }
    ,
    success: function() {
        /* Confirm we really want to do this */
        var abortBox = Ext.getCmp('pv-ct-import-abort');
        if ( abortBox.getValue() == 0 ) {
            /* Succesful import, flip the abort back on */
            abortBox.setValue(true);
            Ext.Msg.show({
                msg: _('evoimportsuccess'),
                buttons: Ext.Msg.OK
            });
        } else {
            /* Confirm the import */
            Ext.Msg.confirm( _('evoimport_importingwarnbox'), _('evoimport_importingwarntext'),
                        function(btn){if (btn === 'yes') {
                                var abortBox = Ext.getCmp('pv-ct-import-abort');
                                abortBox.setValue(false);
                        }
                        });
        }
    }
    ,
    failure: function() {
         /* Turn abort on again */
         var abortBox = Ext.getCmp('pv-ct-import-abort');
         abortBox.setValue(true);
    }

});
Ext.reg('pv-panel-evoimport',PV.panel.EvoImport);
