<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;

use STS\CMDB\CMDBServerTable;

try {
    $chassisId = $_POST['chassisId'];

    $simBladeTable   = new HPSIMBladeTable();
    $cmdbServerTable = new CMDBServerTable();

    $blades = $simBladeTable->getByChassisId($chassisId);

    $hostList = "";
    for ($j = 0; $j < count($blades); $j++) {
        $blade = $blades[$j];

        if ($blade->getFullDnsName()) {
            $hostList .= $blade->getFullDnsName() . "\n";
            $cmBlade = $cmdbServerTable->getByName($blade->getFullDnsName());
        } else {
            $hostList .= $blade->getDeviceName() . "\n";
            $cmBlade = $cmdbServerTable->getByNameStartsWith($blade->getDeviceName());
        }
        if ($cmBlade->getName() != null) {
            $vms = $cmdbServerTable->getChildren($cmBlade->getSysId());
            if (count($vms) > 0) {
                for ($k = 0; $k < count($vms); $k++) {
                    $vm = $vms[$k];
                    $hostList .= $vm->getName() . "\n";
                }
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "hostList"   => rtrim($hostList)
        )
    );
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
}

