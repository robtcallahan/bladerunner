/*******************************************************************************
 *
 * $Id: App.js 78143 2013-08-22 15:49:29Z rcallaha $
 * $Date: 2013-08-22 11:49:29 -0400 (Thu, 22 Aug 2013) $
 * $Author: rcallaha $
 * $Revision: 78143 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/App.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.App = Ext.extend(Ext.Viewport, {
    taskUpdateRTLog:   null,
    runnerUpdateRTLog: null,

    initComponent: function () {
        this.sounds = {};
        this.soundManager.onready(function() {
        		this.sounds.beedoo = this.soundManager.createSound({
        				id: 'beedoo',
        				url: 'resources/sounds/beedoo_minions.mp3',
        				autoLoad: true
        		});
        }, this);

        this.notify = new Ext.Notify({});

        this.taskUpdateStatus = {
            run:      this.updateStatus,
            interval: BRunner.oneMinute * 10,
            scope:    this
        };
        this.runnerUpdateStatus = new Ext.util.TaskRunner();

        this.crsStore = new Ext.data.Store({
            proxy:  new Ext.data.HttpProxy(
                {
                    url:        'php/get_crs.php'
                }),
            reader: new Ext.data.JsonReader(
                {
                    root:          'crs',
                    totalProperty: 'total'
                }, [
                    {name: 'id', type: 'string'},
                    {name: 'name', type: 'string'},
                    {name: 'createdOn', type: 'string'},
                    {name: 'createdBy', type: 'string'},
                    {name: 'owner', type: 'string'},
                    {name: 'requestor', type: 'string'},
                    {name: 'descr', type: 'string'}
                ])
        });

        this.tasksStore = new Ext.data.Store({
            proxy:  new Ext.data.HttpProxy(
                {
                    url:        'php/get_core_tasks.php'
                }),
            reader: new Ext.data.JsonReader(
                {
                    root:          'tasks',
                    totalProperty: 'total'
                }, [
                    {name: 'sysId', type: 'string'},
                    {name: 'number', type: 'string'},
                    {name: 'shortDescr', type: 'string'},
                    {name: 'openedBy', type: 'string'},
                    {name: 'openedAt', type: 'string'}
                ])
        });


        header = [
            "<div class='header'>",
            "<div class='titleblock'>",
            "<img src='resources/images/blade_runner.png' />",
            "</div>",
            "<div class='header-info last-update secondaryBHighContrast-1'>",
            "<table class='header-info last-update secondaryBHighContrast-1'>",
            "<tr><td>Last HP SIM Update:</td><td id='sim-update'>" + this.simLastUpdate + "</td></tr>",
            "<tr><td>Last HP Chassis Update:</td><td id='chassis-update'>" + this.chassisLastUpdate + "</td></tr>",
            "<tr><td>Last HP WWN Update:</td><td id='wwn-update'>" + this.wwnLastUpdate + "</td></tr>",
            "</table>",
            "</div>",
            "<div class='navblock'>",
            "<div class='nav'>",
            " <a target=_blank href='" + BRunner.wikiHelpURL + "'>Help</a>",
            " &nbsp; | &nbsp; <a href='#' onclick='app.sendFeedback();'>Feedback</a>",
            " &nbsp; | &nbsp; <a href='#' onclick='app.showUsers();'>Users</a>",
            "</div>",
            "</div>",
            "<div class='header-info welcome secondaryBHighContrast-1'>Hi " + (this.actor.nickName ? this.actor.nickName : this.actor.firstName) + "</div>",
            "<div id='snsite' class='sn-site'>ServiceNow Site: " + this.snSite + "</div>",
            "<div id='drymode' class='drymode'>Dry Mode: " + (this.dryMode ? "ON - no edits will be saved" : "OFF") + "</div>",
            "<div id='status-indicator' class='header-info status-indicator'><img class='loading' src='../../ext/resources/images/default/s.gif'/> Processing...</div>",
            "</div>"].join("");

        footer = [
            "<div class='footer x-toolbar'>",
            "<a target='_blank' title='Go to the BladeRunner wiki page' href='" + BRunner.wikiURL + "'>BladeRunner " + this.release + " - " + this.env + "</a>",
            "<br>",
            "&copy; Copyright 2012 Neustar, Inc. All rights reserved. &nbsp; STS - Strategic Tools & Solutions &nbsp; ",
            "Web Programming -- <a href='mailto:Rob.Callahan@neustar.biz'>Rob Callahan, Principal Tool</a> &nbsp; ",
            "Powered by -- <a href='http://www.sencha.com/'>ExtJS</a> &amp; <a href='http://us2.php.net/'>PHP</a>",
            "</div>"].join("");


        this.chassisTree = new BRunner.ChassisTree({
            title:      'HP Chassis',
            id:         'chassis-tab',
            height:     180,
            autoScroll: false,
            notify:     this.notify,
            crsStore:   this.crsStore,
            tasksStore: this.tasksStore,
            actor:      this.actor,
            app:        this,
            listeners: {
                scope:     this,
                brexport:    this.exportExcel,
                updatecr:  this.promptForCR,
                osmsemail: this.getOsmEmails
            }
        });

        this.arrayTree = new BRunner.ArrayTree({
            title:      'Arrays',
            id:         'arrays-tab',
            height:     180,
            autoScroll: false,
            notify:     this.notify,
            crsStore:   this.crsStore,
            actor:      this.actor,
            listeners: {
                scope:     this,
                brexport:    this.exportExcel,
                updatecr:  this.promptForCR,
                osmsemail: this.getOsmEmails
            }
        });

        this.switchTree = new BRunner.SwitchTree({
            title:      'SAN Switches',
            id:         'switches-tab',
            height:     180,
            autoScroll: false,
            notify:     this.notify,
            crsStore:   this.crsStore,
            actor:      this.actor,
            listeners: {
                scope:     this,
                brexport:    this.exportExcel,
                updatecr:  this.promptForCR,
                osmsemail: this.getOsmEmails
            }
        });

        this.reportsGrid = new BRunner.ReportsGrid({
            title:  'Reports',
            id:     'reports-tab',
            height: 180,
            notify: this.notify,
            actor:  this.actor,
            app:    this.chassisTree
        });

        this.reportsTree = new BRunner.ReportsTree({
            title:  'ESX Blades',
            id:     'reportstree-tab',
            height: 180,
            notify: this.notify,
            actor:  this.actor,
            app:    this.chassisTree
        });

        this.exceptionsPanel = new BRunner.ExceptionsPanel({
            title:  'Exceptions',
            id:     'exceptions-tab',
            //height: 180,
            notify: this.notify,
            actor:  this.actor,
            layout: 'border',
            items:  [
                {
                    xtype:       'treepanel',
                    id:          'exceptions-nav-tree',
                    region:      'west',
                    width:       250,
                    useArrows:   true,
                    animate:     true,
                    //containerScroll: true,
                    border:      false,
                    rootVisible: false,
                    loader:      new Ext.tree.TreeLoader({
                        dataUrl:         'php/get_exception_reports.php',
                        baseParams:      {
                            dataType: 'treegrid'
                        },
                        preloadChildren: true
                    }),
                    root:        {
                        nodeType:  'async',
                        text:      'Exception Reports',
                        draggable: false,
                        id:        'root'
                    },
                    listeners:   {
                        load:  function (node) {
                            if (node.id !== "root") return;
                            this.getNodeById("hpsim").expand(true, true);
                            this.getNodeById("sanscreen").expand(true, true);
                        },
                        click: function (node, e) {
                            Ext.getCmp('exceptions-tab').getReport(node.id);
                        }
                    }
                },
                {
                    xtype:      'panel',
                    region:     'center',
                    title:      'Exception Report',
                    id:         'exceptions-panel',
                    html:       '<div id="exceptions-div" class="er-div"></div>',
                    autoScroll: true
                }
            ]
        });

        tabs = [this.chassisTree, this.arrayTree, this.switchTree, this.reportsTree, this.reportsGrid, this.exceptionsPanel];

        Ext.apply(this, {
            renderTo: document.body,
            layout:   'border',
            items:    [
                {
                    region:       'north',
                    margins:      '0 0 0 0',
                    height:       80,
                    html:         header,
                    bodyCssClass: 'header'
                },
                {
                    region:     'center',
                    xtype:      'tabpanel',
                    id:         'tab-panel',
                    activeTab:  0,
                    autoScroll: true,
                    items:      tabs,
                    listeners:  {
                        scope:     this,
                        tabchange: function (tabpanel, panel) {
                            if (panel.id === "reports-tab") {
                                panel.getStore().load();
                                //} else if (panel.id === "exceptions-tab") {
                                //	panel.update();
                            }
                        }
                    }
                },
                {
                    region:  'south',
                    xtype:   'panel',
                    layout:  'fit',
                    border:  false,
                    frame:   false,
                    cls:     'footer',
                    margins: '0 0 0 0',
                    height:  30,
                    html:    footer
                }
            ]
        });

        BRunner.App.superclass.initComponent.apply(this, arguments);
    },

    afterRender: function () {
        BRunner.App.superclass.afterRender.apply(this, arguments);
        if (this.dryMode) {
            Ext.get('drymode').show();
        }
        if (this.snSite !== "Production") {
            Ext.get('snsite').show();
        }

        // turning this off for now since the jobs only run once per day so there's no need to refresh
        // start the cron job status update (upper right of header)
        this.startUpdateStatus();
    },

        /*
     * node = "all" || node
     * reportType = "details" || "wwns"
     */
    exportExcel: function (me, node, reportType) {
        // create the form in HTML
        var html = "<span class='csv-message'>Generating MicroSquish Excel file...</span>\n",
            win,
            task,
            nodeType = node.attributes.type,
            dbId = node.attributes.dbId;

        if (node === "all") {
            if (reportType === "wwns") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_" + nodeType + "_wwns.php'>\n" +
                        "  <input type='hidden' name='nodeType' value='all' />\n" +
                        "  <input type='hidden' name='nodeDbId' value='none' />\n";
            }
        }
        else {
            if (reportType === "details") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_" + nodeType + "_tree.php'>\n";
            }
            else if (reportType === "wwns") {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_" + nodeType + "_wwns.php'>\n";
            } else {
                html += "<form name='exportForm' id='exportForm' method='post' action='php/export_" + nodeType + "_tree.php'>\n" +
                        "  <input type='hidden' name='nodeId' value='" + node.id + "' />\n";
            }
            html += "  <input type='hidden' name='nodeType' value='" + nodeType + "' />\n" +
                    "  <input type='hidden' name='nodeDbId' value='" + dbId + "' />\n";
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
    },


    startUpdateStatus: function () {
        this.runnerUpdateStatus.start(this.taskUpdateStatus);
    },

    stopUpdateStatus: function () {
        this.runnerUpdateStatus.stop(this.taskUpdateStatus);
    },

    promptForCR: function (me, node)
    {
        var win = new Ext.Window({
            title:      'Update CR',
            modal:      true,
            constrain:  true,
            height:     205,
            width:      250,
            autoScroll: true,
            layout:     'border',
            items:      [
                {
                    region:  'north',
                    height:  '80',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    html:    'About to assign all related objects in ' + node.attributes.col1 + '...<br><br>' +
                        'Please specify the draft Change Request',
                    margins: '10 0 3 5'
                },
                {
                    region:  'center',
                    xtype:   'panel',
                    baseCls: 'window-panel',
                    margins: '10 0 5 5',
                    items:   [
                        {
                            xtype:          'combo',
                            label:          'Change Request',
                            id:             'crId',
                            name:           'crId',
                            forceSelection: true,
                            triggerAction:  'all',
                            minChars:       3,
                            mode:           'remote',
                            store:          this.crsStore,
                            valueField:     'id',
                            displayField:   'name',
                            width:          200,
                            tpl:            '<tpl for="."><div ext:qtip="Created On: {createdOn}<br>Created By: {createdBy}<br>Owner: {owner}<br>Requestor: {requestor}<br>Descr: {descr}" class="x-combo-list-item">{name}</div></tpl>'
                            // force the user to click the Submit button
                            /*
                             listeners: {
                             scope: this,
                             select: function(combo, rec, index) {
                             win.close();
                             this.updateCR(node, rec.data.id);
                             }
                             }
                             */
                        }
                    ]
                },
                {
                    region:     'south',
                    margins:    '5 0 10 5',
                    xtype:      'checkbox',
                    id:         'removeCisSubsystems',
                    checked:    true,
                    boxLabel:   'Remove existing CIs & SubSystems',
                    name:       'removeCisSubsystems',
                    inputValue: 1
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
                    text:    'Submit',
                    cls:     'window-button',
                    scope:   this,
                    handler: function () {
                        this.crGetObjects(node, Ext.getCmp('crId').getValue(), Ext.getCmp('removeCisSubsystems').getValue());
                        win.close();
                    }
                }
            ]
        });

        win.render(document.body);
        win.center();
        win.show();
    },

    crGetObjects: function (node, crId, removeCisSubsystems)
    {
        Ext.get('status-indicator').show();

        var win = new Ext.Window({
            title:      'Update CR Log',
            id:         'logwindow',
            autoScroll: true,
            modal:      true,
            constrain:  true,
            height:     650,
            width:      450,
            items:      [
                {
                    xtype:   'panel',
                    id:      'logpanel',
                    baseCls: 'window-panel',
                    margins: '10 0 0 5',
                    html:    '<div id="logpanel-content"><i>running...</i></div>'
                }
            ],
            buttons:    [
                {
                    text:     'OK',
                    id:       'win-close-button',
                    disabled: true,
                    cls:      'window-button',
                    handler:  function () {
                        win.close();
                    }
                }
            ]
        });

        win.render(document.body);
        win.center();
        win.show();

        this.startRTUpdate();

        Ext.Ajax.request({
            url:             'php/cr_arrays_and_hosts.php',
            params:          {
                nodeType:            node.attributes.type,
                dbId:                node.attributes.dbId,
                crId:                crId,
                removeCisSubsystems: removeCisSubsystems
            },
            scope:           this,
            mycallback:      function (json, options) {
                var crData = {
                    nodeType:            options.params.nodeType,
                    dbId:                options.params.dbId,
                    crId:                options.params.crId,
                    removeCisSubsystems: options.params.removeCisSubsystems,
                    startTime:           json.startTime,
                    hostIndex:           0,
                    hostsToProcess:      json.hosts,
                    processedHosts:      [],
                    subSysIdsHash:       {}
                };
                if (crData.nodeType === "switch") {
                    this.crProcessArray(crData);
                } else {
                    this.crProcessHost(crData);
                }
            },
            myerrorcallback: this.crError
        });
    },

    crProcessArray: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_process_array.php',
            params: {
                nodeType: crData.nodeType,
                arrayIndex: crData.arrayIndex,
                numArrays: crData.arraysToProcess.length,
                arrayId: crData.arraysToProcess[crData.arrayIndex].id,
                subSysIdsHash: Ext.util.JSON.encode(crData.subSysIdsHash)
            },
            scope:           this,
            crData: crData,

            mycallback:      function (json, options) {
                var crData = options.crData;

                crData.arrayIndex++;
                crData.processedArrays = crData.processedArrays.concat(json.processedArrays);

                // add the returned subSysIdsHash to our saved crData.subSysIdshash
                for (var prop in json.subSysIdsHash) {
                    crData.subSysIdsHash[prop] = json.subSysIdsHash[prop];
                }

                if (crData.arrayIndex === crData.arraysToProcess.length) {
                    this.crProcessHost(crData);
                } else {
                    this.crProcessArray(crData);
                }
            },
            myerrorcallback: this.crError
        });
    },

    crProcessHost: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_process_host.php',
            params: {
                nodeType: crData.nodeType,
                hostIndex: crData.hostIndex,
                numHosts: crData.hostsToProcess.length,
                hostId: crData.hostsToProcess[crData.hostIndex].id,
                subSysIdsHash: Ext.util.JSON.encode(crData.subSysIdsHash)
            },
            scope:           this,
            crData: crData,

            mycallback:      function (json, options) {
                var crData = options.crData;

                crData.hostIndex++;
                crData.processedHosts = crData.processedHosts.concat(json.processedHosts);

                // add the returned subSysIdsHash to our saved crData.subSysIdshash
                for (var prop in json.subSysIdsHash) {
                    crData.subSysIdsHash[prop] = json.subSysIdsHash[prop];
                }

                if (crData.hostIndex === crData.hostsToProcess.length) {
                    if (crData.hostType === "switch") {
                        this.crAddArrays(crData);
                    } else {
                        this.crAddCi(crData);
                    }
                } else {
                    this.crProcessHost(crData);
                }
            },
            myerrorcallback: this.crError
        });
    },

    crAddArrays: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_add_arrays.php',
            params: {
                nodeType:            crData.nodeType,
                crId:                crData.crId,
                arrays:              Ext.util.JSON.encode(crData.processedArrays),
                removeCisSubsystems: crData.removeCisSubsystems
            },
            scope: this,
            crData: crData,

            mycallback:      function (json, options) {
                var crData = options.crData;
                this.crAddHosts(crData);
            },
            myerrorcallback: this.crError
        });
    },

    crAddCi: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_add_ci.php',
            params: {
                nodeType:            crData.nodeType,
                dbId:                crData.dbId,
                crId:                crData.crId,
                removeCisSubsystems: crData.removeCisSubsystems
            },
            scope: this,
            crData: crData,

            mycallback: function (json, options) {
                var crData = options.crData;
                this.crAddHosts(crData);
            },
            myerrorcallback: this.crError
        });
    },

    crAddHosts: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_add_hosts.php',
            params: {
                nodeType:            crData.nodeType,
                crId:                crData.crId,
                hosts:               Ext.util.JSON.encode(crData.processedHosts),
                removeCisSubsystems: crData.removeCisSubsystems
            },
            scope: this,
            crData: crData,

            mycallback:      function (json, options) {
                var crData = options.crData;
                this.crAddSubSystems(crData);
            },
            myerrorcallback: this.crError
        });
    },

    crAddSubSystems: function(crData)
    {
        Ext.Ajax.request({
            url:             'php/cr_add_subsystems.php',
            params: {
                nodeType:            crData.nodeType,
                crId:                crData.crId,
                subSysIdsHash:       Ext.util.JSON.encode(crData.subSysIdsHash),
                removeCisSubsystems: crData.removeCisSubsystems,
                startTime:           crData.startTime
            },
            scope: this,
            crData: crData,

            mycallback:      function (json, options) {
                this.stopRTUpdate();
                Ext.get('status-indicator').hide();
                Ext.get('logpanel-content').update(json.log, false, function () {
                    var content = Ext.get('logpanel-content'),
                        bottom = content.getHeight(),
                        panel = Ext.getCmp('logwindow');
                    panel.body.scroll("b", bottom, false);
                });
                Ext.getCmp('win-close-button').enable();
            },
            myerrorcallback: this.crError
        });
    },

    crError: function () {
        var content = Ext.get('logpanel-content'),
            bottom = content.getHeight(),
            panel = Ext.getCmp('logwindow');

        this.stopRTUpdate();
        panel.body.scroll("b", bottom, false);
        Ext.get('status-indicator').hide();
        Ext.getCmp('win-close-button').enable();

        Ext.Msg.show({
            title:   'ERROR: Update CR',
            msg:     "The server returned an error. The CR may not have been updated.",
            buttons: Ext.Msg.OK,
            icon:    Ext.MessageBox.ERROR
        });
    },

    startRTUpdate: function ()
    {
        if (this.taskUpdateRTLog === null) {
            this.taskUpdateRTLog = {
                run:      this.updateRTLog,
                interval: BRunner.oneSecond,
                scope:    this
            };
            this.runnerUpdateRTLog = new Ext.util.TaskRunner();
        }
        this.runnerUpdateRTLog.start(this.taskUpdateRTLog);
    },

    updateRTLog: function ()
    {
        Ext.Ajax.request({
            url:        'php/get_rt_log.php',
            params:     {
            },
            scope:      this,
            mycallback: function (json, options) {
                var content = Ext.get('logpanel-content'),
                    bottom = content.getHeight(),
                    panel = Ext.getCmp('logwindow');

                if (json.log !== "done") {
                    Ext.get('logpanel-content').update(json.log);
                }
                panel.body.scroll("b", bottom, false);
            }
        });
    },

    stopRTUpdate: function ()
    {
        this.runnerUpdateRTLog.stop(this.taskUpdateRTLog);
    },

    getOsmEmails: function(me, node) {
        Ext.get('status-indicator').show();

        var win = new Ext.Window({
            title:      'Retrieving Subsystem OSMs Email Log',
            id:         'logwindow',
            autoScroll: true,
            modal:      true,
            constrain:  true,
            height:     650,
            width:      450,
            items:      [
                {
                    xtype:   'panel',
                    id:      'logpanel',
                    baseCls: 'window-panel',
                    margins: '10 0 0 5',
                    html:    '<div id="logpanel-content"><i>running...</i></div>'
                }
            ],
            buttons:    [
                {
                    text:     'OK',
                    id:       'win-close-button',
                    disabled: true,
                    cls:      'window-button',
                    scope:    this,
                    handler:  function () {
                        win.close();
                        this.showEmailComposeWindow();
                    }
                }
            ]
        });

        win.render(document.body);
        win.center();
        win.show();

        this.startRTUpdate();

        Ext.Ajax.request({
            url:             'php/get_osms_email.php',
            params:          {
                'nodeType': node.attributes.type,
                'dbId':     node.attributes.dbId
            },
            scope:           this,
            node:            node,
            mycallback:      function (json, options) {
                this.stopRTUpdate();

                this.emails = json.emails;
                this.ssAndOsm = json.ssAndOsm;
                this.emailedNode = options.node;

                Ext.get('status-indicator').hide();
                Ext.get('logpanel-content').update(json.log, false, function () {
                    var content = Ext.get('logpanel-content'),
                        bottom = content.getHeight(),
                        panel = Ext.getCmp('logwindow');
                    panel.body.scroll("b", bottom, false);
                });
                Ext.getCmp('win-close-button').enable();
            },
            myerrorcallback: function (json, options) {
                var content = Ext.get('logpanel-content'),
                    bottom = content.getHeight(),
                    panel = Ext.getCmp('logwindow');

                this.stopRTUpdate();
                panel.body.scroll("b", bottom, false);
                Ext.get('status-indicator').hide();
                Ext.getCmp('win-close-button').enable();

                Ext.Msg.show({
                    title:   'ERROR: Update CR',
                    msg:     "The server returned an error.",
                    buttons: Ext.Msg.OK,
                    icon:    Ext.MessageBox.ERROR
                });
            }
        });
    },

    showEmailComposeWindow: function () {
        var win, bodyText = "", subs = this.ssAndOsm;

        bodyText = "<br><br><table style='font-size:9pt;'><tr><th align='left'>Subsystem</th><th align='left'>Operations Support Manager</th></tr>";
        for (var i = 0; i < subs.length; i++) {
            bodyText += "<tr><td>" + subs[i].ssName + "</td><td>" + subs[i].osm + "</td></tr>";
        }
        bodyText += "</table>";

        win = new Ext.Window({
            title:      'New Message',
            id:         'emailwindow',
            autoScroll: true,
            modal:      true,
            constrain:  true,
            height:     650,
            width:      600,
            items:      [
                {
                    xtype:       'form',
                    id:          'formpanel',
                    baseCls:     'window-panel',
                    labelWidth:  50,
                    frame:       true,
                    bodyStyle:   'padding:5px 5px 0',
                    width:       585,
                    defaults:    {width: 520},
                    defaultType: 'textfield',
                    items:       [
                        {
                            fieldLabel: 'To',
                            id:         'email-to',
                            vtype:      'email',
                            allowBlank: false,
                            value:      this.emails.join()
                        },
                        {
                            fieldLabel: 'Cc',
                            id:         'email-cc',
                            vtype:      'email',
                            allowBlank: true
                        },
                        {
                            fieldLabel: 'Bcc',
                            id:         'email-bcc',
                            vtype:      'email',
                            allowBlank: true
                        },
                        {
                            fieldLabel: 'Subject',
                            id:         'email-subject',
                            allowBlank: false
                        },
                        {
                            fieldLabel: 'From',
                            id:         'email-from',
                            vtype:      'email',
                            allowBlank: false,
                            disabled:   true,
                            value:      this.actor.email
                        }
                    ]
                },
                {
                    xtype:  'htmleditor',
                    id:     'email-bodyText',
                    width:  585,
                    height: 420,
                    value:  bodyText
                }
            ],
            buttons:    [
                {
                    text:    'Send',
                    id:      'win-send-button',
                    cls:     'window-button',
                    scope:   this,
                    handler: function () {
                        this.sendEmail(win);
                    }
                },
                {
                    text:    'Cancel',
                    id:      'win-cancel-button',
                    cls:     'window-button',
                    scope:   this,
                    handler: function () {
                        win.close();
                    }
                }
            ]
        });

        win.render(document.body);
        win.center();
        win.show();
    },

    sendEmail: function (win) {
        var to = Ext.getCmp('email-to').getValue(),
            from = Ext.getCmp('email-from').getValue(),
            cc = Ext.getCmp('email-cc').getValue(),
            bcc = Ext.getCmp('email-bcc').getValue(),
            subject = Ext.getCmp('email-subject').getValue(),
            bodyText = Ext.getCmp('email-bodyText').getValue();

        win.close();
        Ext.get('status-indicator').show();

        Ext.Ajax.request({
            url:             'php/send_email.php',
            params:          {
                to:       to,
                from:     from,
                cc:       cc,
                bcc:      bcc,
                subject:  subject,
                bodyText: bodyText
            },
            scope:           this,
            mycallback:      function (json, options) {
                Ext.get('status-indicator').hide();
                this.notify.setAlert(this.notify.STATUS_INFO, "Your email has been sent.", 5);
            },
            myerrorcallback: function (json, options) {
                Ext.get('status-indicator').hide();
                this.notify.setAlert(this.notify.STATUS_ERROR, "The server responded with an error. Your email may not have been sent.");
            }
        });
    },

    updateStatus: function () {
        Ext.Ajax.request({
            url:        'php/get_update_status.php',
            scope:      this,
            mycallback: function (json, options) {
                var item, id, el,
                    items = ["sim", "chassis", "wwn", "ss"];

                for (var i = 0; i < items.length; i++) {
                    item = items[i];
                    id = item + "-update";
                    el = Ext.get(id);
                    if (el && json[item]) {
                        el.dom.innerHTML = json[item];
                    }
                }
            },

            myerrorcallback: function (json, options, response) {
            }
        });
    },

    /* standard methods */
    sendFeedback: function () {
        var form,
            win;

        form = new Ext.form.FormPanel({
            //border: false,
            layout:       'form',
            frame:        true,
            labelWidth:   100,
            defaultType:  'textfield',
            monitorValid: true,
            items:        [
                {
                    id:         'emailFrom',
                    name:       'emailFrom',
                    fieldLabel: 'From',
                    width:      380,
                    grow:       false,
                    disabled:   true,
                    value:      this.actor.email
                },
                {
                    id:         'emailSubject',
                    name:       'emailSubject',
                    fieldLabel: 'Subject',
                    width:      380,
                    grow:       false,
                    disabled:   true,
                    value:      "BladeRunner Feedback"
                },
                {
                    xtype:      'checkboxgroup',
                    fieldLabel: 'Feedback Type',
                    name:       'feedbackType',
                    items:      [
                        {
                            boxLabel:  'Bug',
                            listeners: {
                                "check": function (checkbox, checked) {
                                    if (checked) {
                                        Ext.get('emailSubject').dom.value = "BladeRunner Bug Report";
                                    }
                                }
                            }
                        },
                        {
                            boxLabel:  'Request',
                            listeners: {
                                "check": function (checkbox, checked) {
                                    if (checked) {
                                        Ext.get('emailSubject').dom.value = "BladeRunner Feature Request";
                                    }
                                }
                            }
                        },
                        {
                            boxLabel:  'Feedback',
                            listeners: {
                                "check": function (checkbox, checked) {
                                    if (checked) {
                                        Ext.get('emailSubject').dom.value = "BladeRunner Feedback";
                                    }
                                }
                            }
                        }
                    ]
                },
                {
                    xtype:      'textarea',
                    id:         'emailBody',
                    name:       'emailBody',
                    fieldLabel: "Comments",
                    allowBlank: false,
                    width:      '100%',
                    grow:       false,
                    height:     200,
                    minLength:  3
                }
            ],
            buttons:      [
                {
                    text:     'Send',
                    formBind: true,
                    scope:    this,
                    handler:  function () {
                        if (typeof form.form.getFieldValues()['feedbackType'] == "undefined") {
                            Ext.Msg.show({
                                title:   'ERROR',
                                msg:     'You must specify an feedback type.',
                                buttons: Ext.Msg.OK,
                                icon:    Ext.MessageBox.ERROR
                            });
                            return;
                        }

                        Ext.Ajax.request(
                            {
                                url:    'php/send_feedback.php',
                                params: {
                                    'emailFrom':    Ext.get('emailFrom').dom.value,
                                    'emailSubject': Ext.get('emailSubject').dom.value,
                                    'emailBody':    Ext.get('emailBody').dom.value
                                },
                                scope:  this,

                                mycallback: function (returnHash) {
                                    this.notify.setAlert(this.notify.STATUS_INFO, "Thanks!<br>Your email has been sent", 3);
                                }
                            });
                        win.close();
                    }
                },
                {
                    text:    'Cancel',
                    handler: function () {
                        win.close();
                    }
                }
            ]
        });

        // create and show window
        win = new Ext.Window({
            title:     'Bug Report, Feature Request or General Feedback',
            modal:     true,
            constrain: true,
            width:     600,
            height:    300,
            layout:    'fit',
            closable:  false,
            border:    false,
            items:     [
                form
            ]
        });

        win.show();
    },

    showUsers: function () {
        var logins = new BRunner.LoginsGridPanel();

        // create and show window
        win = new Ext.Window({
            title:      'BladeRunner User Logins',
            modal:      true,
            constrain:  true,
            width:      1000,
            height:     600,
            layout:     'fit',
            closable:   true,
            border:     false,
            autoScroll: true,
            items:      [
                logins
            ]
        });

        win.show();
        logins.getStore().load();
    }
});


