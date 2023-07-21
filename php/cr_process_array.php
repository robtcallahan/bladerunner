<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBStorageDeviceTable;
use STS\CMDB\CMDBSubsystemTable;

use STS\SANScreen\SANScreenArrayTable;

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

    // instantiate the classes
    $cmdbStorageDeviceTable = new CMDBStorageDeviceTable();
    $cmdbSubSysTable        = new CMDBSubsystemTable();

    $ssArrayTable = new SANScreenArrayTable();

    // get the passed host id
    $arrayId       = $_POST['arrayId'];
    $arrayIndex    = $_POST['arrayIndex'];
    $numArrays     = $_POST['numArrays'];
    $subSysIdsHash = json_decode($_POST['subSysIdsHash']);

    // get the SANScreenArray from the passed arrayId
    $ssArray = $ssArrayTable->getById($arrayId);

    if ($arrayIndex == 0) {
        logMsg("<br>Arrays:<br>{$line}<br>");
    }
    logMsg("[" . ($arrayIndex + 1) . " of " . $numArrays . "] " . $ssArray->getName() . "...");

    $arrays        = array();
    $subSysIdsHash = array();
    if ($ssArray->getSerialNumber() == "") {
        logMsg("ERROR: NOT FOUND in SANScreen DB<br>");
    } else {
        try {
            $snArray = $cmdbStorageDeviceTable->getBySerialNumber($ssArray->getSerialNumber());
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
                    "success"         => 0,
                    "processedArrays" => $hosts,
                    "subSysIdsHash"   => $subSysIdsHash
                )
            );
            exit;
        }

        if ($snArray->getSysId()) {
            $arrays[] = array(
                "name"  => $snArray->getName(),
                "sysId" => $snArray->getSysId()
            );

            $ssId = $snArray->getSubsystemListId();
            if (preg_match("/,/", $ssId)) {
                $ar = explode(",", $ssId);
                for ($i = 0; $i < count($ar); $i++) {
                    if (!array_key_exists($ssId, $subSysIdsHash)) {
                        $ss                   = $cmdbSubSysTable->getById($ssId);
                        $subSysIdsHash[$ssId] = array(
                            "id"   => $ssId,
                            "name" => $ss->getName()
                        );
                    }
                }
            } else {
                if (!array_key_exists($ssId, $subSysIdsHash)) {
                    $ss                   = $cmdbSubSysTable->getById($ssId);
                    $subSysIdsHash[$ssId] = array(
                        "id"   => $ssId,
                        "name" => $ss->getName()
                    );
                }
            }
            logMsg("OK<br>");
        } else {
            logMsg("ERROR: NOT FOUND<br>");
        }
    }

    header('Content-Type: application/json');
    print json_encode(
        array(
            "success"         => 0,
            "processedArrays" => $arrays,
            "subSysIdsHash"   => $subSysIdsHash
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

