/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: LoginsGridPanel.js 73204 2013-03-14 18:03:31Z rcallaha $
 * $Date: 2013-03-14 14:03:31 -0400 (Thu, 14 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73204 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/LoginsGridPanel.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.LoginsGridPanel = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function () {
        store = new Ext.data.Store(
            {
                proxy:  new Ext.data.HttpProxy(
                    {
                        url:        'php/get_logins.php',
                        mycallback: function () {
                        }
                    }),
                reader: new Ext.data.JsonReader(
                    {
                        root:          'logins',
                        totalProperty: 'count'
                    }, [
                        {name: 'id', type: 'int'},
                        {name: 'empId', type: 'string'},
                        {name: 'lastName', type: 'string'},
                        {name: 'firstName', type: 'string'},
                        {name: 'username', type: 'string'},
                        {name: 'title', type: 'string'},
                        {name: 'dept', type: 'string'},
                        {name: 'numLogins', type: 'int'},
                        {name: 'lastLogin', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                        {name: 'ipAddr', type: 'string'},
                        {name: 'browser', type: 'string'},
                        {name: 'platform', type: 'string'}
                    ])
            }); // eo store object

        // set the default sort
        store.setDefaultSort('lastLogin', 'DESC');

        // define the column model for the grid
        colModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true,
                width:    80
            },
            columns:  [
                new Ext.grid.RowNumberer({width: 25}),
                {
                    header:    "Last Name",
                    dataIndex: 'lastName',
                    width:     80
                }, {
                    header:    "First Name",
                    dataIndex: 'firstName',
                    width:     80
                }, {
                    header:    "Last Login",
                    dataIndex: 'lastLogin',
                    width:     100,
                    renderer:  Ext.util.Format.dateRenderer("Y-m-d H:i")
                }, {
                    header:    "Hits",
                    dataIndex: 'numLogins',
                    width:     25
                }, {
                    header:    "User Agent",
                    dataIndex: 'browser',
                    width:     60
                }, {
                    header:    "Platform",
                    dataIndex: 'platform',
                    width:     60
                }, {
                    header:    "Title",
                    dataIndex: 'title',
                    width:     100
                }, {
                    header:    "Deptartment",
                    dataIndex: 'dept',
                    width:     130
                }, {
                    header:    "IP Address",
                    dataIndex: 'ipAddr',
                    width:     80
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
                msg: "Loading logins..."
            },
            store:       store,
            cm:          colModel,
            viewConfig:  {
                forceFit:     true,
                scrollOffset: 0
            }
        });
        // call parent
        BRunner.LoginsGridPanel.superclass.initComponent.apply(this, arguments);

        // parent call post-processing, e.g. install event handlers

    } // eo function initComponent

}); // eo extent


