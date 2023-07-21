/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: HostTree.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/HostTree.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.HostTree = Ext.extend(Ext.ux.tree.EditorGrid, {
    taskUpdateRTLog:   null,
    runnerUpdateRTLog: null,

    initComponent: function () {
        Ext.apply(this, arguments);

        this.highlightedNodeId = null;

        this.hostsStore = new Ext.data.Store({
            proxy:  new Ext.data.HttpProxy(
                {
                    url:        'php/chassis_search.php',
                    mycallback: function () {
                    }
                }),
            reader: new Ext.data.JsonReader(
                {
                    root:          'hosts',
                    totalProperty: 'total'
                }, [
                    {name: 'name', type: 'string'},
                    {name: 'node', type: 'string'}
                ])
        });


        this.distSwitchStore = new Ext.data.Store({
            proxy:  new Ext.data.HttpProxy(
                {
                    url:        'php/get_dist_switches.php',
                    mycallback: function () {
                    }
                }),
            reader: new Ext.data.JsonReader(
                {
                    root:          'switches',
                    totalProperty: 'total'
                }, [
                    {name: 'name', type: 'string'}
                ])
        });

        this.toolbar = new Ext.Toolbar({
            items: [
                {
                    text: ' File &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        items: [
                            {
                                text:    'Export WWNs',
                                tooltip: 'Export all chassis, blades and WWNs',
                                iconCls: 'export',
                                handler: function () {
                                    this.exportExcel("all", "wwns");
                                },
                                scope:   this
                            }
                        ]
                    }
                },
                {
                    xtype: 'tbspacer'
                },
                {
                    xtype: 'tbspacer'
                },
                {
                    xtype: 'tbtext',
                    text:  'Search Hosts or WWNs: '
                },
                {
                    xtype:          'combo',
                    id:             'hostSearch',
                    name:           'hostSearch',
                    forceSelection: true,
                    triggerAction:  'all',
                    minChars:       3,
                    mode:           'remote',
                    store:          this.hostsStore,
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

        // define the data store for the grid
        this.loader = new Ext.ux.tree.TreeGridLoader({
            dataUrl:         'php/get_host_tree.php',
            baseParams:      {
                dataType: 'treegrid',
                node:     'root'
            },
            treeGrid:        this,
            preloadChildren: true,
            clearOnLoad:     true,
            listeners:       {
                scope: this,
                load:  this.treeLoaded
            }
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
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col1 + '</b>';
                        }
                        else if (rec.type === "VM") {
                            if (rec.active === "1") {
                                return col1;
                            }
                            else if (rec.queried === "1") {
                                return '<span class="x-item-disabled">' + col1 + '</span>';
                            }
                            else {
                                return col1;
                            }
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
                width:     350,
                align:     'left',
                tpl:       new Ext.XTemplate('{col2:this.format}', {
                    format: function (col2, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col2 + '</b>';
                        }
                        else if (rec.col1 === "Virtual Hosts") {
                            if (col2 === "0") {
                                return '<span class="not-queried">Xen Master not queried; no access</span>';
                            }
                            else {
                                return "&nbsp;";
                            }
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
                width:     120,
                align:     'right',
                tpl:       new Ext.XTemplate('{col3:this.format}', {
                    format: function (col3, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col3 + '</b>';
                        }
                        else if (col3 === null) {
                            return '&nbsp;';
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
                width:     120,
                align:     'right',
                tpl:       new Ext.XTemplate('{col4:this.format}', {
                    format: function (col4, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
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
                width:     80,
                align:     'right',
                tpl:       new Ext.XTemplate('{col5:this.format}', {
                    format: function (col5, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
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
                width:     80,
                align:     'right',
                tpl:       new Ext.XTemplate('{col6:this.format}', {
                    format: function (col6, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col6 + '</b>';
                        }
                        else {
                            return col6;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col7',
                sortable:  false,
                width:     80,
                align:     'center',
                tpl:       new Ext.XTemplate('{col7:this.format}', {
                    format: function (col7, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col7 + '</b>';
                        }
                        else {
                            if (rec.col7 === "Unknown") {
                                return '<img height=12 width=12 src="resources/images/icons/status_unknown.gif" />';
                            }
                            else if (rec.col7 === "Critical") {
                                return '<img src="resources/images/icons/status_critical.gif" />';
                            }
                            else if (rec.col7 === "Major") {
                                return '<img src="resources/images/icons/status_major.gif" />';
                            }
                            else if (rec.col7 === "Minor") {
                                return '<img src="resources/images/icons/status_minor.gif" />';
                            }
                            else if (rec.col7 === "Normal") {
                                return '<img src="resources/images/icons/status_normal.gif" />';
                            }
                            else {
                                return col7;
                            }
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col8',
                width:     80,
                sortable:  false,
                align:     'center',
                tpl:       new Ext.XTemplate('{col8:this.format}', {
                    format: function (col8, rec) {
                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col8 + '</b>';
                        }
                        else {
                            if (rec.col8 === "Unknown") {
                                return '<img height=12 width=12 src="resources/images/icons/status_unknown.gif" />';
                            }
                            else if (rec.col7 === "Critical") {
                                return '<img src="resources/images/icons/status_critical.gif" />';
                            }
                            else if (rec.col8 === "Major") {
                                return '<img src="resources/images/icons/status_major.gif" />';
                            }
                            else if (rec.col8 === "Minor") {
                                return '<img src="resources/images/icons/status_minor.gif" />';
                            }
                            else if (rec.col8 === "Normal") {
                                return '<img src="resources/images/icons/status_normal.gif" />';
                            }
                            else {
                                return col8;
                            }
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col9',
                width:     250,
                sortable:  false,
                align:     'left',
                tpl:       new Ext.XTemplate('{col9:this.format}', {
                    format: function (col9, rec) {
                        //rec.loader.treeGrid.columns[8].editor.disabled(true);

                        if (typeof rec.header != "undefined" && rec.header) {
                            return '<b>' + col9 + '</b>';
                        }
                        else {
                            if (rec.col9 === null) {
                                return "";
                            }
                            else {
                                return col9;
                            }
                        }
                    }
                }),
                editor:    new Ext.form.TextArea({
                    name:       'comments',
                    allowBlank: true,
                    height:     100,
                    cls:        'x-tree-node'
                })
            }
        ];

        var root = new Ext.tree.AsyncTreeNode({
            id:        'root',
            draggable: false,
            expanded:  true
        });

        // apply config
        Ext.apply(this, {
            //hideHeaders: true,
            //enableHdMenu: false,
            enableSort:   false,
            useArrows:    true,
            rootVisible:  false,
            singleExpand: false,

            trackMouseOver: true,

            //stripeRows: true, // doesn't work
            autoScroll:     false, // scrolls automatically. true will add a second scroll bar
            margins:        '0 0 0 0',
            loadMask:       {
                msg: "Loading..."
            },

            loader:  this.loader,
            columns: this.columns,
            root:    root,

            tbar: this.toolbar,

            listeners: {
                scope:       this,
                contextmenu: this.showContextMenu,
                beforeedit:  this.beforeEdit,
                afteredit:   this.saveComments
            }
        });

        // call parent
        BRunner.HostTree.superclass.initComponent.apply(this, arguments);
    },

    /**
     * only nodes with attr editable=true can be edited.
     * note the only column editor is set on col9
     */
    beforeEdit: function (arg) {
        var attrs = arg.node.attributes;
        return typeof attrs.editable !== "undefined" && attrs.editable;
    },

    treeLoaded: function (loader, node, response) {
        return;

        var bladeFolder;

        // expand blades if chassis was expanded
        if (node.attributes.type === "chassis") {
            bladeFolder = this.getNodeById(node.id + "/blades");
            bladeFolder.expand();
        }

        // expand VMs if blade was expanded
        if (node.attributes.type === "blade") {
            bladeFolder = this.getNodeById(node.id + "/vms");
            if (bladeFolder) bladeFolder.expand();
        }
        return;

        if (node.id !== "root") return;

        var distSw = "Sterling OMS",
            node = this.getNodeById(distSw);

        node.expand(false, true, function () {
            var node = this.getNodeById("Sterling OMS/42");
            node.expand(false, true, function () {
                var node = this.getNodeById("Sterling OMS/42/blades");
                node.expand(false, true, function () {
                    //var node = this.getNodeById("Charlotte General Purpose/1808/blades/1829");
                    //node.expand();
                }, this);
            }, this);
        }, this);
    },

    saveComments: function (o) {
        var args = arguments,
            node = o.node,
            value = o.value,
            origValue = o.originalValue,
            row = o.row,
            col = o.column;

        if (value === origValue) return;

        Ext.get('status-indicator').show();

        Ext.Ajax.request({
            url:             'php/update_comments.php',
            params:          {
                nodeId:   node.attributes.dbId,
                nodeType: node.attributes.type,
                field:    'comments',
                value:    value
            },
            scope:           this,
            mycallback:      function (json, options) {
                //this.notify.setAlert(this.notify.STATUS_INFO, "Comments have been saved");
                this.markClean(node, col);
                Ext.get('status-indicator').hide();
            },
            myerrorcallback: function (json, options) {
                this.notify.setAlert(this.notify.STATUS_ERROR, "ERROR: " + json.errorText + "<br>Comments not saved.");
                node.set("col9", origValue);
                this.markClean(node, col);
                Ext.get('status-indicator').hide();
            }
        });
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
                    disabled: !(type === "chassis" || type === "distSwitch"),
                    scope:    this,
                    handler:  function () {
                        this.exportExcel(node, "details");
                        menu.hide();
                    }
                },
                {
                    text:     'Export WWNs',
                    iconCls:  'export',
                    disabled: !(type === "chassis" || type === "distSwitch"),
                    scope:    this,
                    handler:  function () {
                        this.exportExcel(node, "wwns");
                        menu.hide();
                    }
                },
                {
                    text:     'List Blades & VMs',
                    iconCls:  'list',
                    disabled: type !== "chassis",
                    scope:    this,
                    handler:  function () {
                        this.listBladesVms(node);
                        menu.hide();
                    }
                },
                {
                    text:     'Add Hosts to CR',
                    iconCls:  'update-cr',
                    disabled: !(type === "chassis" || type === "blade"),
                    scope:    this,
                    handler:  function () {
                        this.promptForCR(node);
                        menu.hide();
                    }
                },
                {
                    text:     'Compose EMail to Subsystem OSMs',
                    iconCls:  'email',
                    disabled: !(type === "chassis" || type === "blade"),
                    scope:    this,
                    handler:  function () {
                        this.getOsmEmails(node);
                        menu.hide();
                    }
                },
                {
                    text:     'Assign Chassis to Dist Switch',
                    iconCls:  'assign-chassis',
                    disabled: !(type === "chassis" && node.id.search(/Unassigned/) !== -1),
                    scope:    this,
                    handler:  function () {
                        this.assignChassis(node);
                        menu.hide();
                    }
                }
            ]
        });

        // find out where we clicked and display the menu close by
        coords = e.getXY();
        menu.showAt([coords[0] + 5, coords[1] + 5]);
    },

    /*
     * node = "all" || node
     * reportType = "details" || "wwns"
     */
    exportExcel:     function (node, reportType) {
        // create the form in HTML
        var html = "<span class='csv-message'>Generating CSV file...</span>\n",
            win,
            task;

        if (node === "all") {
            if (reportType === "wwns") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_chassis_wwns.php'>\n" +
                    "  <input type='hidden' name='nodeType' value='all' />\n" +
                    "  <input type='hidden' name='nodeDbId' value='none' />\n";
            }
        }
        else {
            if (reportType === "details") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_chassis_tree.php'>\n";
            }
            else if (reportType === "wwns") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_chassis_wwns.php'>\n";
            }
            html += "  <input type='hidden' name='nodeType' value='" + node.attributes.type + "' />\n" +
                "  <input type='hidden' name='nodeDbId' value='" + node.attributes.dbId + "' />\n";
        }
        html += "</form>\n";

        // open a window and submit the form
        win = new Ext.Window({
            modal:      true,
            constrain:  true,
            height:     60,
            width:      200,
            autoScroll: true,
            items:      [
                {
                    html: html
                }
            ]
        });
        win.render(document.body);
        win.center();
        win.show();

        task = new Ext.util.DelayedTask();
        task.delay(2000, this.submitExportForm, this, [win]);
    },

    submitExportForm: function (win) {
        Ext.get('exportForm').dom.submit();
        win.close();
    }

});
