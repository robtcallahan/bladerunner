/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: ExceptionsPanel.js 73204 2013-03-14 18:03:31Z rcallaha $
 * $Date: 2013-03-14 14:03:31 -0400 (Thu, 14 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73204 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/ExceptionsPanel.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.ExceptionsPanel = Ext.extend(Ext.Panel, {
    initComponent: function () {
        Ext.apply(this, arguments);

        // apply config
        Ext.apply(this, {
            margins:  '0 0 0 0',
            loadMask: {
                msg: "Loading..."
            }
        });

        // call parent
        BRunner.ExceptionsPanel.superclass.initComponent.apply(this, arguments);
    },

    getReport: function (reportId) {
        if (reportId.search(/\//) === -1) return;

        Ext.Ajax.request({
            url:             'php/get_exception_report.php',
            params:          {
                reportId: reportId
            },
            scope:           this,
            mycallback:      function (json, options) {
                Ext.get('exceptions-div').dom.innerHTML = json.html;
                Ext.getCmp('exceptions-panel').setTitle(json.title);
            },
            myerrorcallback: function (json, options) {
            }
        });
    }
});
