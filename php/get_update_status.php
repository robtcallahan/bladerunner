<?php

include __DIR__ . "/../config/global.php";

try {
    $config = $GLOBALS['config'];

    // last update of data
    if (file_exists("{$config->lastUpdateDataDir}/{$config->simLastUpdateFile}")) {
        $sim = file_get_contents("{$config->lastUpdateDataDir}/{$config->simLastUpdateFile}");
    } else {
        $sim = "Unknown";
    }

    if (file_exists("{$config->lastUpdateDataDir}/{$config->chassisLastUpdateFile}")) {
        $chassis = file_get_contents("{$config->lastUpdateDataDir}/{$config->chassisLastUpdateFile}");
    } else {
        $chassis = "Unknown";
    }

    if (file_exists("{$config->lastUpdateDataDir}/{$config->wwnLastUpdateFile}")) {
        $wwn = file_get_contents("{$config->lastUpdateDataDir}/{$config->wwnLastUpdateFile}");
    } else {
        $wwn = "Unknown";
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "sim"        => $sim,
            "chassis"    => $chassis,
            "wwn"        => $wwn
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

