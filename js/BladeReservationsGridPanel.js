/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: BladeReservationsGridPanel.js 73611 2013-03-26 13:23:06Z rcallaha $
 * $Date: 2013-03-26 09:23:06 -0400 (Tue, 26 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73611 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/BladeReservationsGridPanel.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.BladeReservationsGridPanel = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function () {
        store = new Ext.data.Store(
            {
                proxy:  new Ext.data.HttpProxy(
                    {
                        url:        'php/get_blade_reservations.php',
                        mycallback: function () {
                        }
                    }),
                reader: new Ext.data.JsonReader(
                    {
                        root:          'reservations',
                        totalProperty: 'count'
                    }, [
                        {name: 'id', type: 'int'},
                        {name: 'bladeId', type: 'int'},
                        {name: 'chassisId', type: 'int'},

                        {name: 'chassisName', type: 'string'},
                        {name: 'bladeName', type: 'string'},
                        {name: 'slotNumber', type: 'int'},
                        {name: 'projectName', type: 'string'},

                        {name: 'taskNumber', type: 'string'},
                        {name: 'taskSysId', type: 'string'},
                        {name: 'taskUri', type: 'string'},
                        {name: 'taskShortDescr', type: 'string'},

                        {name: 'dateReserved', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                        {name: 'userReserved', type: 'string'},
                        {name: 'dateUpdated', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                        {name: 'userUpdated', type: 'string'}
                    ])
            }); // eo store object

        // set the default sort
        store.setDefaultSort('bladeName', 'ASC');

        // define the column model for the grid
        colModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    80
            },
            columns:  [
                new Ext.grid.RowNumberer({width: 25}),
                {
                    header:    "Blade Name",
                    dataIndex: 'bladeName',
                    width:     80,
                    renderer:  function (value, metaData, record, rowIndex, colIndex, store) {
                        var returnVal = '<span title="Click to go to blade" ' +
                            'style="text-decoration:underline; cursor:pointer;" ' +
                            'onclick="app.chassisTree.goToBlade(' + record.data.bladeId + ');">' + value + '</span>';
                        return returnVal;
                    }
                }, {
                    header:    "Chassis Name",
                    dataIndex: 'chassisName',
                    width:     80
                }, {
                    header:    "Slot",
                    dataIndex: 'slotNumber',
                    width:     40
                }, {
                    header:    "Project Name",
                    dataIndex: 'projectName',
                    width:     140
                }, {
                    header:    "Task Number",
                    dataIndex: 'taskNumber',
                    width:     90,
                    renderer:  function (value, metaData, record, rowIndex, colIndex, store) {
                        return '<span title="Click to go to ServiceNow ticket" ' +
                            'style="text-decoration:underline; cursor:pointer;" ' +
                            'onclick=window.open("' + record.data.taskUri + '","_blank");> ' +
                            value + '</span>';
                    }
                }, {
                    header:    "Task Descr",
                    dataIndex: 'taskShortDescr',
                    width:     100
                }, {
                    header:    "Date Reserved",
                    dataIndex: 'dateReserved',
                    width:     100,
                    renderer:  Ext.util.Format.dateRenderer("Y-m-d H:i")
                }, {
                    header:    "User Reserved",
                    dataIndex: 'userReserved',
                    width:     70
                }, {
                    header:    "Date Updated",
                    dataIndex: 'dateUpdated',
                    width:     100,
                    renderer:  Ext.util.Format.dateRenderer("Y-m-d H:i")
                }, {
                    header:    "User Updated",
                    dataIndex: 'userUpdated',
                    width:     70
                }
            ]
        }); // eo column model object

        // apply config
        Ext.apply(this, {
            stripeRows:  true,
            columnWidth: .5,
            autoHeight:  true,
            margins:     '0 0 0 0',
            loadMask:    {
                msg: "Loading exceptions..."
            },
            store:       store,
            cm:          colModel,
            viewConfig:  {
                forceFit:     true,
                scrollOffset: 0
            }
        });
        // call parent
        BRunner.BladeReservationsGridPanel.superclass.initComponent.apply(this, arguments);

        // parent call post-processing, e.g. install event handlers

    } // eo function initComponent

}); // eo extent


