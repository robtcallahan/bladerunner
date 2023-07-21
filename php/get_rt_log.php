<?php

include __DIR__ . "/../config/global.php";

try {
    // config file
    $config = $GLOBALS['config'];

    // this user
    $actorUserName = $_SERVER["PHP_AUTH_USER"];

    // initialize the real-time log output file
    $logFile = "{$config->tmpDir}/rt-{$actorUserName}.log";
    if (file_exists($logFile)) {
        $file = file_get_contents($logFile);
        echo json_encode(
            array(
                "returnCode" => 0,
                "log"        => $file
            )
        );
    } else {
        header('Content-Type: application/json');
        echo json_encode(
            array(
                "returnCode" => 0,
                "log"        => "done"
            )
        );
    }
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

