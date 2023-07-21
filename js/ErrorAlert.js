/*******************************************************************************
 *
 * $Id: ErrorAlert.js 75201 2013-05-13 15:07:20Z rcallaha $
 * $Date: 2013-05-13 11:07:20 -0400 (Mon, 13 May 2013) $
 * $Author: rcallaha $
 * $Revision: 75201 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/js/ErrorAlert.js $
 *
 *******************************************************************************
 */

Ext.namespace('BRunner');

BRunner.ErrorAlert = function (msg, dataProxy, type, action, options, response, arg) {
    var json = Ext.util.JSON.decode(response.responseText),
        notify = new Ext.Notify({});

    Ext.MessageBox.buttonText.cancel = "Details";

    Ext.Msg.show({
        title:   'ERROR',
        msg:     msg,
        buttons: Ext.Msg.OKCANCEL,
        icon:    Ext.MessageBox.ERROR,
        fn:      function (buttonId) {
            if (buttonId == "cancel") {
                var html = "<div style='height:190; overflow:scroll;'><font style='font-family:arial; font-size:9pt'>";
                html += "<pre style='font-family:courier; font-size:8pt'>";
                html += "ERROR " + json.errorCode + ": " + json.errorText + "\n";
                html += "   in file: " + json.errorFile + "\n";
                html += "   at line: " + json.errorLine + "\n";
                html += "     stack: \n" + json.errorStack + "\n";
                if (typeof json.output != "undefined") {
                    html += "    output: \n" + json.output;
                }
                html += "</pre>";
                html += "</font></div>";

                var detailsWin = new Ext.Window({
                    title:      "ERROR",
                    modal:      true,
                    constrain:  true,
                    autoscroll: true,
                    width:      700,
                    height:     260,
                    layout:     'form',
                    items:      [
                        {
                            xtype:       'panel',
                            height:      240,
                            width:       690,
                            //autoScroll: true,
                            buttonAlign: 'right',
                            html:        html
                        }
                    ],
                    buttons:    [
                        {
                            text:      'Close',
                            listeners: {
                                "click": function () {
                                    detailsWin.close();
                                    return;
                                }
                            }
                        },
                        {
                            text:      'Report Error',
                            listeners: {
                                "click": function () {
                                    var body = "";

                                    body += "ERROR " + json.errorCode + ": " + json.errorText + "\n";
                                    body += "   in file: " + json.errorFile + "\n";
                                    body += "   at line: " + json.errorLine + "\n";
                                    body += "     stack: \n" + json.errorStack;

                                    Ext.Ajax.request({
                                        url:     'php/send_email.php',
                                        params:  {
                                            'emailSubject': "BRunner Error Report",
                                            'emailBody':    body
                                        },
                                        success: function (response, request) {
                                            var json = Ext.util.JSON.decode(response.responseText);

                                            if (json.returnCode == 0) {
                                                // let the user know that the email was successful
                                                notify.setAlert(notify.STATUS_INFO, "The Error Report has been sent to the website admin");
                                                detailsWin.close();
                                            }
                                            else {
                                                var msg = "An error has occurred.<br>The report could not be sent";
                                                BRunner.ErrorAlert(msg, null, null, null, null, response, null);
                                            }
                                        },
                                        failure: function (response, request) {
                                            Ext.Msg.show({
                                                title:   'ERROR',
                                                msg:     "An error has occurred:<br>" + response.statusText,
                                                buttons: Ext.Msg.OK,
                                                icon:    Ext.MessageBox.ERROR
                                            });
                                        }
                                    });
                                }
                            }
                        }
                    ]
                });
                detailsWin.show();
            }
        }
    });

    Ext.MessageBox.buttonText.cancel = "Cancel";

    return;

    // Always send an error report unless it's me
    if (BRunner.user.userName == "rcallaha") {
        return;
    }

    var body = [
        "Username: " + BRunner.user.userName + "\n",
        "EMail: " + BRunner.user.email + "\n\n",
        "ERROR " + json.errorCode + ": " + json.errorText + "\n",
        "   in file: " + json.errorFile + "\n",
        "   at line: " + json.errorLine + "\n",
        "     stack: \n" + json.errorStack + "\n\n",
        "output:\n" + json.output
    ].join("");

    Ext.Ajax.request({
        url:     'php/send_email.php',
        params:  {
            'emailFrom':    BRunner.user.email,
            'emailSubject': "BRunner Error Report",
            'emailBody':    body
        },
        success: function (response, request) {
            // ignore success
        },
        failure: function (response, request) {
            // ignore failure
        }
    });
};

