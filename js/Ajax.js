/*******************************************************************************
 *
 * @class TicketsGrid
 * @extends Ext.grid.GridPanel
 *
 * TicketsGrid description_here
 *
 * $Id: Ajax.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/Ajax.js $
 *
 *******************************************************************************
 */

Ext.Ajax.timeout = 5 * BRunner.oneMinute;
Ext.Ajax.method = "POST";

Ext.Ajax.on('requestcomplete',
    function (conn, response, options) {
        var scope = options.myscope || options.scope || this,
            dataType = options.dataType || options.params && options.params.dataType || "json",
            json;

        if (typeof options.isUpload !== "undefined" && options.isUpload) {

        }

        else if (dataType === "storemenu" || dataType === "treegrid") {
            json = Ext.util.JSON.decode(response.responseText);
            if (typeof json.returnCode !== "undefined" && json.returnCode !== 0) {
                BRunner.ErrorAlert(json.errorText, null, null, null, null, response, null);
            }

        }

        else if (dataType === "html") {
            if (typeof options.mycallback != "undefined") {
                options.mycallback.call(scope, response.responseText);
            }
        }

        else {
            // decode the returned JSON
            json = Ext.util.JSON.decode(response.responseText);

            // did we return success? 0 = success
            var success = false;
            if ((json.hasOwnProperty('success') && json.success === 0) || (json.hasOwnProperty('returnCode') && json.returnCode === 0)) {
                success = true;
            }

            if (success) {
                if (typeof options.mycallback != "undefined") {
                    options.mycallback.call(scope, json, options);
                }
            }
            else if (!success && typeof options.myerrorcallback !== "undefined") {
                options.myerrorcallback.call(scope, json, options, response);
            }
            else if (!success && typeof json.userMessage !== "undefined") {
                if (typeof options.maskedEl !== "undefined") {
                    options.maskedEl.unmask();
                }

                Ext.Msg.show({
                    title:   'ERROR',
                    msg:     "An error has occurred:<br>" + json.userMessage,
                    buttons: Ext.Msg.OK,
                    icon:    Ext.MessageBox.ERROR
                });
            }
            else {
                if (typeof options.maskedEl !== "undefined") {
                    options.maskedEl.unmask();
                }

                BRunner.ErrorAlert(json.errorText, null, null, null, null, response, null);
            }
        }
    });

Ext.Ajax.on('requestexception',
    function (conn, response, options) {
        Ext.Msg.show({
            title:   'ERROR',
            msg:     "An error has occurred:<br>" + response.status + ": " + response.statusText,
            buttons: Ext.Msg.OK,
            icon:    Ext.MessageBox.ERROR
        });
        BRunner.ConsoleLog("server response", response);
    });

