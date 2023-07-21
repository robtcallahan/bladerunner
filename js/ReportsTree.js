/*******************************************************************************
 *
 * @class ReportTree
 * @extends Ext.ux.tree.EditorGrid
 *
 * ReportTree description_here
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.ReportsTree = Ext.extend(Ext.ux.tree.EditorGrid, {

    initComponent: function () {
        Ext.apply(this, arguments);

        this.showMinions = false;
        this.selectedReport = "vmwarevms"

        this.reportsMeta = {
            // xenvms report is not enabled
            xenvms: {
                title: 'Xen VMs',
                id: 'select-report-xenvms',
                colWidths: {
                    col1: 330,
                    col2: 180,
                    col3: 120,
                    col4: 120,
                    col5: 120,
                    col6: 120,
                    col7: 120,
                    col8: 120,
                    col9:  1,
                    col10: 1
                },
                colAlign: {
                    col1:  'left',
                    col2:  'left',
                    col3:  'right',
                    col4:  'right',
                    col5:  'right',
                    col6:  'right',
                    col7:  'right',
                    col8:  'right',
                    col9:  'right',
                    col10: 'right'
                }
            },
            vmwarevms: {
                title: 'VMware VMs',
                id: 'select-report-vmwarevms',
                colWidths: {
                    col1: 280,
                    col2: 120,
                    col3: 120,
                    col4: 140,
                    col5: 140,
                    col6: 220,
                    col7: 200,
                    col8: 100,
                    col9: 100,
                    col10:100,
                    col11:100
                },
                colAlign: {
                    col1:  'left',
                    col2:  'left',
                    col3:  'left',
                    col4:  'left',
                    col5:  'left',
                    col6:  'left',
                    col7:  'left',
                    col8:  'right',
                    col9:  'right',
                    col10: 'right',
                    col11: 'right'
                }
            }
        };

        this.toolbar = new Ext.Toolbar({
            items: [
                {
                    text: ' File &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        items: [
                            {
                                text:    'Export',
                                tooltip: 'Export flattened view of data',
                                iconCls: 'export',
                                handler: function () {
                                    this.exportExcel();
                                },
                                scope:   this
                            }
                        ]
                    }
                },
                {
                    text: ' Report &nbsp; ',
                    menu: {
                        xtype: 'menu',
                        items: [
                            /*
                             {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.xenvms.title,
                                id:        this.reportsMeta.xenvms.id,
                                checked:   !!(this.selectedReport === "xenvms"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            },
                             */
                            {
                                xtype:     'menucheckitem',
                                text:      this.reportsMeta.vmwarevms.title,
                                id:        this.reportsMeta.vmwarevms.id,
                                checked:   !!(this.selectedReport === "vmwarevms"),
                                group:     'report-type',
                                listeners: {
                                    scope: this,
                                    click: this.onReportCheck
                                }
                            }
                        ]
                    }
                },
                '->',
                {
                    xtype: 'tbtext',
                    id:    'report-title',
                    text:  this.reportsMeta[this.selectedReport].title,
                    cls:   'report-title'
                }
            ]
        });

        // define the data store for the grid
        this.loader = new Ext.ux.tree.TreeGridLoader({
            dataUrl:         'php/get_report_tree.php',
            baseParams:      {
                dataType: 'treegrid',
                node:     '/' + this.selectedReport
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
                sortable:  false,
                width:     this.reportsMeta[this.selectedReport].colWidths.col1,
                align:     this.reportsMeta[this.selectedReport].colAlign.col1,
                tpl:       new Ext.XTemplate('{col1:this.format}', {
                    format: function (col1, rec) {
                        var r, tip, hyperError = "";
                        if (col1 === null) col1 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col1 + '</b> &nbsp; ';
                        } else {
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col2,
                align:     this.reportsMeta[this.selectedReport].colAlign.col2,
                tpl:       new Ext.XTemplate('{col2:this.format}', {
                    format: function (col2, rec) {
                        if (col2 === null) col2 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col2 + '</b> &nbsp; ';
                        } else {
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col3,
                align:     this.reportsMeta[this.selectedReport].colAlign.col3,
                tpl:       new Ext.XTemplate('{col3:this.format}', {
                    format: function (col3, rec) {
                        if (col3 === null) col3 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col3 + '</b> &nbsp; ';
                        } else {
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col4,
                align:     this.reportsMeta[this.selectedReport].colAlign.col4,
                tpl:       new Ext.XTemplate('{col4:this.format}', {
                    format: function (col4, rec) {
                        if (col4 === null) col4 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col4 + '</b> &nbsp; ';
                        } else {
                            var html = '<td class="x-treegrid-col"><span class="x-tree-node-indent"></span>' +
                                '<img src="/ext/resources/images/default/s.gif" class="x-tree-ec-icon x-tree-elbow-plus">' +
                                '<img src="/ext/resources/images/default/s.gif" class="x-tree-node-icon blade" unselectable="on">' +
                                '<a hidefocus="on" class="x-tree-node-anchor" href="#" tabindex="1"><span unselectable="on">' + col4 + '</span></a></td>';
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col5,
                align:     this.reportsMeta[this.selectedReport].colAlign.col5,
                tpl:       new Ext.XTemplate('{col5:this.format}', {
                    format: function (col5, rec) {
                        if (col5 === null) col5 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col5 + '</b> &nbsp; ';
                        } else {
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col6,
                align:     this.reportsMeta[this.selectedReport].colAlign.col6,
                tpl:       new Ext.XTemplate('{col6:this.format}', {
                    format: function (col6, rec) {
                        if (col6 === null) col6 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col6 + '</b> &nbsp; ';
                        } else {
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
                width:     this.reportsMeta[this.selectedReport].colWidths.col7,
                align:     this.reportsMeta[this.selectedReport].colAlign.col7,
                tpl:       new Ext.XTemplate('{col7:this.format}', {
                    format: function (col7, rec) {
                        if (col7 === null) col7 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col7 + '</b> &nbsp; ';
                        } else if (typeof rec.type !== "undefined" && rec.type === "totals") {
                            return '<b>' + col7 + "</b> &nbsp; ";
                        } else {
                            return col7;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col8',
                width:     this.reportsMeta[this.selectedReport].colWidths.col8,
                sortable:  false,
                align:     this.reportsMeta[this.selectedReport].colAlign.col8,
                tpl:       new Ext.XTemplate('{col8:this.format}', {
                    format: function (col8, rec) {
                        if (col8 === null) col8 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col8 + "</b> &nbsp; ";
                        } else {
                            return col8;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col9',
                width:     this.reportsMeta[this.selectedReport].colWidths.col9,
                sortable:  false,
                align:     this.reportsMeta[this.selectedReport].colAlign.col9,
                tpl:       new Ext.XTemplate('{col9:this.format}', {
                    format: function (col9, rec) {
                        if (col9 === null) col9 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col9 + "</b> &nbsp; ";
                        } else {
                            return col9;
                        }
                    }
                })
            },
            {
                xtype:     'tgcolumn',
                header:    "",
                dataIndex: 'col10',
                width:     this.reportsMeta[this.selectedReport].colWidths.col10,
                sortable:  false,
                align:     this.reportsMeta[this.selectedReport].colAlign.col10,
                tpl:       new Ext.XTemplate('{col10:this.format}', {
                    format: function (col10, rec) {
                        if (col10 === null) col10 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col10 + "</b> &nbsp; ";
                        } else if (typeof rec.type !== "undefined" && rec.type === "totals") {
                            return '<b>' + col10 + "</b> &nbsp; ";
                        } else {
                            return col10 + " &nbsp;";
                        }
                    }
                })
            },
            {
                xtype: 'tgcolumn',
                header: "",
                dataIndex: 'col11',
                width: this.reportsMeta[this.selectedReport].colWidths.col11,
                sortable: false,
                align: this.reportsMeta[this.selectedReport].colAlign.col11,
                tpl: new Ext.XTemplate('{col11:this.format}', {
                    format: function(col11, rec) {
                        if (col11 === null) col11 = "";
                        if (typeof rec.header !== "undefined" && rec.header) {
                            return '<b>' + col11 + "</b> &nbsp; ";
                        } else if (typeof rec.type !== "undefined" && rec.type === "totals") {
                            return '<b>' + col11 + "</b> &nbsp; ";
                        } else {
                            return col11 + " &nbsp;";
                        }
                    }
                })
            }
        ];

        var root = new Ext.tree.AsyncTreeNode({
            id:        '/' + this.selectedReport,
            draggable: false,
            expanded:  true
        });

        // apply config
        Ext.apply(this, {
            enableSort:   false,
            useArrows:    true,
            rootVisible:  false,
            singleExpand: false,

            trackMouseOver: true,

            autoScroll:     false, // scrolls automatically. true will add a second scroll bar
            margins:        '0 0 0 0',
            loadMask:       {
                msg: "Loading..."
            },

            loader:  this.loader,
            columns: this.columns,
            root:    root,

            tbar: this.toolbar
        });

        // call parent
        BRunner.ReportsTree.superclass.initComponent.apply(this, arguments);
    },

    afterRender: function() {
        BRunner.ReportsTree.superclass.afterRender.apply(this, arguments);

        if (this.showMinions) {
            setTimeout(this.minions, 2000);
        }
    },

    onReportCheck: function (item, e) {
        e.stopEvent();

        var index = item.id.replace(/select-report-/, ''),
            reportMeta = this.reportsMeta[index],
            title = reportMeta.title;

        Ext.get('report-title').dom.innerHTML = title;
        this.selectedReport = index;

        this.loader.baseParams.node = '/' + this.selectedReport;
        var root = new Ext.tree.AsyncTreeNode({
            id:        '/' + this.selectedReport,
            draggable: false,
            expanded:  false
        });
        this.setRootNode(root);
    },

    minions: function() {
        var winHeight = document.body.offsetHeight,
            winWidth = document.body.offsetWidth,
            el = Ext.get("minion1");

        this.app.sounds.beedoo.play();
        //el.setLeft(winWidth - 300).setTop(winHeight - 261).show();

        /*
        console.log("Start effect 1");
        el.slideIn('b', {
            easing: 'backIn',
            duration: 1,
            remove: false,
            useDisplay: false
        })
        .slideOut('b', {
            easing: 'backIn',
            duration: 1,
            remove: false,
            useDisplay: false
        });
        */

            var winHeight = document.body.offsetHeight,
                winWidth = document.body.offsetWidth,
                el = Ext.get("minion_fire");

            el.setLeft(-200).setTop(winHeight/2).show();
            console.log("Start FX");
            el.shift({
                easing: 'none',
                duration: 10,
                remove: false,
                useDisplay: true,
                x: winWidth + 250,
                scope: this,
                callback: function() {
                    this.app.sounds.beedoo.stop();
                }
            });
    },

    minionsBounce: function(left) {

    },

    treeLoaded: function (loader, node, response) {
        var bladeFolder;
        return;

        // Next lines of code are for automatic expansion when in development for quick testing
        if (node.id !== "/root") return;

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

    exportExcel: function (item, e) {
        // create the form in HTML
        var win,
            task,
            html;

        //e.stopEvent();

        html = "<span class='csv-message'>Generating Excel file...</span>\n" +
            "<form name='exportForm' id='exportForm' method='post' action='php/get_report_tree.php'>\n" +
            "<input type='hidden' name='node' value='/vmwarevms/export'>\n" +
            "</form>\n";


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

        /*
        setTimeout(function(win) {
            Ext.get('exportForm').dom.submit();
            win.destroy();
        }, 1500);
        */
        task = new Ext.util.DelayedTask();
        task.delay(12000, this.submitExportForm, this, [win]);
    },

    submitExportForm: function (win) {
        Ext.get('exportForm').dom.submit();
        win.destroy();
    }
});
