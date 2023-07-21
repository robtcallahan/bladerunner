<?php
/*******************************************************************************
 *
 * $Id: index.php 76871 2013-07-16 17:40:14Z rcallaha $
 * $Date: 2013-07-16 13:40:14 -0400 (Tue, 16 Jul 2013) $
 * $Author: rcallaha $
 * $Revision: 76871 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/index.php $
 *
 *******************************************************************************
 */

include __DIR__ . "/config/global.php";

use STS\AD\ADUserTable;
use STS\Login\UserTable;
use STS\Login\LoginTable;
use STS\Login\PageViewTable;

try {
    // config settings
    $config    = $GLOBALS['config'];
    $userTable = new UserTable();

    // obtain the LDAP auth'd username and get the Active Directory information for the user
    if ($_SERVER['SERVER_NAME'] == "localhost") {
        $userName = "rcallaha";
        $actor    = $userTable->getByUserName($userName);

        if (!$actor->get('id')) {
            // user does not exist in the local user table. create an entry
            $actor->setAccessCode(0);
            $actor->setUserName($userName);
            $actor = $userTable->create($actor);
        } else {
            $userTable->update($actor);
        }

        // now update the info in the login table with last login timestamp and web agent info
        $loginTable = new LoginTable();
        $loginTable->record($actor->getId());
        $pvTable = new PageViewTable();
        $pvTable->record($actor->getId());
    } else if (array_key_exists("PHP_AUTH_USER", $_SERVER)) {
        $userName = $_SERVER["PHP_AUTH_USER"];

        // check if user exists in our db. if not, we'll just skip the logging step for now
        $actor = $userTable->getByUserName($userName);

        try {
            // use AD ldap to get info and save to local table
            $adUserTable = new ADUserTable();
            $adUser      = $adUserTable->getByUid($userName);

            if ($adUser && $adUser->get('firstName')) {
                foreach (array('empId', 'firstName', 'lastName', 'title', 'dept', 'email', 'office', 'officePhone', 'mobilePhone') as $key) {
                    $actor->set($key, $adUser->get($key));
                }
            }
        } catch (ErrorException $e) {
            // skip errors
        }

        if (!$actor->get('id')) {
            // user does not exist in the local user table. create an entry
            $actor->setAccessCode(0);
            $actor->setUserName($userName);
            $actor = $userTable->create($actor);
        } else {
            $userTable->update($actor);
        }

        // now update the info in the login table with last login timestamp and web agent info
        $loginTable = new LoginTable();
        $loginTable->record($actor->getId());
        $pvTable = new PageViewTable();
        $pvTable->record($actor->getId());
    } else {
        $returnHash = array(
            "returnCode" => 1,
            "errorCode"  => 0,
            "errorText"  => "User is unknown. Perhaps authentication is turned off."
        );
        echo "<pre>" . print_r($returnHash, true) . "</pre>";
        exit;
    }

    // sn site
    $snSiteTrans = array(
        "prod" => "Production",
        "test" => "Test",
        "int"  => "Test (Int)",
        "dev"  => "Development"
    );
    $snSite      = $snSiteTrans[$config->servicenow->site];

    // define the BURT environment based upon the hostname (lame, but good enough for now)
    if (preg_match("/~/", $_SERVER["REQUEST_URI"])) {
        $env = "Development (User Instance)";
    } else if (preg_match("/bladerunner-dev|stopcdvvt1.va|localhost/", $_SERVER["SERVER_NAME"])) {
        $env = "Development";
    } else if (preg_match("/bladerunner-qa|stopcqavt1.va/", $_SERVER["SERVER_NAME"])) {
        $env = "QA";
    } else if (preg_match("/bladerunner.ops|chtlcprvt1|stopcprvt1/", $_SERVER["SERVER_NAME"])) {
        $env = "Production";
    } else {
        $env = "Unknown";
    }

    // release info
    $release = file_get_contents("ABOUT");
    $release = rtrim($release);

    // last update of data
    if (file_exists("{$config->lastUpdateDataDir}/{$config->simLastUpdateFile}")) {
        $simLastUpdate = file_get_contents("{$config->lastUpdateDataDir}/{$config->simLastUpdateFile}");
    } else {
        $simLastUpdate = "Unknown";
    }
    if (file_exists("{$config->lastUpdateDataDir}/{$config->chassisLastUpdateFile}")) {
        $chassisLastUpdate = file_get_contents("{$config->lastUpdateDataDir}/{$config->chassisLastUpdateFile}");
    } else {
        $chassisLastUpdate = "Unknown";
    }
    if (file_exists("{$config->lastUpdateDataDir}/{$config->wwnLastUpdateFile}")) {
        $wwnLastUpdate = file_get_contents("{$config->lastUpdateDataDir}/{$config->wwnLastUpdateFile}");
    } else {
        $wwnLastUpdate = "Unknown";
    }

    // reports tab permissions
    $reportsAccess = array_search($actor->getUserName(), $config->reports->users) === false ? 0 : 1;

    // concatenate a bunch of js files
    $js = "";
    $js .= file_get_contents("js/Notify.js");

    $js .= file_get_contents("js/Constants.js");
    $js .= file_get_contents("js/Helpers.js");
    $js .= file_get_contents("js/ConsoleLog.js");
    $js .= file_get_contents("js/ErrorAlert.js");

    $js .= file_get_contents("js/LoginsGridPanel.js");

    $js .= file_get_contents("js/AutoSizeColumns.js");

    $js .= file_get_contents("js/treegrid/TreeGridSorter.js");
    $js .= file_get_contents("js/treegrid/TreeGrid.js");
    $js .= file_get_contents("js/treegrid/TreeGridColumnResizer.js");
    $js .= file_get_contents("js/treegrid/TreeGridColumns.js");
    $js .= file_get_contents("js/treegrid/TreeGridLoader.js");
    $js .= file_get_contents("js/treegrid/TreeGridNodeUI.js");

    $js .= file_get_contents("js/Ext.tree.TreeSerializer.js");
    $js .= file_get_contents("js/Ext.ux.tree.EditorGrid.js");

    $js .= file_get_contents("js/ColumnHeaderGroup.js");

    $js .= file_get_contents("js/Ajax.js");

} catch (Exception $e) {
    echo "<html><body><pre>";
    echo "Error " . $e->getCode() . ": " . $e->getMessage() . "\n";
    echo "  in file: " . $e->getFile() . "\n";
    echo "  at line: " . $e->getLine() . "\n";
    echo "    trace:\n" . $e->getTraceAsString() . "\n";
    echo "</pre></body></html>";
    exit;
}

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>

    <title>Blade Runner <?= $release ?> - <?= $env ?></title>

    <link rel="shortcut icon" href="resources/images/blade_runner1.ico"/>

    <link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css"/>
    <link rel="stylesheet" type="text/css" href="resources/css/app.css"/>


    <?php if (preg_match("/Development/", $env)) { ?>
        <script type='text/javascript' src='/ext/adapter/ext/ext-base.js'></script>
        <script type='text/javascript' src='/ext/ext-all-debug.js'></script>
    <?php } else { ?>
        <script type='text/javascript' src='/ext/adapter/ext/ext-base.js'></script>
        <script type='text/javascript' src='/ext/ext-all.js'></script>
    <?php } ?>

    <!--
    <script type='text/javascript' src='js/Notify.js'></script>

    <script type='text/javascript' src='js/Constants.js'></script>
    <script type='text/javascript' src='js/Helpers.js'></script>
    <script type='text/javascript' src='js/ConsoleLog.js'></script>
    <script type='text/javascript' src='js/ErrorAlert.js'></script>

    <script type='text/javascript' src='js/LoginsGridPanel.js'></script>

    <script type='text/javascript' src='js/AutoSizeColumns.js'></script>

    <script type='text/javascript' src='js/treegrid/TreeGridSorter.js'></script>
    <script type='text/javascript' src='js/treegrid/TreeGrid.js'></script>
    <script type='text/javascript' src='js/treegrid/TreeGridColumnResizer.js'></script>
    <script type='text/javascript' src='js/treegrid/TreeGridColumns.js'></script>
    <script type='text/javascript' src='js/treegrid/TreeGridLoader.js'></script>
    <script type='text/javascript' src='js/treegrid/TreeGridNodeUI.js'></script>

    <script type='text/javascript' src='js/Ext.tree.TreeSerializer.js'></script>
    <script type='text/javascript' src='js/Ext.ux.tree.EditorGrid.js'></script>

    <script type='text/javascript' src='js/Ajax.js'></script>
    -->
    <script type='text/javascript'>
        <?=$js?>
    </script>

    <script type='text/javascript' src='soundmanager/js/soundmanager2-nodebug-jsmin.js'></script>
    <script>
        soundManager.url = 'soundmanager/swf/';
    </script>

    <script type='text/javascript' src='js/BladeReservationsGridPanel.js'></script>
    <script type='text/javascript' src='js/ChassisTree.js'></script>
    <script type='text/javascript' src='js/ArrayTree.js'></script>
    <script type='text/javascript' src='js/SwitchTree.js'></script>
    <script type='text/javascript' src='js/ReportsTree.js'></script>
    <script type='text/javascript' src='js/ReportsGrid.js'></script>
    <script type='text/javascript' src='js/ExceptionsPanel.js'></script>

    <script type='text/javascript' src='js/App.js'></script>
    <script type='text/javascript' src='js/main.js'></script>

    <script type='text/javascript'>
        Ext.namespace('BRunner');
        BRunner.env = '<?=$env?>';
        BRunner.release = '<?=$release?>';
        BRunner.snSite = '<?=$snSite?>';
        BRunner.dryMode = '<?=$config->dryMode?>';
        BRunner.simLastUpdate = '<?=$simLastUpdate?>';
        BRunner.chassisLastUpdate = '<?=$chassisLastUpdate?>';
        BRunner.wwnLastUpdate = '<?=$wwnLastUpdate?>';
        BRunner.reportsAccess = <?=$reportsAccess?>;
        BRunner.actor = {
            id:          <?=$actor->getId()?>,
            firstName: '<?=$actor->getFirstName()?>',
            lastName: '<?=$actor->getLastName()?>',
            userName: '<?=$actor->getUserName()?>',
            nickName: '<?=$actor->getNickName()?>',
            email: '<?=$actor->getEmail()?>'
        };
    </script>
</head>

<body id="body">
<div id="minion1" class="minion"><img src="resources/images/minions1.png"></div>
<div id="minion_fire" class="minion"><img src="resources/images/minion_fire.png"></div>
</body>
</html>

