<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMVMTable;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    // get the user and update the page view
    $userName = $_SERVER["PHP_AUTH_USER"];

    // set default time zone
    date_default_timezone_set("Greenwich");

    // get the passed node
    $node = null;
    if (array_key_exists('node', $_REQUEST)) {
        $node = $_REQUEST['node'];
    } else {
        throw new ErrorException("node not passed. No idea what to do");
    }

    if (preg_match("/^\/([A-Za-z]+)/", $node, $m)) {
        $file = "get_report_tree_" . $m[1] . ".php";
        include($file);
    }


    #header('Content-Type: application/json');
    #echo json_encode($nodes);
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json');
    print json_encode(
        array(
            "returnCode" => 1,
            "errorCode"  => $e->getCode(),
            "errorText"  => $e->getMessage(),
            "errorFile"  => $e->getFile(),
            "errorLine"  => $e->getLine(),
            "errorStack" => $e->getTraceAsString()
        )
    );
    exit;
}

