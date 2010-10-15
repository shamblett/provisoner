/**
 * Loads a grid of MODx users.
 * 
 * @class PV.grid.User
 * @extends MODx.grid.Grid
 * @param {Object} config An object of config properties
 * @xtype pv-grid-user
 */
PV.grid.User = function(config) {
    config = config || {};
	Ext.applyIf(config,{
		url: PV.config.connector_url
		,baseParams: {
            action: 'users/getlist'
		}
		,preventRender: true
        ,autoWidth: true
		,fields: ['id','type','username','fullname','email'
            ,'gender','blocked','role','menu']
        ,columns: this.getColumns()
        ,paging: true
		,autosave: true
		,tbar: [{
            xtype: 'textfield'
            ,name: 'username_filter'
            ,id: 'modx-filter-username'
            ,emptyText: _('filter_by_username')
            ,listeners: {
                'change': {fn:this.filterByName,scope:this}
                ,'render': {fn:function(tf) {
                    tf.getEl().addKeyListener(Ext.EventObject.ENTER,function() {
                        tf.fireEvent('change'); 
                    },this);
                }}
            }
        }]
	});
	PV.grid.User.superclass.constructor.call(this,config);
};
Ext.extend(PV.grid.User,MODx.grid.Grid,{
	getColumns: function() {		
		var gs = new Ext.data.SimpleStore({
			fields: ['text','value']
			,data: [['-',0],[_('male'),1],[_('female'),2]]
		});
		
		return [{
			header: _('id')
            ,dataIndex: 'id'
            ,sortable: false
		},{
                        header: _('name')
            ,dataIndex: 'username'
            ,sortable: true
		},{
			header: _('user_full_name')
            ,dataIndex: 'fullname'
            ,sortable: true
			,editor: { xtype: 'textfield' }
                },{
			header: _('user_email')
            ,dataIndex: 'email'
            ,sortable: true
			,editor: { xtype: 'textfield' }
		},{
			header: _('user_block')
            ,dataIndex: 'blocked'
			,editor: { xtype: 'combo-boolean', renderer: 'boolean' }
        }];
	}
    
    ,importUser: function() {
            MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'users/import'
				,id: this.menu.record.id
                                ,type: this.menu.record.type
			}
            ,listeners: {
            	'success': {fn:this.refresh,scope:this}
            }
        });
    }
    				
	,rendGender: function(d,c) {
		switch(d.toString()) {
			case '0':
				return '-';
			case '1':
				return _('male');
			case '2':
				return _('female');
		}
	}
    
    ,filterByName: function(tf,newValue,oldValue) {
        this.getStore().baseParams = {
            action: 'users/getList'
            ,username: newValue            
        };
        this.getStore().load({
            params: {
                start: 0
                ,limit: 15
            }
            ,scope: this
            ,callback: this.refresh
        });
    }
});
Ext.reg('pv-grid-user',PV.grid.User);
