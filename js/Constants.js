/*******************************************************************************
 *
 * $Id: Constants.js 73204 2013-03-14 18:03:31Z rcallaha $
 * $Date: 2013-03-14 14:03:31 -0400 (Thu, 14 Mar 2013) $
 * $Author: rcallaha $
 * $Revision: 73204 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/Constants.js $
 *
 *******************************************************************************
 */

    // define the variable name space for the application classes
Ext.namespace('BRunner');

BRunner.debug = true;

BRunner.oneMilliSecond = 1;
BRunner.oneSecond = 1000 * BRunner.oneMilliSecond;
BRunner.oneMinute = 60 * BRunner.oneSecond;
BRunner.oneHour = 60 * BRunner.oneMinute;
BRunner.oneDay = 24 * BRunner.oneHour;

BRunner.months = new Array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');

BRunner.monthsFull = new Array('January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December');

BRunner.weekdays = new Array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');

BRunner.weekdaysFull = new Array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');

// define some global constant values

BRunner.wikiURL = "http://ops.neustar.biz/wiki/index.php/BladeRunner";
BRunner.wikiHelpURL = "http://ops.neustar.biz/wiki/index.php/BladeRunner#Help";

