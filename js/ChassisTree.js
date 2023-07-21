/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: ChassisTree.js 82520 2014-01-07 20:06:34Z rcallaha $
 * $Date: 2014-01-07 15:06:34 -0500 (Tue, 07 Jan 2014) $
 * $Author: rcallaha $
 * $Revision: 82520 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/ChassisTree.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.ChassisTree = Ext.extend(Ext.ux.tree.EditorGrid, {
    view:              "switch",

    reservationWin: null, // using this instance variable since passing the win as a param didn't work. it lost it's DOM

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

        this.hostsStore = new Ext.data.Store({
            baseParams: {
                view: this.view
            },
            proxy:      new Ext.data.HttpProxy({
                url:        'php/chassis_search.php',
                mycallback: function () {
                }
            }),
            reader:     new Ext.data.JsonReader({
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
                    text: ' View &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        plain: true,
                        items: [
                            {
                                //xtype: 'menucheckitem',
                                text:      'Show Reservations',
                                id:        "view-menu-reservations",
                                //tooltip: 'Export all blades and WWNs',
                                //iconCls: 'export',
                                listeners: {
                                    scope: this,
                                    click: this.showReservations
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      'Distribution Switch',
                                id:        "view-menu-switch",
                                checked:   true,
                                //tooltip: 'Export all blades and WWNs',
                                //iconCls: 'export',
                                group:     'view',
                                listeners: {
                                    scope: this,
                                    click: this.onViewCheck
                                }
                            },
                            {
                                xtype:     'menucheckitem',
                                text:      'Chassis',
                                id:        "view-menu-chassis",
                                //tooltip: 'Export all blades and WWNs',
                                //iconCls: 'export',
                                group:     'view',
                                listeners: {
                                    scope: this,
                                    click: this.onViewCheck
                                }
                            }
                        ]
                    }
                },
                {
                    xtype: 'tbspacer'
                },
                {
                    xtype: 'tbtext',
                    text:  'Search Chassis, MPs, Hosts or WWNs: '
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
            dataUrl:         'php/get_chassis_tree.php',
            baseParams:      {
                dataType: 'treegrid',
                node:     'root-distsw'
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
                        var r, tip, hyperError = "";

                        if (col1 === null) col1 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col1 + '</b>';
                        }
                        else if (rec.type === "vm") {
                            if (rec.iconCls === "vm-excep") {
                                return '<span ext:qtitle="VM w/Exception" ext:qtip="' + rec.vmException + '">' + col1 + '</span>';
                            }
                            else {
                                return '<span ext:qtitle="VM" ext:qtip=" ">' + col1 + '</span>';
                            }
                        }
                        else if (rec.type === "blade") {
                            r = rec.bladeRes;

                            // check to see if this is a hypervisor and if it was queried
                            // if not queried, then we'll marked this on the UI
                            if (rec.isHyper && rec.isCoreHyper && !rec.hyperQueried) {
                                hyperError = ' &nbsp; <span class="not-queried">(!Queried-connection error)</span>';
                            } else if (rec.isHyper && !rec.isCoreHyper && !rec.hyperQueried) {
                                hyperError = ' &nbsp; <span class="not-queried">(!Queried-not in Core)</span>';
                            }

                            if (rec.iconCls === "blade-active") {
                                return '<span ext:qtitle="Active Blade" ext:qtip=" ">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-active-excep") {
                                return '<span ext:qtitle="Active Blade w/Exception" ext:qtip="' + rec.bladeException + '">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-inventory") {
                                return '<span ext:qtitle="Inventory Blade" ext:qtip=" ">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-inventory-excep") {
                                return '<span ext:qtitle="Inventory Blade w/Exception" ext:qtip="' + rec.bladeException + '">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-spare") {
                                return '<span ext:qtitle="Spare Blade" ext:qtip=" ">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-reserved") {
                                if (r.taskNumber !== "") {
                                    tip = "Task: " + r.taskNumber + "<br>" +
                                        "Project: " + r.projectName + "<br>" +
                                        "Short Descr: " + r.taskShortDescr + "<br>" +
                                        "Resrved by: " + r.userReserved + " on " + r.dateReserved + "<br>" +
                                        "Updated by: " + r.userUpdated + " on " + r.dateUpdated;
                                } else {
                                    tip = "Project: " + r.projectName + "<br>" +
                                        "Resrved by: " + r.userReserved + " on " + r.dateReserved + "<br>" +
                                        "Updated by: " + r.userUpdated + " on " + r.dateUpdated;
                                }
                                return '<span ext:qtitle="Reserved Blade" ext:qtip="' + tip + '">' + col1 + '</span>' + hyperError;
                            }
                            else if (rec.iconCls === "blade-reserved-excep") {
                                if (r.taskNumber !== "") {
                                    tip = "Task: " + r.taskNumber + "<br>" +
                                        "Project: " + r.projectName + "<br>" +
                                        "Short Descr: " + r.taskShortDescr + "<br>" +
                                        "Resrved by: " + r.userReserved + " on " + r.dateReserved + "<br>" +
                                        "Updated by: " + r.userUpdated + " on " + r.dateUpdated + "<br>" +
                                        rec.bladeException;
                                } else {
                                    tip = "Project: " + r.projectName + "<br>" +
                                        "Resrved by: " + r.userReserved + " on " + r.dateReserved + "<br>" +
                                        "Updated by: " + r.userUpdated + " on " + r.dateUpdated;
                                }
                                return '<span ext:qtitle="Reserved Blade w/Exception" ext:qtip="' + tip + '">' + col1 + '</span>' + hyperError;
                            }
                            else {
                                return col1 + hyperError;
                            }
                        }
                        else if (rec.type === "emptySlot") {
                            if (rec.iconCls === "blade-empty") {
                                return '<span ext:qtitle="Empty Slot" ext:qtip=" ">' + col1 + '</span>';
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
                width:     180,
                align:     'left',
                tpl:       new Ext.XTemplate('{col2:this.format}', {
                    format: function (col2, rec) {
                        if (col2 === null) col2 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col2 + '</b>';
                        }
                        else if (rec.col1 === "Virtual Hosts") {
                            if (col2 !== "") {
                                return '<span class="not-queried">' + col2 + '</span>';
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
                        if (col3 === null) col3 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            if (typeof rec.col3tip !== "undefined") {
                                return '<b ext:qtitle="' + col3 + '" ext:qtip="' + rec.col3tip + '">' + col3 + '</b>';
                            }
                            else {
                                return '<b>' + col3 + '</b>';
                            }
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
                        if (col4 === null) col4 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            if (typeof rec.col4tip !== "undefined") {
                                return '<b ext:qtitle="' + col4 + '" ext:qtip="' + rec.col4tip + '">' + col4 + '</b>';
                            }
                            else {
                                return '<b>' + col4 + '</b>';
                            }
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
                width:     100,
                align:     'right',
                tpl:       new Ext.XTemplate('{col5:this.format}', {
                    format: function (col5, rec) {
                        if (col5 === null) col5 = "";

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
                width:     80,
                align:     'right',
                tpl:       new Ext.XTemplate('{col6:this.format}', {
                    format: function (col6, rec) {
                        if (col6 === null) col6 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
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
                        if (col7 === null) col7 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col7 + '</b>';
                        }
                        else {
                            if (rec.col7 === "Unknown") {
                                return '<img ext:qtitle="Status Unknown" ext:qtip="Status is Unknown according to HP SIM" height=12 width=12 src="resources/images/icons/status_unknown.gif" />';
                            }
                            else if (rec.col7 === "Critical") {
                                return '<img ext:qtitle="Status Critical" ext:qtip="Status is Critical according to HP SIM" src="resources/images/icons/status_critical.gif" />';
                            }
                            else if (rec.col7 === "Major") {
                                return '<img ext:qtitle="Status Major" ext:qtip="Status is Major according to HP SIM" src="resources/images/icons/status_major.gif" />';
                            }
                            else if (rec.col7 === "Minor") {
                                return '<img ext:qtitle="Status Minor" ext:qtip="Status is Minor according to HP SIM" src="resources/images/icons/status_minor.gif" />';
                            }
                            else if (rec.col7 === "Normal") {
                                return '<img ext:qtitle="Status Normal" ext:qtip="Status is Normal according to HP SIM" src="resources/images/icons/status_normal.gif" />';
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
                        if (col8 === null) col8 = "";

                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col8 + '</b>';
                        }
                        else {
                            if (rec.col8 === "Unknown") {
                                return '<img ext:qtitle="Status Unknown" ext:qtip="Status is Unknown according to HP SIM" height=12 width=12 src="resources/images/icons/status_unknown.gif" />';
                            }
                            else if (rec.col8 === "Critical") {
                                if (typeof rec.col8type !== "undefined" && rec.col8type === "power-status") {
                                    return '<img ext:qtitle="Powered Off" ext:qtip="Indicates the blade is powered off." src="resources/images/icons/status_critical.gif" />';
                                }
                                else {
                                    return '<img ext:qtitle="Status Critical" ext:qtip="Status is Critical according to HP SIM" src="resources/images/icons/status_critical.gif" />';
                                }
                            }
                            else if (rec.col8 === "Major") {
                                return '<img ext:qtitle="Status Major" ext:qtip="Status is Major according to HP SIM" src="resources/images/icons/status_major.gif" />';
                            }
                            else if (rec.col8 === "Minor") {
                                return '<img ext:qtitle="Status Minor" ext:qtip="Status is Minor according to HP SIM" src="resources/images/icons/status_minor.gif" />';
                            }
                            else if (rec.col8 === "Normal") {
                                if (typeof rec.col8type !== "undefined" && rec.col8type === "power-status") {
                                    return '<img ext:qtitle="Powered On" ext:qtip="Indicates the blade is powered on." src="resources/images/icons/status_normal.gif" />';
                                }
                                else {
                                    return '<img ext:qtitle="Status Normal" ext:qtip="Status is Normal according to HP SIM" src="resources/images/icons/status_normal.gif" />';
                                }
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
                        if (col9 === null) col9 = "";

                        //rec.loader.treeGrid.columns[8].editor.disabled(true);

                        if (typeof rec.header !== "undefined" && rec.header) {
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

        this.root = new Ext.tree.AsyncTreeNode({
            id:        'root-distsw',
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
            root:    this.root,

            tbar: this.toolbar,

            listeners: {
                scope:       this,
                contextmenu: this.showContextMenu,
                beforeedit:  this.beforeEdit,
                afteredit:   this.saveComments
            }
        });

        // call parent
        BRunner.ChassisTree.superclass.initComponent.apply(this, arguments);
    },

    /**
     * only nodes with attr editable=true can be edited.
     * note the only column editor is set on col9
     */
    beforeEdit: function (arg) {
        var attrs = arg.node.attributes;
        return typeof attrs.editable !== "undefined" && attrs.editable;
    },

    onViewCheck: function (item, e) {
        e.stopEvent();

        if (item.id === "view-menu-chassis") {
            this.view = "chassis";
            this.hostsStore.baseParams.view = this.view;

            var root = new Ext.tree.AsyncTreeNode({
                id:        'root-chassis',
                draggable: false,
                expanded:  true
            });
            this.loader.baseParams.node = "root-chassis";
        }
        else {
            this.view = "switch";
            this.hostsStore.baseParams.view = this.view;

            var root = new Ext.tree.AsyncTreeNode({
                id:        'root-distsw',
                draggable: false,
                expanded:  true
            });
            this.loader.baseParams.node = "root-distsw";
        }

        this.setRootNode(root);
        //this.getLoader().load(this.getRootNode());
    },

    treeLoaded: function (loader, node, response) {
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

        // Next lines of code are for automatic expansion when in development for quick testing
        if (node.id !== "root-distsw") return;

        var distSw = "Sterling General Purpose",
            node = this.getNodeById(distSw);

        node.expand(false, true, function () {
            var node = this.getNodeById("Sterling General Purpose/2901");
            node.expand(false, true, function () {
                var node = this.getNodeById("Sterling General Purpose/2901/blades");
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

    goToChassis: function(chassisId) {
        if (this.reservationsWindow) this.reservationsWindow.close();
        Ext.getCmp('tab-panel').setActiveTab(0);

        Ext.Ajax.request({
            url:             'php/get_chassis_nodeid.php',
            params:          {
                chassisId:   chassisId
            },
            scope:           this,
            mycallback:      function (json, options) {
                this.clearSearch();
                this.expandForSearch(json.nodeId, 0);
            }
        });
    },

    goToMgmtProcessor: function(mpId) {
        if (this.reservationsWindow) this.reservationsWindow.close();
        Ext.getCmp('tab-panel').setActiveTab(0);

        Ext.Ajax.request({
            url:             'php/get_mgmt_processor_nodeid.php',
            params:          {
                mpId:   mpId
            },
            scope:           this,
            mycallback:      function (json, options) {
                this.clearSearch();
                this.expandForSearch(json.nodeId, 0);
            }
        });
    },

    goToBlade: function(bladeId) {
        if (this.reservationsWindow) this.reservationsWindow.close();
        Ext.getCmp('tab-panel').setActiveTab(0);

        Ext.Ajax.request({
            url:             'php/get_blade_nodeid.php',
            params:          {
                bladeId:   bladeId
            },
            scope:           this,
            mycallback:      function (json, options) {
                this.clearSearch();
                this.expandForSearch(json.nodeId, 0);
            }
        });
    },

    goToVm: function(vmId) {
        Ext.getCmp('tab-panel').setActiveTab(0);

        Ext.Ajax.request({
            url:             'php/get_vm_nodeid.php',
            params:          {
                vmId: vmId
            },
            scope:           this,
            mycallback:      function (json, options) {
                this.clearSearch();
                this.expandForSearch(json.nodeId, 0);
            }
        });
    },

    hostSelected: function (combo, record, index) {
        var nodeId = record.data.node,
            nodeElements,
            nodeIdString,
            node,
            depth = 0;

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

        // if this is the chassis view, then we need to skip the first level which is the Dist Switch in the switch view as
        // this level in the tree doesn't exist in this view. So we start at a depth of 1 instead of 0 (default). make sense?
        if (this.view === "chassis") {
            depth = 1;
        }
        this.expandForSearch(nodeId, depth);
    },

    clearSearch: function () {
        var textnode;

        if (this.highlightedNode && this.highlightedNode.getUI()) {
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
        var type = node.attributes.type,
            iconCls = node.attributes.iconCls;

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
                    text:     'Reservations',
                    iconCls:  'reservations',
                    disabled: type === "blade" && iconCls.match(/inventory|reserved/) ? false : true,
                    menu:     {
                        xtype: 'menu',
                        items: [
                            {
                                text:     'New',
                                iconCls:  'reservation-new',
                                disabled: iconCls.match(/inventory/) ? false : true,
                                scope:    this,
                                handler:  function () {
                                    this.promptForReservation(node, "new");
                                    menu.hide();
                                }
                            },
                            {
                                text:     'Modify',
                                iconCls:  'reservation-modify',
                                disabled: iconCls.match(/reserved/) ? false : true,
                                scope:    this,
                                handler:  function () {
                                    this.promptForReservation(node, "modify");
                                    menu.hide();
                                }
                            },
                            {
                                text:     'Complete',
                                iconCls:  'reservation-complete',
                                disabled: iconCls.match(/reserved/) ? false : true,
                                scope:    this,
                                handler:  function () {
                                    this.completeReservation(node);
                                    menu.hide();
                                }
                            },
                            {
                                text:     'Cancel',
                                iconCls:  'reservation-cancel',
                                disabled: iconCls.match(/reserved/) ? false : true,
                                scope:    this,
                                handler:  function () {
                                    this.cancelReservation(node);
                                    menu.hide();
                                }
                            }
                        ]
                    }
                },
                {
                    text:     'Export Details',
                    iconCls:  'export',
                    disabled: !(type === "chassis" || type === "distSwitch"),
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('brexport', this, node, 'details');
                        menu.hide();
                    }
                },
                {
                    text:     'Export WWNs',
                    iconCls:  'export',
                    disabled: !(type === "chassis" || type === "distSwitch"),
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('brexport', this, node, 'wwns');
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
                    text:     'Add Related Items to CR',
                    iconCls:  'update-cr',
                    disabled: !(type === "chassis" || type === "blade"),
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('updatecr', this, node);
                        menu.hide();
                    }
                },
                {
                    text:     'Compose EMail to Subsystem OSMs',
                    iconCls:  'email',
                    disabled: !(type === "chassis" || type === "blade"),
                    scope:    this,
                    handler:  function () {
                        this.fireEvent('osmsemail', this, node);
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

    listBladesVms: function (node)
    {
        Ext.Ajax.request({
            url:        'php/list_blades_and_vms.php',
            params:     {
                'chassisId': node.attributes.dbId
            },
            scope:      this,
            mycallback: function (json, options) {
                var win = new Ext.Window({
                    title:      'List of Blades and VMs',
                    modal:      true,
                    constrain:  true,
                    height:     500,
                    width:      200,
                    autoScroll: false,
                    items:      [
                        {
                            id:            'host-list',
                            xtype:         'textarea',
                            selectOnFocus: true,
                            readOnly:      true,
                            height:        450,
                            width:         190,
                            value:         json.hostList
                        }
                    ],
                    buttons:    [
                        {
                            text:    'Select All',
                            handler: function () {
                                Ext.get('host-list').focus();
                            }
                        },
                        {
                            text:    'Close',
                            handler: function () {
                                win.close();
                            }
                        }
                    ]
                });
                win.render(document.body);
                win.center();
                win.show();
            }
        });
    },

    assignChassis: function (node)
    {
        win = new Ext.Window({
            title:      'Assign Chassis To Distribution Switch',
            modal:      true,
            constrain:  true,
            height:     180,
            width:      250,
            autoScroll: true,
            layout:     'border',
            items:      [
                {
                    region:  'north',
                    height:  '80',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    html:    'Assigning Chassis: ' + node.attributes.col1,
                    margins: '10 0 10 5'
                },
                {
                    region:  'center',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    margins: '10 0 0 5',
                    items:   [
                        {
                            xtype:          'combo',
                            label:          'Search',
                            id:             'chassisId',
                            name:           'chassisId',
                            forceSelection: true,
                            triggerAction:  'all',
                            minChars:       3,
                            mode:           'remote',
                            store:          this.distSwitchStore,
                            emptyText:      'Select Distribution Switch',
                            valueField:     'name',
                            displayField:   'name',
                            width:          220
                        }
                    ]
                }
            ],
            buttons:    [
                {
                    text:    'Cancel',
                    handler: function () {
                        win.close();
                    }
                },
                {
                    text:    'Submit',
                    scope:   this,
                    handler: function () {
                        this.assignDistSwitch(node, Ext.getCmp('chassisId').getValue());
                        win.close();
                    }
                }
            ]
        });

        win.render(document.body);
        win.center();
        win.show();
    },

    assignDistSwitch: function (node, distSwName)
    {
        Ext.Ajax.request({
            url:        'php/assign_chassis_to_dist_switch.php',
            params:     {
                'chassisId':  node.attributes.dbId,
                'switchName': distSwName
            },
            scope:      this,
            mycallback: function (json, options) {
                this.notify.setAlert(this.notify.STATUS_INFO, "The chassis has been assigned.");

                this.loader.load(this.getNodeById("Unassigned"), function (node) {
                    node.expand();
                }, this);

                this.loader.load(this.getNodeById(distSwName), function (node) {
                    node.expand(false, true, function (node) {
                        var movedNode = this.getNodeById(distSwName + "/" + options.params.chassisId),
                            tNode = movedNode.getUI().getTextEl(),
                            el = new Ext.Element(tNode);
                        movedNode.select();
                        el.highlight();
                    }, this);
                }, this);
            }
        });
    },

    promptForReservation: function (node, action)
    {
        var bladeRes = node.attributes.bladeRes,
            win;

        win = new Ext.Window({
            title:      'Reserve Blade',
            modal:      true,
            constrain:  true,
            closable:   false,
            disabled:   true,
            height:     250,
            width:      400,
            autoScroll: true,
            layout:     'border',
            items:      [
                {
                    region:  'north',
                    height:  '80',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    html:    'About to reserve blade in slot ' + node.attributes.col3 + ' named ' + node.attributes.col1 + '...<br><br>' +
                        'Please specify the ServiceNow Task, if desired, and the Project name<br><br>',
                    margins: '10 0 3 5'
                },
                {
                    region:  'center',
                    xtype:   'form',
                    baseCls: 'window-panel',
                    margins: '10 0 5 5',
                    items:   [
                        {
                            xtype:      'textfield',
                            fieldLabel: 'Project Name<span style="color:#ff0000;">*</span>',
                            id:         'projectName',
                            name:       'projectName',
                            width:      180
                        },
                        {
                            xtype:          'combo',
                            fieldLabel:     'Core Catalog Task',
                            id:             'taskSysId',
                            name:           'taskSysId',
                            forceSelection: true,
                            triggerAction:  'all',
                            minChars:       3,
                            mode:           'remote',
                            store:          this.tasksStore,
                            valueField:     'sysId',
                            displayField:   'number',
                            width:          200,
                            tpl:            '<tpl for="."><div ext:qtip="Created On: {openedAt}<br>Created By: {openedBy}<br>Descr: {shortDescr}" class="x-combo-list-item">{number}</div></tpl>'
                        }
                    ]
                },
                {
                    region:  'south',
                    height:  '50',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    html:    '<span style="color:#ff0000;">*</span>Indicates required fields',
                    margins: '10 0 3 5'
                }
            ],
            buttons:    [
                {
                    text:    'Cancel',
                    cls:     'window-button',
                    handler: function () {
                        win.close();
                    }
                },
                {
                    text:    'Save',
                    cls:     'window-button',
                    scope:   this,
                    handler: function () {
                        var taskId = Ext.getCmp('taskSysId').getValue(),
                            projectName = Ext.getCmp('projectName').getValue();

                        // make sure that we have a value for projectName
                        if (projectName == "") {
                            Ext.Msg.show({
                                title:   'ERROR',
                                msg:     "You must specify a project name for this reservation.",
                                buttons: Ext.Msg.OK,
                                icon:    Ext.MessageBox.ERROR
                            });
                            return;
                        }

                        // check if the SN Task has changed. If so, let's warn the user so they are aware
                        if (action === "modify" && bladeRes.taskSysId !== taskId) {
                            Ext.Msg.show({
                                title:   'Changing ServiceNow Task',
                                msg:     'Are you sure you want change the ServiceNow Task for this reservation?',
                                buttons: Ext.Msg.YESNO,
                                scope:   this,
                                fn:      function (button) {
                                    if (button === 'yes') {
                                        this.updateReservation(node, taskId, projectName, action);
                                        win.close();
                                    }

                                }
                            });
                        }
                        else {
                            // make the update happen
                            this.updateReservation(node, taskId, projectName, action);
                            win.close();
                        }
                    }
                }
            ]
        });

        win.render("body");
        win.center();
        win.show();

        this.reservationWin = win;

        // if the action is a modify, then we want to populate the fields with
        // their current values. So, we'll create an onload listener to do that
        // after we load the combo box's data store
        if (action === "modify") {
            this.tasksStore.on("load", function () {
                // use "call" to pass a couple of params
                this.setCurrentTaskId.call(this, [node]);
            }, this);
            this.tasksStore.load();
        }
        else {
            win.enable();
        }
    },

    setCurrentTaskId: function (args)
    {
        var node = args[0],
            bladeRes = node.attributes.bladeRes;

        this.reservationWin.enable();
        Ext.getCmp("taskSysId").setValue(bladeRes.taskSysId);
        Ext.getCmp("projectName").setValue(bladeRes.projectName);
    },

    updateReservation: function (node, taskSysId, projectName, action)
    {
        // first, split the node id by "/", remove the last element and put back together
        // this allows us to reload and expand up to, but not including, the blade
        var ar = node.id.split("/"),
            nodeId;

        ar.splice(-1, 1);
        nodeId = ar.join("/");

        Ext.Ajax.request({
            url:             'php/blade_reservation.php',
            params:          {
                nodeType:    node.attributes.type,
                dbId:        node.attributes.dbId,
                taskSysId:   taskSysId,
                projectName: projectName,
                action:      action
            },
            scope:           this,
            mycallback:      function (json, options) {
                this.loader.load(this.getNodeById(nodeId), function (node) {
                    node.expand();
                }, this);
            },
            myerrorcallback: function (json, options) {
                Ext.Msg.show({
                    title:   'ERROR: Update CR',
                    msg:     "The server returned an error. The blade may not have been reserved.",
                    buttons: Ext.Msg.OK,
                    icon:    Ext.MessageBox.ERROR
                });
            }
        });
    },

    completeReservation: function (node)
    {
        var bladeRes = node.attributes.bladeRes;

        Ext.Msg.show({
            title:   'Complete Reservation',
            msg:     'Are you sure you want to mark this reservation as complete?',
            buttons: Ext.Msg.YESNO,
            scope:   this,
            fn:      function (button) {
                if (button === 'yes') {
                    this.updateReservation(node, bladeRes.taskSysId, bladeRes.projectName, "complete");
                }
            }
        });
    },

    cancelReservation: function (node)
    {
        var bladeRes = node.attributes.bladeRes;

        Ext.Msg.show({
            title:   'Cancel Reservation',
            msg:     'Are you sure you want to cancel this reservation?',
            buttons: Ext.Msg.YESNO,
            scope:   this,
            fn:      function (button) {
                if (button === 'yes') {
                    this.updateReservation(node, bladeRes.taskSysId, bladeRes.projectName, "cancel");
                }
            }
        });
    },

    showReservations: function () {
        reservationsGridPanel = new BRunner.BladeReservationsGridPanel();

        // create and show window
        this.reservationsWindow = new Ext.Window({
            title:      'Blade Reservations',
            modal:      true,
            constrain:  true,
            width:      1000,
            height:     500,
            layout:     'fit',
            closable:   true,
            border:     false,
            autoScroll: true,
            items:      [
                reservationsGridPanel
            ]
        });

        this.reservationsWindow.show();
        reservationsGridPanel.getStore().load();
    }
});
