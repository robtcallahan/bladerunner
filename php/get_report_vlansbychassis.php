<?php

use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
use STS\HPSIM\HPSIMVLANDetailTable;

// start with the distribution switches
$simChassisTable     = new HPSIMChassisTable();
$simSwitchTable      = new HPSIMSwitchTable();
$simVlanTable        = new HPSIMVLANTable();
$simVlanDetailsTable = new HPSIMVLANDetailTable();
$neumatic            = new NeuMatic();

$rows         = $simChassisTable->getNetworks();
$distSwitches = array();
foreach ($rows as $row) {
    $distSwitches[] = $row->distSwitchName;
}

// sort by dist switch
function sortByDistSwitch($a, $b) {
    $aST = preg_match("/Sterling/", $a);
    $aDE = preg_match("/Denver/", $a);
    $aCH = preg_match("/Charlotte/", $a);

    $bST = preg_match("/Sterling/", $b);
    $bDE = preg_match("/Denver/", $b);
    $bCH = preg_match("/Charlotte/", $b);

    // Dist switch locations in the following order: Sterling,
    if (($aST && !$bST) || ($aDE && !$bDE && !$bST)) {
        return -1;
    } else if (($aDE && $bST) || ($aCH && !$bCH)) {
        return 1;
    } else {
        $aDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $a);
        $bDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $b);
        $r       = strcmp($aDistSw, $bDistSw);
        return $r;
    }
}
usort($distSwitches, 'sortByDistSwitch');

$grid = array();

$lastDistSwitch = "";
$firstTime      = true;
foreach ($distSwitches as $distSwitch) {
    $chassises = $simChassisTable->getBySwitchName($distSwitch);

    $lastChassisName = "";
    foreach ($chassises as $chassis) {
        $switch = $simSwitchTable->getActiveByChassisId($chassis->getId());

        if ($switch->getId()) {
            $vlans = $simVlanTable->getBySwitchId($switch->getId());

            if ($distSwitch != $lastDistSwitch && !$firstTime) {
                $grid[] = array("divider" => true);
            }
            foreach ($vlans as $vlan) {
                $subnetMask = "";
                $gateway    = "";

                if ($vlan->getVlanId() != "-- --") {
                    $vlanDetails = $simVlanDetailsTable->getByVlanIdAndDistSwitchName($vlan->getVlanId(), $distSwitch);
                    if ($vlanDetails->getVlanId()) {
                        $subnetMask = $vlanDetails->getSubnetMask();
                        $gateway    = $vlanDetails->getGateway();
                    } else {
                        $vlanDetails = $neumatic->getVlanByVlanIdAndDistSwitch($vlan->getVlanId(), $distSwitch);
                        if ($vlanDetails && $vlanDetails->vlanId) {
                            $subnetMask = $vlanDetails->netmask;
                            $gateway    = $vlanDetails->gateway;
                        }
                    }
                }
                $grid[]          = array(
                    "distSwitch"  => $distSwitch != $lastDistSwitch ? $distSwitch : "",
                    "chassisId"   => $chassis->getDeviceName() != $lastChassisName ? $chassis->getId() : 0,
                    "chassisName" => $chassis->getDeviceName() != $lastChassisName ? $chassis->getDeviceName() : "",
                    "vlanName"    => $vlan->getName(),
                    "vlanId"      => $vlan->getVlanId(),
                    "subnetMask"  => $subnetMask,
                    "gateway"     => $gateway,
                    "divider"     => false
                );
                $lastChassisName = $chassis->getDeviceName();
                $lastDistSwitch  = $distSwitch;
                $firstTime       = false;
            }
        }
    }
}

if (isset($export) && $export) {
    include "export_report_vlansbychassis.php";
} else {
    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "total"      => count($grid),
            "grid"       => $grid
        )
    );
    exit;
}
