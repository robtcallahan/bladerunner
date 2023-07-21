/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: SwitchTree.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/SwitchTree.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.SwitchTree = Ext.extend(Ext.ux.tree.EditorGrid, {
    initComponent: function () {
        Ext.apply(this, arguments);

        this.addEvents(
            // custom events
            /**
             * @event updatecr
             * Fires when a when a user selects 'Add Related Items to CR' from the contect menu
             * @param {Node} node // the node that was right-clicked
             */
            'cellclick',
            /**
             * @event osmsemail
             * Fires when a when a user selects 'Compose EMail to Subsystem OSMs' from the contect menu
             * @param {Node} node // the node that was right-clicked
             */
            'osmsemail',
            /**
             * @event brexport
             * Fires when a when a user selects 'Export Details' or 'Export WWNs' from the contect menu
             * @param {Node} node // the node that was right-clicked
             * @param type // either 'details' or 'wwwns'
             */
            'brexport'
        );

        this.switchesStore = new Ext.data.Store({
            proxy:  new Ext.data.HttpProxy(
                {
                    url:        'php/switch_search.php',
                    mycallback: function () {
                    }
                }),
            reader: new Ext.data.JsonReader(
                {
                    root:          'data',
                    totalProperty: 'total'
                }, [
                    {name: 'name', type: 'string'},
                    {name: 'node', type: 'string'}
                ])
        });

        // define the data store for the grid
        this.loader = new Ext.ux.tree.TreeGridLoader({
            dataUrl:         'php/get_switch_tree.php',
            baseParams:      {
                dataType: 'treegrid',
                node:     'root'
            },
            treeGrid:        this,
            preloadChildren: true,
            listeners:       {
                scope: this,
                load:  this.treeLoaded
            }
        });

        this.toolbar = new Ext.Toolbar({
            items: [
                {
                    xtype: 'tbtext',
                    text:  'Search Arrays, Hosts or VMs: '
                },
                {
                    xtype:          'combo',
                    id:             'switchSearch',
                    name:           'switchSearch',
                    forceSelection: true,
                    triggerAction:  'all',
                    minChars:       3,
                    mode:           'remote',
                    store:          this.switchesStore,
                    valueField:     'node',
                    displayField:   'name',
                    width:          200,
                    listeners:      {
                        scope:  this,
                        select: this.hostSelected
                    }
                }
            ]
        });

        // define the column model for the grid
        this.columns = [
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col1',
                width:     330,
                sortable:  false,
                align:     'left',
                tpl:       new Ext.XTemplate('{col1:this.format}', {
                    format: function (col1, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col1 + '</b>';
                        }
                        else {
                            return col1;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col2',
                sortable:  false,
                width:     250,
                align:     'left',
                tpl:       new Ext.XTemplate('{col2:this.format}', {
                    format: function (col2, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col2 + '</b>';
                        }
                        else {
                            return col2;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col3',
                sortable:  false,
                width:     170,
                align:     'left',
                tpl:       new Ext.XTemplate('{col3:this.format}', {
                    format: function (col3, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col3 + '</b>';
                        }
                        else {
                            return col3;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col4',
                sortable:  false,
                width:     200,
                align:     'left',
                tpl:       new Ext.XTemplate('{col4:this.format}', {
                    format: function (col4, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col4 + '</b>';
                        }
                        else if (col4 === null) {
                            return "";
                        }
                        else {
                            return col4;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col5',
                sortable:  false,
                width:     180,
                align:     'left',
                tpl:       new Ext.XTemplate('{col5:this.format}', {
                    format: function (col5, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col5 + '</b>';
                        }
                        else {
                            return col5;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col6',
                sortable:  false,
                width:     180,
                align:     'left',
                tpl:       new Ext.XTemplate('{col6:this.format}', {
                    format: function (col6, rec) {
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col6 + '</b>';
                        }
                        else {
                            return col6;
                        }
                    }
                })
            }
        ];

        this.root = new Ext.tree.AsyncTreeNode({
            text:      'Switches',
            draggable: false,
            id:        'root',
            expanded:  true
        });

        // apply config
        Ext.apply(this, {
            enableSort:   false,
            useArrows:    true,
            rootVisible:  false,
            singleExpand: false,

            trackMouseOver: true,

            autoScroll: false, // scrolls automatically. true will add a second scroll bar
            margins:    '0 0 0 0',
            loadMask:   {
                msg: "Loading..."
            },

            loader:  this.loader,
            columns: this.columns,
            root:    this.root,

            tbar: this.toolbar,

            listeners: {
                scope:       this,
                contextmenu: this.showContextMenu
            }
        });

        // call parent
        BRunner.SwitchTree.superclass.initComponent.apply(this, arguments);
    },

    treeLoaded: function (loader, node, response) {
        return;

        // the rest of this is for development only. It expands nodes so you don't have to
        if (node.id !== "root") return;

        var node = this.getNodeById("1");

        node.expand(false, true, function () {
            var node = this.getNodeById("1/blades");
            node.expand(false, true, function () {
                var node = this.getNodeById("1/blades/3");
                node.expand();
                /*
                 node.expand(false, true, function() {
                 var node = this.getNodeById("1/blades/1/hosts");
                 node.expand(false, true, function() {
                 var node = this.getNodeById("1/blades/1/arrays");
                 node.expand();
                 }, this);
                 }, this);
                 */
            }, this);
        }, this);
    },

    hostSelected: function (combo, record, index) {
        var nodeId = record.data.node,
            nodeElements,
            nodeIdString,
            node;

        if (nodeId === "") {
            Ext.Msg.show({
                title:   'ERROR',
                msg:     "This is an unknown node",
                buttons: Ext.Msg.OK,
                icon:    Ext.MessageBox.ERROR
            });
            return;
        }

        // clear previous search
        this.clearSearch();

        this.expandForSearch(nodeId, 0);
    },

    clearSearch: function () {
        var textnode;

        if (this.highlightedNode) {
            textNode = this.highlightedNode.getUI().textNode;
            textNode.innerHTML = this.highlightedNode.originalText;
        }
    },

    expandForSearch: function (nodeId, depth) {
        var nodeElements = nodeId.split("/"),
            nodeIdString = "",
            node;

        if (depth === nodeElements.length) {
            this.highlightNode(nodeId);
            return;
        }

        for (var i = 0; i <= depth; i++) {
            if (i !== 0) nodeIdString += "/";
            nodeIdString += nodeElements[i];
        }
        node = this.getNodeById(nodeIdString);
        depth++;

        node.expand(false, true, function (node) {
            this.expandForSearch(nodeId, depth);
        }, this);
    },

    highlightNode: function (nodeId) {
        var node = this.getNodeById(nodeId),
            textNode = node.getUI().textNode;


        // save the original text so that we can restore it later
        if (typeof node.originalText === "undefined") {
            node.originalText = textNode.innerHTML;
        }

        // add the span with class to highlight the text
        textNode.innerHTML = '<span class="stringHighlight">' + textNode.innerHTML + '</span>';

        // insure the node is visible
        node.select();

        // save the node so we can unhighlight if another search is called
        this.highlightedNode = node;
    },

    showContextMenu: function (node, e) {
        var type = node.attributes.type;

        e.stopEvent();

        // check for appropriate row type and return if not. eg. a header row or empty slot
        if (type === "Header" || type === "Empty Slot") return;

        // define the contenxt senstive menu
        menu = new Ext.menu.Menu({
            shadow: 'drop',
            items:  [
                {
                    xtype:   'menutextitem',
                    text:    '<b>' + node.attributes.col1 + ' ' + type + ' Menu</b>',
                    iconCls: 'none'
                },
                {
                    text:     'Expand All',
                    iconCls:  'expand-all',
                    disabled: false,
                    scope:    this,
                    handler:  function () {
                        node.expand(true, true);
                        menu.hide();
                    }
                },
                {
                    text:     'Collapse All',
                    iconCls:  'collapse-all',
                    disabled: false,
                    scope:    this,
                    handler:  function () {
                        node.collapse(true, true);
                        menu.hide();
                    }
                },
                {
                    text:     'Export Details',
                    iconCls:  'export',
                    disabled: !(type === "switch"
                        || type === "arrays-folder"
                        || type === "hosts-folder"
                        || type === "swblades-folder"
                        || type === "swblade-folder"
                        || type === "swblade-arrays-folder"
                        || type === "swblade-hosts-folder"),
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('brexport', this, node, '');
                        menu.hide();
                    }
                },
                {
                    text:     'Add Related Items to CR',
                    iconCls:  'update-cr',
                    disabled: type !== "switch",
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('updatecr', this, node);
                        menu.hide();
                    }
                },
                {
                    text:     'Compose EMail to Subsystem OSMs',
                    iconCls:  'email',
                    disabled: true,
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('osmsemail', this, node);
                        menu.hide();
                    }
                }
            ]
        });

        // find out where we clicked and display the menu close by
        coords = e.getXY();
        menu.showAt([coords[0] + 5, coords[1] + 5]);
    }
});
