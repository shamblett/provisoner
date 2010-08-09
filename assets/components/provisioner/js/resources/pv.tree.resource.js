/**
 * Generates the Resource Tree in Ext
 * 
 * @class PV.tree.Resource
 * @extends MODx.tree.Tree
 * @param {Object} config An object of options.
 * @xtype pv-tree-resource
 */
PV.tree.Resource = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		rootVisible: false
		,expandFirst: true
		,enableDrag: true
		,enableDrop: false
		,sortBy: 'menuindex'
		,title: ''
		,remoteToolbar: false
		,url: PV.config.connector_url
        ,action: 'resource/getlist'
	});
	PV.tree.Resource.superclass.constructor.call(this,config);
};

Ext.extend(PV.tree.Resource,MODx.tree.Tree,{
	forms: {}
	,windows: {}
	,stores: {}
	
	,_initExpand: function() {
		var treeState = Ext.state.Manager.get(this.treestate_id);
		if (treeState === undefined) {
			if (this.root) { this.root.expand(); }
			var wn = this.getNodeById('web_0');
			if (wn && this.config.expandFirst) {
				wn.select();
				wn.expand();
			}
		} else {
            this.expandPath(treeState);
        }
	}

	,importResource: function(item,e) {
        var node = this.cm.activeNode;
		var id = node.id;
		MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'resource/import'
				,id: id
				,folder: !node.expanded
                ,convert: false
			}
			,listeners: {
				'success': {fn:function() { this.refreshNode(node.id); },scope:this}
			}
		});
	}
        ,importConvertResource: function(item,e) {
        var node = this.cm.activeNode;
		var id = node.id;
		MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'resource/import'
				,id: id
				,folder: !node.expanded
                ,convert: true
			}
			,listeners: {
				'success': {fn:function() { this.refreshNode(node.id); },scope:this}
			}
		});
	}
	
});

Ext.reg('pv-tree-resource',PV.tree.Resource);

