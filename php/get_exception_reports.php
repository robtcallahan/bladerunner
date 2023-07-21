<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIM;
use STS\HPSIM\HPSIMMgmtProcessorExceptionTable;
use STS\HPSIM\HPSIMBladeExceptionTable;
use STS\HPSIM\HPSIMVMExceptionTable;

try {
    $config = $GLOBALS['config'];
    $br     = new BladeRunner();

    // get the passed node
    $node = array_key_exists('node', $_POST) ? $_POST['node'] : 'root';

    if ($node === "root") {
        $nodes = array(
            array(
                "text" => "HP SIM",
                "id"   => "hpsim",
                "cls"  => "folder",
                "leaf" => false
            ),
            array(
                "text" => "SANScreen",
                "id"   => "sanscreen",
                "cls"  => "folder",
                "leaf" => false
            )
        );
    } else if ($node == "hpsim") {
        $r1 = $br->execCommand("wc -l {$config->simExcepDataDir}/{$config->simChassisNotInCmdb} | awk '{print $1}'");
        $r2 = $br->execCommand("wc -l {$config->simExcepDataDir}/{$config->simBladesNotInCmdb} | awk '{print $1}'");

        $hpsim                   = new HPSIM();
        $mgmtProcessorExceptions = $hpsim->getAllMgmtProcessorExceptions();
        $bladeExceptions         = $hpsim->getAllCoreHostingBladeExceptions();
        $vmExceptions            = $hpsim->getAllCoreHostingVmExceptions();
        $hyperExceptions         = $hpsim->getAllHypervisorConnectionExceptions();

        $nodes = array(
            array(
                "text" => "Mgmt Processor Exceptions (" . count($mgmtProcessorExceptions) . ")",
                "id"   => "hpsim/5",
                "cls"  => "file",
                "leaf" => true
            ),
            array(
                "text" => "Blade Exceptions (" . count($bladeExceptions) . ")",
                "id"   => "hpsim/1",
                "cls"  => "file",
                "leaf" => true
            ),
            array(
                "text" => "VM Exceptions (" . count($vmExceptions) . ")",
                "id"   => "hpsim/4",
                "cls"  => "file",
                "leaf" => true
            ),
            array(
                "text" => "Hypervisor Exceptions (" . count($hyperExceptions) . ")",
                "id"   => "hpsim/6",
                "cls"  => "file",
                "leaf" => true
            ),
            array(
                "text" => "Chassis Not in CMDB ({$r1[0]})",
                "id"   => "hpsim/2",
                "cls"  => "file",
                "leaf" => true
            ),
            array(
                "text" => "Blades Not in CMDB ({$r2[0]})",
                "id"   => "hpsim/3",
                "cls"  => "file",
                "leaf" => true
            )
        );
    } else if ($node == "sanscreen") {
        $r1    = $br->execCommand("wc -l {$config->ssExcepDataDir}/{$config->ssHostsNotInCmdb} | awk '{print $1}'");
        $nodes = array(
            array(
                "text" => "Hosts Not in CMDB ({$r1[0]})",
                "id"   => "sanscreen/1",
                "cls"  => "file",
                "leaf" => true
            )
        );
    }

    header('Content-Type: application/json');
    echo json_encode($nodes);
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

