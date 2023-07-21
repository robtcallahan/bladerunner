/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: BladeExceptionsGridPanel.js 73204 2013-03-14 18:03:31Z rcallaha $
 * $Date: 2013-03-14 14:03:31 -0400 (Thu, 14 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73204 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/BladeExceptionsGridPanel.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.BladeExceptionsGridPanel = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function () {
        store = new Ext.data.Store(
            {
                proxy:  new Ext.data.HttpProxy(
                    {
                        url:        'php/get_exceptions.php',
                        mycallback: function () {
                        }
                    }),
                reader: new Ext.data.JsonReader(
                    {
                        root:          'exceptions',
                        totalProperty: 'count'
                    }, [
                        {name: 'id', type: 'int'},
                        {name: 'bladeId', type: 'int'},
                        {name: 'chassisId', type: 'int'},

                        {name: 'chassisName', type: 'string'},
                        {name: 'bladeName', type: 'string'},
                        {name: 'productName', type: 'string'},
                        {name: 'slotNumber', type: 'int'},
                        {name: 'powerStatus', type: 'string'},

                        {name: 'excepDescr', type: 'string'},
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
                    width:     120
                }, {
                    header:    "Product Name",
                    dataIndex: 'producName',
                    width:     120
                }, {
                    header:    "Slot Num",
                    dataIndex: 'slotNumber',
                    width:     40
                }, {
                    header:    "Description",
                    dataIndex: 'excepDescr',
                    width:     150
                }, {
                    header:    "Date Found",
                    dataIndex: 'dateUpdated',
                    width:     100,
                    renderer:  Ext.util.Format.dateRenderer("Y-m-d H:i")
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
        BRunner.BladeExceptionsGridPanel.superclass.initComponent.apply(this, arguments);

        // parent call post-processing, e.g. install event handlers

    } // eo function initComponent

}); // eo extent


