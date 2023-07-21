<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBServerTable;
use STS\CMDB\CMDBSubsystemTable;

use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenVmTable;

use STS\Util\SysLog;

try {
    // config file
    $config = $GLOBALS['config'];

    // initialize syslog
    $sysLog = new SysLog($config->appName);
    $sysLog->setLogLevel($config->logLevel);
    $sysLog->debug("Debug starting...");

    // this user
    $actorUserName = $_SERVER["PHP_AUTH_USER"];

    // initialize the real-time log output file
    $rtLogFileName = "{$config->tmpDir}/rt-{$actorUserName}.log";
    $rtLog         = fopen($rtLogFileName, "a");

    // initialize the return message string and default tabs
    $logStr = "";
    $tab    = " &nbsp; &nbsp; &nbsp; ";
    $line   = "----------------------------------------";

    // get the passed params
    $nodeType      = $_POST['nodeType'];
    $hostId        = $_POST['hostId'];
    $hostIndex     = $_POST['hostIndex'];
    $numHosts      = $_POST['numHosts'];
    $subSysIdsHash = json_decode($_POST['subSysIdsHash']);

    // instantiate the classes
    $cmdbServerTable = new CMDBServerTable();
    $cmdbSubSysTable = new CMDBSubsystemTable();

    switch ($nodeType) {
        case "blade":
            $simBladeTable = new STS\HPSIM\HPSIMBladeTable();
            $host          = $simBladeTable->getById($hostId);
            $hostName      = $host->getFullDnsName();
            break;
        case "chassis":
            $simBladeTable = new STS\HPSIM\HPSIMBladeTable();
            $host          = $simBladeTable->getById($hostId);
            $hostName      = $host->getFullDnsName();
            break;
        case "array":
            $ssHostTable = new STS\SANScreen\SANScreenHostTable();
            $host        = $ssHostTable->getById($hostId);
            $hostName    = $host->getName();
            break;
        case "switch":
            // get the SANScreenHost from the passed hostId
            $ssHostTable = new SANScreenHostTable();
            $host        = $ssHostTable->getById($hostId);
            $hostName    = $host->getName();
            break;
        default:
            $host     = null;
            $hostName = "";
    }

    if ($hostIndex == 0) {
        logMsg("<br>Hosts:<br>{$line}<br>");
    }
    logMsg("[" . ($hostIndex + 1) . " of " . $numHosts . "] " . $hostName . "...");

    $hosts         = array();
    $subSysIdsHash = array();
    $snHost        = null;
    if ($host->getSysId() != "") {
        $snHost = $cmdbServerTable->getBySysId($host->getSysId());
    } else {
        try {
            $snHost = $cmdbServerTable->getByNameStartsWith($hostName);
        } catch (\ErrorException $e) {
            switch ($e->getCode()) {
                case \STS\CMDB\CMDBDAO::MULTIPLE_ENTRIES:
                    // multiple entries returned from CMDB
                    logMsg("ERROR: MULTIPLE ENTRIES<br>");
                    break;
                case \STS\CMDB\CMDBDAO::RETURN_EMPTY:
                    // empty return value from CDMB
                    logMsg("ERROR: EMPTY RETURN<br>");
                    break;
                default:
                    // some other error
                    logMsg("ERROR:UNKNOWN<br>");
            }
            print json_encode(
                array(
                    "success"        => 0,
                    "processedHosts" => $hosts,
                    "subSysIdsHash"  => $subSysIdsHash
                )
            );
            exit;
        }
    }

    if ($snHost->getSysId()) {
        $hosts[] = array(
            "name"  => $snHost->getName(),
            "sysId" => $snHost->getSysId()
        );
        if (preg_match("/,/", $snHost->getSubsystemListId())) {
            $subsystemIds = explode(",", $snHost->getSubsystemListId());
            for ($i = 0; $i < count($subsystemIds); $i++) {
                $subsystemId = $subsystemIds[$i];
                if (strlen($subsystemId) == 32 && !array_key_exists($subsystemId, $subSysIdsHash)) {
                    $subsystem                   = $cmdbSubSysTable->getById($subsystemId);
                    $subSysIdsHash[$subsystemId] = array(
                        "id"   => $subsystemId,
                        "name" => $subsystem->getName()
                    );
                }
            }
        } else {
            $subsystemId = $snHost->getSubsystemListId();
            if (strlen($subsystemId) == 32 && !array_key_exists($subsystemId, $subSysIdsHash)) {
                $subsystem                   = $cmdbSubSysTable->getById($subsystemId);
                $subSysIdsHash[$subsystemId] = array(
                    "id"   => $subsystemId,
                    "name" => $subsystem->getName()
                );
            }
        }
        logMsg("OK<br>");

        // get the vms for this host if exist from the cmdb
        $vms    = $cmdbServerTable->getChildren($snHost->getSysId());
        $numVms = count($vms);
        for ($j = 0; $j < $numVms; $j++) {
            $snVm = $vms[$j];
            logMsg($tab . "VM [" . ($j + 1) . " of " . $numVms . "] " . $snVm->getName() . "...");

            //$snVm = $cmdbServerTable->getBySysId($vm->getSysId());
            if ($snVm->getSysId()) {
                $hosts[] = array(
                    "name"  => $snVm->getName(),
                    "sysId" => $snVm->getSysId()
                );
                if (preg_match("/,/", $snVm->getSubsystemListId())) {
                    $subsystemIds = explode(",", $snVm->getSubsystemListId());
                    for ($i = 0; $i < count($subsystemIds); $i++) {
                        $subsystemId = $subsystemIds[$i];
                        if (strlen($subsystemId) == 32 && !array_key_exists($subsystemId, $subSysIdsHash)) {
                            $subsystem                   = $cmdbSubSysTable->getById($subsystemId);
                            $subSysIdsHash[$subsystemId] = array(
                                "id"   => $subsystemId,
                                "name" => $subsystem->getName()
                            );
                        }
                    }
                } else {
                    $subsystemId = $snHost->getSubsystemListId();
                    if (strlen($subsystemId) == 32 && !array_key_exists($subsystemId, $subSysIdsHash)) {
                        $subsystem                   = $cmdbSubSysTable->getById($subsystemId);
                        $subSysIdsHash[$subsystemId] = array(
                            "id"   => $subsystemId,
                            "name" => $subsystem->getName()
                        );
                    }
                }
                logMsg("OK<br>");
            } else {
                logMsg("ERROR: NOT FOUND<br>");
            }
        }
    } else {
        logMsg("ERROR: NOT FOUND<br>");
    }

    header('Content-Type: application/json');
    print json_encode(
        array(
            "success"        => 0,
            "processedHosts" => $hosts,
            "subSysIdsHash"  => $subSysIdsHash
        )
    );
    exit;
} catch (\Exception $e) {
    header('Content-Type: application/json');
    print json_encode(
        array(
            "success"    => 1,
            "errorCode"  => $e->getCode(),
            "errorText"  => $e->getMessage(),
            "errorFile"  => $e->getFile(),
            "errorLine"  => $e->getLine(),
            "errorStack" => $e->getTraceAsString()
        )
    );
    exit;
}

function logMsg($logMsg) {
    global $rtLog, $logStr;

    fwrite($rtLog, $logMsg);
    $logStr .= $logMsg;
}

