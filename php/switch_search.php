<?php

include __DIR__ . "/../config/global.php";

use STS\SANScreen\SANScreen;

try {
    $query = $_POST['query'];

    $ss = new SANScreen();

    $arrays = $ss->getArraysAndNodeIdsWithSwitchId($query);
    $hosts  = $ss->getHostNamesAndNodeIdsWithSwitchId($query);
    $vms    = $ss->getVMsAndNodeIdsWithSwitchid($query);

    $data = array_merge($arrays, $hosts, $vms);

    function sortByName($a, $b) {
        return strcmp($a['name'], $b['name']);
    }

    usort($data, 'sortByName');

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "total"      => count($data),
            "data"       => $data
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

