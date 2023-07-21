<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBlade;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMVM;
use STS\HPSIM\HPSIMVMTable;

use STS\CMDB\CMDBServerTable;
use STS\CMDB\CMDBSubsystemTable;
use STS\CMDB\CMDBUserTable;

use STS\SANScreen\SANScreenHostTable;

try {
    // config file
    $config    = $GLOBALS['config'];
    $startTime = time();

    // this user
    $actorUserName = $_SERVER["PHP_AUTH_USER"];

    // get the params
    $nodeType = $_POST['nodeType'];
    $dbId     = $_POST['dbId'];

    // initialize the real-time log output file
    $rtLogFileName = "{$config->tmpDir}/rt-{$actorUserName}.log";
    $rtLog         = fopen($rtLogFileName, "w");

    // initialize the return message string and default tabs
    $logStr = "";
    $tab    = " &nbsp; &nbsp; &nbsp; ";

    // instantiate the classes
    $cmdbServerTable = new CMDBServerTable();
    $subSysTable     = new CMDBSubsystemTable();
    $cmdbUserTable   = new CMDBUserTable();

    // create an array of hosts based upon whether the user clicked on a blade, chassis or array
    $hosts = array();
    if ($nodeType === "blade") {
        logMsg("Getting the list of VMs on this blade...<br>");

        $simBladeTable = new HPSIMBladeTable();
        $simVmTable    = new HPSIMVMTable();

        $b   = $simBladeTable->getById($dbId);
        $vms = $simVmTable->getByBladeId($b->getId());

        $hosts[] = array(
            "name"  => getBladeName($b),
            "sysId" => $b->getSysId()
        );
        logMsg("{$b->getFullDnsName()}<br>");

        for ($i = 0; $i < count($vms); $i++) {
            $v       = $vms[$i];
            $hosts[] = array(
                "name"  => getVMName($v),
                "sysId" => $v->getSysId()
            );
            logMsg("{$tab} {$v->getFullDnsName()}<br>");
        }
        logMsg(count($hosts) . " VMs found<br><br>");
    } else if ($nodeType === "chassis") {
        logMsg("Getting the list of blades and VMs on this chassis...<br>");

        $simBladeTable = new HPSIMBladeTable();
        $simVmTable    = new HPSIMVMTable();

        // get a list of blades from the chassis
        $blades = $simBladeTable->getByChassisId($dbId);

        // add the list of VMs from each blade
        for ($i = 0; $i < count($blades); $i++) {
            $b       = $blades[$i];
            $hosts[] = array(
                "name"  => getBladeName($b),
                "sysId" => $b->getSysId()
            );
            logMsg("{$b->getFullDnsName()}<br>");

            $vms = $simVmTable->getByBladeId($b->getId());
            for ($j = 0; $j < count($vms); $j++) {
                $v       = $vms[$j];
                $hosts[] = array(
                    "name"  => getVMName($v),
                    "sysId" => $v->getSysId()
                );
                logMsg("{$tab} {$v->getFullDnsName()}<br>");
            }
        }
        logMsg(count($hosts) . " hosts and VMs found<br><br>");
    } else if ($nodeType === "array") {
        logMsg("Getting a list of hosts and VMs on this array...<br>");

        $ssHostTable = new SanScreenHostTable();

        $arrayHosts = $ssHostTable->getByArrayId($dbId);
        for ($i = 0; $i < count($arrayHosts); $i++) {
            $h       = $arrayHosts[$i];
            $hosts[] = array(
                "name"  => $h->getName() ? $h->getName() : null,
                "sysId" => $h->getSysId()
            );
            logMsg("{$h->getName()}<br>");

            // get the vms
            if ($h->getSysId()) {
                $vms = $cmdbServerTable->getChildren($h->getSysId());
                for ($j = 0; $j < count($vms); $j++) {
                    $vm      = $vms[$j];
                    $hosts[] = array(
                        "name"  => $vm->getName(),
                        "sysId" => $vm->getSysId()
                    );
                    logMsg("{$tab} {$vm->get('name')}<br>");
                }
            }
        }

        logMsg(count($hosts) . " hosts and VMs found<br><br>");
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }

    // hash of subsystems that we'll collect, the hash will dedup
    $subsystems = array();

    // loop thru all the hosts
    logMsg("<br>Processing hosts...<br>");
    for ($i = 0; $i < count($hosts); $i++) {
        $h = $hosts[$i];
        if ($h['name'] == null) continue;

        logMsg("{$tab} {$h['name']}: ");

        // look up the host in the CMDB
        $s = null;
        if ($h['sysId']) {
            $s = $cmdbServerTable->getById($h['sysId']);
        } else {
            try {
                $s = $cmdbServerTable->getByNameStartsWith($h['name']);
            } catch (Exception $e) {
                if ($e->getCode() == 69) {
                    logMsg("multiple entries found in CMDB; Skipping");
                    continue;
                }
            }
        }

        // only process if found
        $hostSysId = $s->getSysId();
        if ($hostSysId) {
            // get the subsystem(s) for this server
            // could have multiple subsystems; check for comma; if found process as array
            $ssIdArray = array();
            $ssId      = $s->getSubsystemListId();
            if (preg_match("/,/", $ssId)) {
                $ssIdArray = explode(",", $ssId);
            } else {
                $ssIdArray = array($ssId);
            }

            // lookup and add the subsystems to our array
            $firstSs = true;
            for ($j = 0; $j < count($ssIdArray); $j++) {
                if (strlen($ssIdArray[$j]) != 32) continue;

                $ss = $subSysTable->getById($ssIdArray[$j]);

                if ($ss->getSysId()) {
                    logMsg($firstSs ? $ss->getName() : "," . $ss->getName());
                    // add to our array
                    $subsystems[$ss->getSysId()] = $ss;
                    $firstSs                     = false;
                }
            }
            logMsg("<br>");
        } else {
            logMsg("Not found in CMDB<br>");
        }
    }
    logMsg("<br>");

    // create a list of emails from the list of subsystems and pass it back to the javascript
    logMsg("Looking up email addresses...<br>");
    $emailsHash = array();
    $ssAndOsm   = array();
    /** @var $ss STS\CMDB\CMDBSubsystem */
    foreach ($subsystems as $sysId => $ss) {
        $ssAndOsm[] = array(
            "ssName" => $ss->getName(),
            "osm"    => $ss->getOwningSupportManager()
        );

        // look up the user
        $user                          = $cmdbUserTable->getById($ss->getOwningSupportManagerId());
        $emailsHash[$user->getEmail()] = $user;
    }
    $emails = array();
    foreach ($emailsHash as $email => $user) {
        $emails[] = $email;
    }
    logMsg("{$tab} " . count($emails) . " emails found.<br><br>");

    logMsg("Complete<br>");
    $endTime       = time();
    $elapsedSecs   = $endTime - $startTime;
    $elapsedFormat = sprintf("%02d:%02d", floor($elapsedSecs / 60), $elapsedSecs % 60);
    logMsg("Start Time: " . date("Y-m-d H:i:s", $startTime) . "<br>");
    logMsg("End Time: " . date("Y-m-d H:i:s", $endTime) . "<br>");
    logMsg("Elapsed Time: " . $elapsedFormat . "<br><br>");

    logMsg("Click OK to close this window and start the email editor.<br>");

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "log"        => $logStr,
            "ssAndOsm"   => $ssAndOsm,
            "emails"     => $emails
        )
    );
    unlink($rtLogFileName);
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

function logMsg($logMsg) {
    global $rtLog, $logStr;

    fwrite($rtLog, $logMsg);
    $logStr .= $logMsg;
}

function getBladeName(HPSIMBlade $b) {
    if ($b->getCmdbName()) {
        return $b->getCmdbName();
    } else if ($b->getFullDnsName()) {
        return $b->getFullDnsName();
    } else if ($b->getDeviceName()) {
        return $b->getDeviceName();
    } else {
        return "";
    }
}

function getVMName(HPSIMVM $vm) {
    if ($vm->getFullDnsName()) {
        return $vm->getFullDnsName();
    } else if ($vm->getDeviceName()) {
        return $vm->getDeviceName();
    } else {
        return "";
    }
}
