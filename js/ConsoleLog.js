/*******************************************************************************
 *
 * $Id: ConsoleLog.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/ConsoleLog.js $
 *
 *******************************************************************************
 */

Ext.namespace('BRunner');

// check to see if we have a console defined or not
if (typeof console == 'undefined') {
    var console = {};
    console.log = function (msg) {

    };
}

BRunner.ConsoleLog = function (message) {
    if (!BRunner.debug) return;

    object = arguments[1] || "";

    var date = new Date();
    var timestamp = sprintf("%3s %02d %02d:%02d:%02d",
        BRunner.months[date.getMonth()],
        date.getDate(),
        date.getHours(),
        date.getMinutes(),
        date.getSeconds());

    if (typeof object === "object") {
        console.log(BRunner.DumpObj(object, "[" + timestamp + "] BRunner: " + message));
    }
    else {
        console.log("[" + timestamp + "] BRunner: " + message + " " + object);
    }
};

BRunner.DumpObj = function (obj, name, indent, depth) {
    if (typeof arguments[2] === "undefined")
        indent = "";
    if (typeof arguments[3] === "undefined")
        depth = 0;

    var MAX_DUMP_DEPTH = 10;

    if (depth > MAX_DUMP_DEPTH) {
        return indent + name + ": <Maximum Depth Reached>\n";
    }

    if (typeof obj === "object") {
        var child = null;
        var output = indent + name + "\n";
        indent += "\t";

        for (var item in obj) {
            if (obj.hasOwnProperty(item) && item !== "argument") {
                try {
                    child = obj[item];
                    if (typeof child === "function") {
                        child = "function()";
                    }
                }
                catch (e) {
                    child = "<Unable to Evaluate>";
                }

                if (typeof child === "object") {
                    output += BRunner.DumpObj(child, item, indent, depth + 1);
                }
                else {
                    output += indent + item + ": " + child + "\n";
                }
            }
        }
        return output;
    }
    else {
        return obj;
    }
};

