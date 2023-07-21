<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIM;

try {
    $query = array_key_exists('query', $_POST) ? $_POST['query'] : null;

    $hpsim   = new HPSIM();
    $results = $hpsim->getDistSwitches($query);

    $switches = array();
    for ($i = 0; $i < count($results); $i++) {
        $switches[] = array(
            "name" => $results[$i]->distSwitchName
        );
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "total"      => count($switches),
            "switches"   => $switches
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

