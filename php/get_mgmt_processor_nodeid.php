<?php

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    $mpId = $_POST['mpId'];

    $br  = new BladeRunner();
    $row = $br->getNodeIdByMPId($mpId);

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "nodeId"     => $row->nodeId
        )
    );
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

