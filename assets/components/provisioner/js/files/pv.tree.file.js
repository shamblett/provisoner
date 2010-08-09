/**
 * Generates the Directory Tree in Ext
 * 
 * @class PV.tree.File
 * @extends MODx.tree.Tree
 * @param {Object} config An object of options.
 * @xtype pv-tree-file
 */
PV.tree.File = function(config) {
	config = config || {};
	Ext.applyIf(config,{
		rootVisible: false
		,root_id: 'root'
		,enableDD: false
		,title: ''
		,url: PV.config.connector_url
        ,action: 'file/getlist'
		,primaryKey: 'dir'
	});
	PV.tree.File.superclass.constructor.call(this,config);
};

Ext.extend(PV.tree.File,MODx.tree.Tree,{
	forms: {}
	,windows: {}
	,stores: {}
	
	,importFile: function(item,e) {
        var node = this.cm.activeNode;
		var id = this.cm.activeNode.id
		var path = this.getPath(node);
		var fullpath = this.cm.activeNode.pathname;
		MODx.Ajax.request({
			url: PV.config.connector_url
			,params: {
				action: 'file/import'
				,file: id
				,folder: !node.attributes.leaf
				,realpath: path
				,prependPath: fullpath
			}
			,listeners: {
				'success': {fn:function() { this.refreshNode(this.cm.activeNode.id); },scope:this}
			}
		});
	}
	
	,getPath:function(node) {
        var path, p, a;

        // get path for non-root node
        if(node !== this.root) {
            p = node.parentNode;
            a = [node.text];
            while(p && p !== this.root) {
                a.unshift(p.text);
                p = p.parentNode;
            }
            a.unshift(this.root.attributes.path || '');
            path = a.join(this.pathSeparator);
        }

        // path for root node is it's path attribute
        else {
            path = node.attributes.path || '';
        }

        // a little bit of security: strip leading / or .
        // full path security checking has to be implemented on server
        path = path.replace(/^[\/\.]*/, '');
        return path+'/';
    } // eo function getPath
	
});

Ext.reg('pv-tree-file',PV.tree.File);

