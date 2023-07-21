/*******************************************************************************
 *
 * $Id: main.js 73457 2013-03-21 10:54:48Z rcallaha $
 * $Date: 2013-03-21 06:54:48 -0400 (Thu, 21 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73457 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/main.js $
 *
 *******************************************************************************
 */

Ext.BLANK_IMAGE_URL = "/ext/resources/images/default/s.gif";

var app = null;

// application main entry point
// Ext.onReady() is called when all files have been loaded into the browser and the DOM is ready
Ext.onReady(function () {
    // Enable quick tips
    Ext.QuickTips.init();
    Ext.apply(Ext.QuickTips.getQuickTip(), {
        minWidth:   100,
        showDelay:  500,
        trackMouse: false
    });

    app = new BRunner.App({
        env:               BRunner.env,
        release:           BRunner.release,
        snSite:            BRunner.snSite,
        dryMode:           BRunner.dryMode,
        simLastUpdate:     BRunner.simLastUpdate,
        chassisLastUpdate: BRunner.chassisLastUpdate,
        wwnLastUpdate:     BRunner.wwnLastUpdate,
        reportsAccess:     BRunner.reportsAccess,
        actor:             BRunner.actor,
        soundManager:      soundManager
    });
});




