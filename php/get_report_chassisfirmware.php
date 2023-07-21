<?php

use STS\HPSIM\HPSIMMgmtProcessorTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMBladeTable;

include "../config/global.php";

try {

    $simMgmtProcessorTable = new HPSIMMgmtProcessorTable();
    $simSwitchTable        = new HPSIMSwitchTable();
    $simChassisTable       = new HPSIMChassisTable();
    $simBladeTable         = new HPSIMBladeTable();

    $rows         = $simChassisTable->getNetworks();
    $distSwitches = array();
    foreach ($rows as $r) {
        $distSwitches[] = $r->distSwitchName;
    }

// structure to return
    $data = array();

    foreach ($distSwitches as $distSwitch) {
        $chassises = $simChassisTable->getBySwitchName($distSwitch);

        $lastChassis = "";
        foreach ($chassises as $chassis) {
            // get the mm and switch
            // two mms and switches will be returned (active and standby)
            // select the one that has firmware defined
            $mms = $simMgmtProcessorTable->getByChassisId($chassis->getId());
            if (count($mms) > 0) {
                $mmFirmware = $mms[0]->getVersion() ? $mms[0]->getVersion() : $mms[1]->getVersion();
            } else {
                $mmFirmware = "N/A";
            }

            $switches   = $simSwitchTable->getByChassisId($chassis->getId());
            $swFirmware = "N/A";
            if (count($switches) > 0) {
                for ($j = 0; $j < count($switches); $j++) {
                    $sw = $switches[$j];
                    if (preg_match("/vc[12]$/", $sw->getDeviceName()) && $sw->getVersion()) {
                        $swFirmware = $sw->getVersion();
                    }
                }
            }

            // get all the blades to obtain the business services
            $blades = $simBladeTable->getByChassisId($chassis->getId());
            $bsList = array();
            foreach ($blades as $blade) {
                $bsList[] = $blade->getBusinessService();
            }
            $businessServices = array_unique($bsList);

            // now loop over our busiess services to create the grid
            foreach ($businessServices as $bs) {
                // add to our returning structure
                $data[] = array(
                    "distSwitch"      => $distSwitch,
                    "chassisId"       => $chassis->getId(),
                    "chassisName"     => $chassis->getDeviceName(),
                    "businessService" => $bs ? $bs : "-",
                    "mmFirmware"      => $mmFirmware,
                    "vcFirmware"      => $swFirmware
                );
            }
        }
    }

// sort the grid
    function sortData($a, $b) {
        $aST = preg_match("/Sterling/", $a['distSwitch']);
        $aDE = preg_match("/Denver/", $a['distSwitch']);
        $aCH = preg_match("/Charlotte/", $a['distSwitch']);

        $bST = preg_match("/Sterling/", $b['distSwitch']);
        $bDE = preg_match("/Denver/", $b['distSwitch']);
        $bCH = preg_match("/Charlotte/", $b['distSwitch']);

        #print "({$aST},{$aDE},{$aCH}) ({$bST},{$bDE},{$bCH})\n";

        // Dist switch locations in the following order: Sterling,
        if (($aST && !$bST) || ($aDE && !$bDE && !$bST)) {
            #printf("Level 1: %3d %-30s - %-30s\n", -1, $a['distSwitch'], $b['distSwitch']);
            return -1;
        } else if (($aDE && $bST) || ($aCH && !$bCH)) {
            #printf("Level 1: %3d %-30s - %-30s\n", 1, $a['distSwitch'], $b['distSwitch']);
            return 1;
        } else {
            #printf("Level 1: %3d %-30s - %-30s\n", 0, $a['distSwitch'], $b['distSwitch']);
            $aDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $a['distSwitch']);
            $bDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $b['distSwitch']);
            $r       = strcmp($aDistSw, $bDistSw);
            if (!$r) {
                $r = strcmp($a['chassisName'], $b['chassisName']);
                if (!$r) {
                    $ret = strcmp($a['businessService'], $b['businessService']);
                    #printf("Level 3: %3d %-30s - %-30s\n", $ret, $a['businessService'], $b['businessService']);
                    return $ret;
                } else {
                    #printf("Level 2: %3d %-30s - %-30s\n", $r, $a['chassisName'], $b['chassisName']);
                    return $r;
                }
            } else {
                #printf("Level 2: %3d %-30s - %-30s\n", $r, $a['distSwitch'], $b['distSwitch']);
                return $r;
            }
        }
    }

#echo "<pre>";
    usort($data, 'sortData');
#echo "</pre>";
#exit;

// now remove repeating dist switches and chassis names
    $grid           = array();
    $lastDistSwitch = "";
    $lastChassis    = "";
    $lastBS         = "";
    $firstTime      = true;
    for ($i = 0; $i < count($data); $i++) {
        $row = $data[$i];

        // skip rows with empty business service if there are more business services for this chassis
        if (($row['businessService'] == "-" || $row['businessService'] == "") && $row['chassisName'] == $data[$i + 1]['chassisName']) {
            continue;
        }

        if ($row['distSwitch'] !== $lastDistSwitch && !$firstTime) {
            $grid[] = array("divider" => true);
        }
        $grid[]         = array(
            "distSwitch"      => $row['distSwitch'] !== $lastDistSwitch ? $row['distSwitch'] : "",
            "chassisId"       => $row['chassisId'],
            "chassisName"     => $row['chassisName'] !== $lastChassis ? $row['chassisName'] : "",
            "businessService" => $row['businessService'],
            "mmFirmware"      => $row['chassisName'] !== $lastChassis ? $row['mmFirmware'] : "",
            "vcFirmware"      => $row['chassisName'] !== $lastChassis ? $row['vcFirmware'] : "",
            "divider"         => false
        );
        $lastDistSwitch = $row['distSwitch'];
        $lastChassis    = $row['chassisName'];
        $firstTime      = false;
    }


    if (isset($export) && $export) {
        include "export_report_chassisfirmware.php";
    } else {
        header('Content-Type: application/json');
        echo json_encode(
            array(
                "returnCode" => 0,
                "total"      => count($grid),
                "grid"       => $grid
            )
        );
    }
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

