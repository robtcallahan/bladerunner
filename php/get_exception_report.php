<?php

use STS\AD\ADUserTable;
use STS\Login\UserTable;
use STS\Login\LoginTable;

use STS\HPSIM\HPSIM;

include __DIR__ . "/../config/global.php";


try {
    $config = $GLOBALS['config'];

    // get the user and update the page view
    $userName  = $_SERVER["PHP_AUTH_USER"];
    $userTable = new UserTable();
    $actor     = $userTable->getByUserName($userName);

    $loginTable = new LoginTable();
    $loginTable->record($actor->getId());

    // ServiceNow site
    $snSite   = $config->servicenow->site;
    $snConfig = $config->servicenow->{$snSite};
    $snHost   = $snConfig->server;

    $reportId = $_POST['reportId'];

    $title    = null;
    $fileName = null;

    if ($reportId == "hpsim/1") {
        $title    = "Report: HP Blade Exceptions";
        $fileName = "{$config->exportDir}/{$config->simBladeExceptions}";
        $headers  = array("Num", "HP SIM Name", "CMDB Name", "Chassis", "Marked as Inv", "Power Status", "Slot", "Product Name", "CMDB Status", "Date Identified");

        $hpsim           = new HPSIM();
        $bladeExceptions = $hpsim->getAllCoreHostingBladeExceptions();

        function sortByChassisAndName($a, $b) {
            if ($a->chassisName > $b->chassisName) {
                return 1;
            } else if ($a->chassisName < $b->chassisName) {
                return -1;
            } else {
                if ($a->hpSimName > $b->hpSimName) {
                    return 1;
                } else if ($a->hpSimName < $b->hpSimName) {
                    return -1;
                } else {
                    return 0;
                }
            }
        }

        function sortByModelAndName($a, $b) {
            if ($a->productName > $b->productName) {
                return 1;
            } else if ($a->productName < $b->productName) {
                return -1;
            } else {
                if ($a->hpSimName > $b->hpSimName) {
                    return 1;
                } else if ($a->hpSimName < $b->hpSimName) {
                    return -1;
                } else {
                    return 0;
                }
            }
        }

        usort($bladeExceptions, 'sortByModelAndName');

        $ptr = fopen($fileName, "w");
        for ($i = 0; $i < count($bladeExceptions); $i++) {
            $e = $bladeExceptions[$i];

            $markedAsInv = $e->isInventory ? "Yes" : "No";
            $dateUpdated = preg_replace("/ \d\d:\d\d:\d\d/", "", $e->dateUpdated);

            $bladeLink   = "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade($e->bladeId);'>$e->hpSimName </span>";
            $chassisLink = "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis($e->chassisId);'>$e->chassisName </span>";

            $cmdbUrl  = "https://{$snHost}/nav_to.do?uri=cmdb_ci_server.do?sys_id={$e->cmdbSysId}";
            $cmdbLink = "<span title='Click to go to the CMDB entry' style='text-decoration: underline;cursor:pointer;' onclick=window.open('$cmdbUrl','_blank');>{$e->cmdbName}</span>";

            fwrite($ptr, "{$bladeLink},\"{$cmdbLink}\",{$chassisLink},{$markedAsInv},{$e->powerStatus},{$e->slotNumber},{$e->productName},{$e->cmInstallStatus},{$dateUpdated}\n");
        }
        fclose($ptr);
    } else if ($reportId == "hpsim/6") {
        $title    = "Report: Hypervisor Exceptions";
        $fileName = "{$config->exportDir}/{$config->simBladeExceptions}";
        $headers  = array("Num", "HP SIM Name", "CMDB Name", "Chassis", "Error", "Slot", "Product Name", "Date Identified");

        $hpsim           = new HPSIM();
        $bladeExceptions = $hpsim->getAllHypervisorConnectionExceptions();

        function sortByChassisAndName($a, $b) {
            if ($a->chassisName > $b->chassisName) {
                return 1;
            } else if ($a->chassisName < $b->chassisName) {
                return -1;
            } else {
                if ($a->hpSimName > $b->hpSimName) {
                    return 1;
                } else if ($a->hpSimName < $b->hpSimName) {
                    return -1;
                } else {
                    return 0;
                }
            }
        }

        usort($bladeExceptions, 'sortByChassisAndName');

        $ptr = fopen($fileName, "w");
        for ($i = 0; $i < count($bladeExceptions); $i++) {
            $e = $bladeExceptions[$i];

            $markedAsInv = $e->isInventory ? "Yes" : "No";
            $dateUpdated = preg_replace("/ \d\d:\d\d:\d\d/", "", $e->dateUpdated);
            $errorText   = preg_replace("/\n/", "<br>", $e->errorText);

            $bladeLink   = "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade($e->bladeId);'>$e->hpSimName </span>";
            $chassisLink = "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis($e->chassisId);'>$e->chassisName </span>";

            $cmdbUrl  = "https://{$snHost}/nav_to.do?uri=cmdb_ci_server.do?sys_id={$e->cmdbSysId}";
            $cmdbLink = "<span title='Click to go to the CMDB entry' style='text-decoration: underline;cursor:pointer;' onclick=window.open('$cmdbUrl','_blank');>{$e->cmdbName}</span>";

            fwrite($ptr, "{$bladeLink},\"{$cmdbLink}\",{$chassisLink},{$errorText},{$e->slotNumber},{$e->productName},{$dateUpdated}\n");
        }
        fclose($ptr);
    } else if ($reportId == "hpsim/2") {
        $title    = "Report: HP SIM Chassis Not In CMDB";
        $fileName = "{$config->simExcepDataDir}/{$config->simChassisNotInCmdb}";
        $headers  = array("Num", "Chassis Name", "Model");
    } else if ($reportId == "hpsim/3") {
        $title    = "Report: HP SIM Blades Not In CMDB";
        $fileName = "{$config->simExcepDataDir}/{$config->simBladesNotInCmdb}";
        $headers  = array("Num", "Blade Name", "Chassis Name", "IP Address", "Model");
    } else if ($reportId == "hpsim/4") {
        $title    = "Report: VM Exceptions";
        $fileName = "{$config->exportDir}/{$config->simVmExceptions}";
        $headers  = array("Num", "VM Name", "Blade Name", "Chassis", "Exception", "Date Identified");

        $hpsim        = new HPSIM();
        $vmExceptions = $hpsim->getAllCoreHostingVmExceptions();

        function sortByChassisBladeAndName($a, $b) {
            if ($a->chassisName > $b->chassisName) {
                return 1;
            } else if ($a->chassisName < $b->chassisName) {
                return -1;
            } else {
                if ($a->bladeName > $b->bladeName) {
                    return 1;
                } else if ($a->bladeName < $b->bladeName) {
                    return -1;
                } else {
                    if ($a->vmName > $b->vmName) {
                        return 1;
                    } else if ($a->vmName < $b->vmName) {
                        return -1;
                    } else {
                        return 0;
                    }
                }
            }
        }

        usort($vmExceptions, 'sortByChassisBladeAndName');

        $ptr = fopen($fileName, "w");
        for ($i = 0; $i < count($vmExceptions); $i++) {
            $e           = $vmExceptions[$i];
            $dateUpdated = preg_replace("/ \d\d:\d\d:\d\d/", "", $e->dateUpdated);

            $bladeLink   = "<span title='Click to go to blade' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToBlade($e->bladeId);'>$e->bladeName </span>";
            $chassisLink = "<span title='Click to go to chassis' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToChassis($e->chassisId);'>$e->chassisName </span>";
            $vmLink      = "<span title='Click to go to VM' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToVm($e->vmId);'>$e->vmName </span>";

            fwrite($ptr, "{$vmLink},{$bladeLink},{$chassisLink},{$e->exceptionDescr},{$dateUpdated}\n");
        }
        fclose($ptr);
    } else if ($reportId == "hpsim/5") {
        $title    = "Report: Management Processor Exceptions";
        $fileName = "{$config->exportDir}/{$config->simMPExceptions}";
        $headers  = array("Num", "Mgmt Processor", "Exception", "Date Identified");

        $hpsim        = new HPSIM();
        $mpExceptions = $hpsim->getAllMgmtProcessorExceptions();

        $ptr = fopen($fileName, "w");
        for ($i = 0; $i < count($mpExceptions); $i++) {
            $e           = $mpExceptions[$i];
            $dateUpdated = preg_replace("/ \d\d:\d\d:\d\d/", "", $e->dateUpdated);
            $nameLink    = "<span title='Click to go to management processor' style='text-decoration:underline; cursor:pointer;' onclick='app.chassisTree.goToMgmtProcessor($e->mpId);'>$e->mpName </span>";
            fwrite($ptr, "{$nameLink},{$e->excepDescr},{$dateUpdated}\n");
        }
        fclose($ptr);
    } else if ($reportId == "sanscreen/1") {
        $title    = "Report: SANScreen Hosts Not In CMDB";
        $fileName = "{$config->ssExcepDataDir}/{$config->ssHostsNotInCmdb}";
        $headers  = array("Num", "Host Name", "IP Address", "WWNs");
    }

    if (!file_exists($fileName)) {
        echo json_encode(
            array(
                "returnCode" => 0,
                "title"      => $title,
                "html"       => "We're very sorry, but no data file found for this report.<br>" .
                    "Perhaps you would like to submit a bug report for this issue by clicking <a href='javascript:app.sendFeedback();'>here</a>."
            )
        );
        exit;
    }

    $res      = stat($fileName);
    $fileDate = date("Y-m-d H:i", $res[9]);
    $title .= "<div style='float: right;'>Report Date: {$fileDate}</div>";

    #$file = file_get_contents($fileName);
    #$recs = explode("\n", $file);
    $filePtr = fopen($fileName, "r");
    #sort($recs);

    $html = "<div><table rules=rows><tr>";
    for ($i = 0; $i < count($headers); $i++) {
        $html .= "<th class='er-cell'><b>{$headers[$i]}</b></th>";
    }
    $html .= "</tr>";

    $row = 0;
    while ($cols = fgetcsv($filePtr)) {
        if ($cols[0] == "") continue;

        $row++;
        $lastColStr = "";
        $html .= "<tr><td class='er-cell'>{$row}</td>";
        for ($j = 0; $j < count($cols); $j++) {
            if (array_key_exists($j, $headers)) {
                $value = $cols[$j] ? $cols[$j] : "&nbsp;";
                $html .= "<td class='er-cell'>{$value}</td>";
            } else {
                $lastColStr .= $value;
            }
        }
        if ($lastColStr != "") {
            $html .= "<td class='er-cell'>{$lastColStr}</td>";
        }
        $html .= "</tr>";
    }

    $html .= "</table></div>";
    fclose($filePtr);

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "title"      => $title,
            "html"       => $html
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

