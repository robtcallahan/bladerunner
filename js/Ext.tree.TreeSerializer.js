/**
 * @class Ext.tree.TreeSerializer
 * A base class for implementations which provide serialization of an
 * {@link Ext.tree.TreePanel}.
 * <p>
 * Implementations must provide a toString method which returns the serialized
 * representation of the tree.
 *
 * @constructor
 * @param {TreePanel} tree
 * @param {Object} config
 */
Ext.tree.TreeSerializer = Ext.extend(Object, {
    constructor: function (tree, config) {
        if (typeof this.toString !== 'function') {
            throw 'Ext.tree.TreeSerializer implementation does not implement toString()';
        }
        this.tree = tree;
        if (this.attributeFilter) {
            this.attributeFilter = this.attributeFilter.createInterceptor(this.defaultAttributeFilter);
        }
        else {
            this.attributeFilter = this.defaultAttributeFilter;
        }
        if (this.nodeFilter) {
            this.nodeFilter = this.nodeFilter.createInterceptor(this.defaultNodeFilter);
        }
        else {
            this.nodeFilter = this.defaultNodeFilter;
        }
        Ext.apply(this, config);
    },

    /**
     * @cfg nodeFilter {Function} (optional) A function, which when passed the node, returns true or false to include
     * or exclude the node.
     */
    /**
     * @cfg attributeFilter {Function} (optional) A function, which when passed an attribute name, and an attribute value,
     * returns true or false to include or exclude the attribute.
     */
    /**
     * @cfg attributeMap {Array} (Optional) An object hash mapping Node attribute names to XML attribute names, or JSON property names.
     */

    /** @private
     * Array of node attributes to ignore.
     */
    standardAttributes: ["nodeType", "expanded", "allowDrag", "allowDrop", "disabled", "icon", "events", "children",
        "cls", "iconCls", "href", "hrefTarget", "qtip", "singleClickExpand", "uiProvider", "loader"],


    /** @private
     * Default attribute filter.
     * Rejects functions and standard attributes.
     */
    defaultAttributeFilter: function (attName, attValue) {
        return (typeof attValue != 'function') &&
            (this.standardAttributes.indexOf(attName) == -1);
    },

    /** @private
     * Default node filter.
     * Accepts all nodes.
     */
    defaultNodeFilter: function (node) {
        return true;
    },

    /** @private
     * Replaces invalid characters with HTML entities.
     */
    escapeXML: function (str) {
        function replaceChars(character) {
            switch (character) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case "'":
                    return '&apos;';
                case '"':
                    return '&quot;';
                default:
                    return '';
            }
            ;
        };
        return String(str).replace(/[&<>"']/g, replaceChars);
    }
});

/**
 * @class Ext.tree.XmlTreeSerializer
 * An implementation of Ext.tree.TreeSerializer which serializes an
 * {@link Ext.tree.TreePanel} to an XML string.
 */
Ext.tree.XmlTreeSerializer = Ext.extend(Ext.tree.TreeSerializer, {
    /**
     * Returns a string of XML that represents the tree
     * @return {String}
     */
    toString: function (nodeFilter, attributeFilter) {
        return '\u003C?xml version="1.0"?>\u003Ctree>' +
            this.nodeToString(this.tree.getRootNode()) + '\u003C/tree>';
    },

    /**
     * Returns a string of XML that represents the node
     * @param {Object} node The node to serialize
     * @return {String}
     */
    nodeToString: function (node) {
        if (!this.nodeFilter(node)) {
            return '';
        }
        var result = '\u003Cnode';
        if (this.attributeFilter("id", node.id)) {
            result += ' id="' + node.id + '"';
        }

        //      Add all user-added attributes unless rejected by the attributeFilter.
        for (var key in node.attributes) {
            if (this.attributeFilter(key, node.attributes[key])) {
                if (key != 'id') {
                    result += ' ' + (this.attributeMap ? (this.attributeMap[key] || key) : key) + '="' + this.escapeXML(node.attributes[key]) + '"';
                }
            }
        }

        //      Add child nodes if any
        var children = node.childNodes;
        var clen = children.length;
        if (clen == 0) {
            result += '/>';
        }
        else {
            result += '>';
            for (var i = 0; i < clen; i++) {
                result += this.nodeToString(children[i]);
            }
            result += '\u003C/node>';
        }
        return result;
    }
});

/**
 * @class Ext.tree.JsonTreeSerializer
 * An implementation of Ext.tree.TreeSerializer which serializes an
 * {@link Ext.tree.TreePanel} to a Json string.
 */
Ext.tree.JsonTreeSerializer = Ext.extend(Ext.tree.TreeSerializer, {
    /**
     * Returns a string of Json that represents the tree
     * @return {String}
     */
    toString: function () {
        return this.nodeToString(this.tree.getRootNode());
    },

    /**
     * Returns a string of Json that represents the node
     * @param {Object} node The node to serialize
     */
    nodeToString: function (node) {
        //      Exclude nodes based on caller-supplied filtering function
        if (!this.nodeFilter(node)) {
            return '';
        }
        var result = "{ ",
            key,
            children = node.childNodes,
            clen = children.length, i;

        if (this.attributeFilter("id", node.id)) {
            result += '"id":"' + node.id + '",';
        }

        //      Add all user-added attributes unless rejected by the attributeFilter.
        for (key in node.attributes) {
            if (this.attributeFilter(key, node.attributes[key])) {
                result += '"' + (this.attributeMap ? (this.attributeMap[key] || key) : key) + '":' + Ext.encode(this.escapeXML(node.attributes[key])) + ',';
            }
        }

        //      Add child nodes if any
        if (clen) {
            result += '"children":[';
            for (i = 0; i < clen; i++) {
                result += this.nodeToString(children[i]) + ',';
            }
            result = result.substr(0, result.length - 1) + ']';
        }
        else {
            result = result.substr(0, result.length - 1);
        }
        return result + "}";
    }
});
/*
 Ext.onReady(function(){
 var tree = new Ext.tree.TreePanel({
 renderTo:'tree-div',
 title: 'My Task List',
 height: 300,
 width: 400,
 useArrows:true,
 autoScroll:true,
 animate:true,
 enableDD:true,
 containerScroll: true,
 rootVisible: false,
 frame: true,
 root: {
 nodeType: 'async'
 },

 // auto create TreeLoader
 dataUrl: 'check-nodes.json',

 listeners: {
 'checkchange': function(node, checked){
 if(checked){
 node.getUI().addClass('complete');
 }else{
 node.getUI().removeClass('complete');
 }
 }
 },

 buttons: [{
 text: 'Get Completed Tasks',
 handler: function(){
 var msg = '', selNodes = tree.getChecked();
 Ext.each(selNodes, function(node){
 if(msg.length > 0){
 msg += ', ';
 }
 msg += node.text;
 });
 Ext.Msg.show({
 title: 'Completed Tasks',
 msg: msg.length > 0 ? msg : 'None',
 icon: Ext.Msg.INFO,
 minWidth: 200,
 buttons: Ext.Msg.OK
 });
 }
 }]
 });

 tree.getRootNode().expand(true);
 (function() {
 var s = new Ext.tree.JsonTreeSerializer(tree).toString();
 console.log(s);
 console.log(Ext.decode(s));
 var s = new Ext.tree.XmlTreeSerializer(tree).toString();
 console.log(s);
 console.log(new DOMParser().parseFromString(s, "text/xml"));
 }).defer(1000);
 });
 */