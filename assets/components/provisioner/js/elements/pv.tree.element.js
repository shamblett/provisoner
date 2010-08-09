/**
 * Generates the Element Tree in Ext
 * 
 * @class PV.tree.Element
 * @extends MODx.tree.Tree
 * @param {Object} config An object of options.
 * @xtype pv-tree-element
 */
PV.tree.Element = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		rootVisible: false
		,enableDD: false
		,title: ''
		,url: PV.config.connector_url
        ,action: 'element/getlist'
	});
	PV.tree.Element.superclass.constructor.call(this,config);
};

Ext.extend(PV.tree.Element,MODx.tree.Tree,{
	forms: {}
	,windows: {}
	,stores: {}
	
	,importElement: function(item,e) {
        var id = this.cm.activeNode.id.substr(2);
		MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'element/import'
				,id: id
                ,convert: false
			}
			,listeners: {
				'success': {fn:function() { this.refreshNode(this.cm.activeNode.id); },scope:this}
			}
		});
	}
    ,importConvertElement: function(item,e) {
        var id = this.cm.activeNode.id.substr(2);
		MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'element/import'
				,id: id
                ,convert: true
			}
			,listeners: {
				'success': {fn:function() { this.refreshNode(this.cm.activeNode.id); },scope:this}
			}
		});
    }
});

Ext.reg('pv-tree-element',PV.tree.Element);

