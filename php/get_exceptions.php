<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIM;

try {
    $hpsim = new HPSIM();
    $rows  = $hpsim->getAllBladeExceptions();

    $exceps = array();
    for ($i = 0; $i < count($rows); $i++) {
        $r = $rows[$i];

        if (preg_match("/^(st|ch)(de|nt)/", $r->chassisName)) continue;

        $o         = (object)array(
            "id"          => $r->id,
            "bladeId"     => $r->bladeId,
            "chassisId"   => $r->chassisId,
            "chassisName" => $r->chassisName,
            "bladeName"   => $r->bladeName,
            "productName" => $r->productName,
            "slotNumber"  => $r->slotNumber,
            "powerStatus" => $r->powerStatus,
            "excepDescr"  => $r->excepDescr,
            "dateUpdated" => $r->dateUpdated,
            "userUpdated" => $r->userUpdated
        );
        $exceps [] = $o;
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "total"      => count($exceps),
            "exceptions" => $exceps
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

