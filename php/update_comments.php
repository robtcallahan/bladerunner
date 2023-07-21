<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMVMTable;

use STS\CMDB\CMDBServerTable;

try {
    // read the config file to get the dryMode value
    $config  = $GLOBALS['config'];
    $dryMode = $config->dryMode;

    // passed AJAX params
    $nodeId   = $_POST['nodeId'];
    $nodeType = $_POST['nodeType'];
    $field    = $_POST['field'];
    $value    = $_POST['value'];

    // get the host depending on what row the user clicked on
    $cmdbServerTable = new CMDBServerTable($useUserCredentials = true);
    $blade           = null;
    $vm              = null;
    $chassis         = null;
    if ($nodeType === "blade") {
        $simBladeTable = new HPSIMBladeTable();
        $blade         = $simBladeTable->getById($nodeId);
    } else if ($nodeType === "vm") {
        $simVmTable = new HPSIMVMTable();
        $vm         = $simVmTable->getById($nodeId);
    } else if ($nodeType === "chassis") {
        $simChTable = new HPSIMChassisTable();
        $chassis    = $simChTable->getById($nodeId);
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }

    // currently we're only updating the comments field
    $s = null;
    if ($field == "comments") {
        if ($value === "") $value = null;

        // update local MySQL row
        if (!$dryMode) {
            if ($nodeType == "blade") {
                $blade->setComments($value);
                $simBladeTable->update($blade);
                $s = $cmdbServerTable->getById($blade->getSysId());
            } else if ($nodeType == "vm") {
                $vm->setComments($value);
                $simVmTable->update($vm);
                $s = $cmdbServerTable->getById($vm->getSysId());
            } else if ($nodeType == "chassis") {
                $chassis->setComments($value);
                $simChTable->update($chassis);
                $s = $cmdbServerTable->getById($chassis->getSysId());
            }
        }

        // update CMDB
        if ($s && $s->getSysId()) {
            if ($value === null) {
                $changes = array(
                    "comments" => "NULL"
                );
            } else {
                $changes = array(
                    "comments" => addslashes($value)
                );
            }
            $json = json_encode($changes);
            if (!$dryMode) $cmdbServerTable->updateByJson($s->getSysId(), $json);
        }
    } else {
        throw new ErrorException("Unknown field specified for update: {$field}");
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0
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

