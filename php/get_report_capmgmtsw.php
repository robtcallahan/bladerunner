<?php

use STS\HPSIM\HPSIMBlade;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassis;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMBladeReservationTable;

$simBladeTable    = new HPSIMBladeTable();
$simChassisTable  = new HPSIMChassisTable();
$simBladeResTable = new HPSIMBladeReservationTable();

// get a list of inventory blades
$blades = $simBladeTable->getInventory();

// get a list of blade reservations
$resHash = $simBladeResTable->getAllHashByBladeId();

// initialize a structure to keep track of model, cores, cpus. We'll count the unique ones
$sites = array(
    "Sterling"  => array(),
    "Charlotte" => array()
);

// loop over each
for ($i = 0; $i < count($blades); $i++) {
    $b = $blades[$i];

    // skip if this blade has a reservation on it
    if (array_key_exists($b->getId(), $resHash)) continue;

    // get the chassis to obtain the site and skip non-Core Hosting
    $ch   = $simChassisTable->getById($b->getChassisId());
    $site = preg_match("/Charlotte/", $ch->getDistSwitchName()) ? "Charlotte" : "Sterling";

    // skip DECE, Mobile Cloud, Ultra & NT chassis
    if (preg_match("/(DECE|Mobile Cloud)/", $ch->getDistSwitchName())) continue;
    if (preg_match("/^(ch|st)(de|nt|ul)/", $ch->getDeviceName())) continue;
    if ($b->getProductName() == "") continue;

    // construct the hash value by concating these 3 field values
    #$hashKey = $ch->getDistSwitchName() . $ch->getDeviceName() . preg_replace("/ProLiant/", "", $b->getProductName()) . $b->getNumCoresPerCpu() . $b->getNumCpus() . $b->getMemorySizeGB();

    // put into the structure
    $grid[] = array(
        "site"       => $site,
        "distSwitch" => $ch->getDistSwitchName(),
        "chassis"    => $ch->getDeviceName(),
        "chassisId"  => $ch->getId(),
        "blade"      => $b->getCmdbName(),
        "bladeId"    => $b->getId(),
        "model"      => $b->getProductName(),
        "cpu"        => $b->getNumCpus() . " X " . $b->getNumCoresPerCpu(),
        "memGB"      => $b->getMemorySizeGB()
    );
    usort($grid, 'sortByModelChassisSwitch');
}

if (isset($export) && $export) {
    include "export_report_capmgmtsw.php";
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

function sortByModelChassisSwitch($a, $b) {
    $result = strcmp($a['model'], $b['model']);
    if ($result == 0) {
        $result = strcmp($a['chassis'], $b['chassis']);
        if ($result == 0) {
            return strcmp($a['distSwitch'], $b['distSwitch']);
        } else {
            return $result;
        }
    } else {
        return $result;
    }
}