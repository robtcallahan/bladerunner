<?php

use STS\HPSIM\HPSIMBlade;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassis;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMSwitch;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMBladeReservationTable;

$simBladeTable    = new HPSIMBladeTable();
$simChassisTable  = new HPSIMChassisTable();
$simSwitchTable   = new HPSIMSwitchTable();
$simBladeResTable = new HPSIMBladeReservationTable();

// get a list of blades
$blades = $simBladeTable->getInventory();

// get a list of blade reservations
$resHash = $simBladeResTable->getAllHashByBladeId();

$distSwitches = array();

/* List of chassis types from the productName column of the switch table*/
/*
    Cisco Catalyst Blade Switch 3020 for HP
    Cisco Catalyst Blade Switch 3120G for HP
    Cisco MDS 9124e 24-port Fabric Switch, HP c-Class BladeSystem
    HP 1/10Gb VC-Enet Module
    HP 1/10Gb-F VC-Enet Module
    HP 4Gb VC-FC Module
    HP ProCurve 6120XG Blade Switch
    HP VC Flex-10 Enet Module
    HP VC FlexFabric 10Gb/24-Port Module
    HP Virtual Connect 4Gb FC Module
    HP Virtual Connect 8GB 24-Port FC Module
    HP Virtual Connect 8Gb 24-Port FC Module.
*/

for ($i = 0; $i < count($blades); $i++) {
    $b = $blades[$i];

    // skip if this blade has a reservation on it
    if (array_key_exists($b->getId(), $resHash)) continue;

    // get the chassis to obtain the site and skip non-Core Hosting
    $ch = $simChassisTable->getById($b->getChassisId());

    // get the associated switch
    /** @var $sw HPSIMSwitch */
    /** @var $sw2 HPSIMSwitch */
    list($sw, $sw2) = $simSwitchTable->getByChassisId($ch->getId());

    // skip this chassis if there is no switch product name.
    // We need to be able to group by switch(chassis) type.
    if ($sw->getProductName() == "") continue;

    // Assign the chassis type by regexp of switch
    if (preg_match("/Flex Fabric/", $sw->getProductName())) {
        $chType = "Flex Fabric";
    } else if (preg_match("/Cisco/", $sw->getProductName())) {
        $chType = "Cisco";
    } else if (preg_match("/VC|Virtual Connect/", $sw->getProductName())) {
        $chType = "VC";
    } else if (preg_match("/ProCurve/", $sw->getProductName())) {
        $chType = "ProCurve";
    } else {
        $chType = "Unknown";
    }

    // regexp to assign site of Sterling or Charlotte
    $site = preg_match("/Charlotte/", $ch->getDistSwitchName()) ? "Charlotte" : "Sterling";

    // skip DECE, Mobile Cloud, Ultra & NT chassis
    if (preg_match("/(DECE|Mobile Cloud)/", $ch->getDistSwitchName())) continue;
    if (preg_match("/^(stnt|chnt|stul)/", $ch->getDeviceName())) continue;

    // construct the hash value by concating these 2 field values.
    // Use this has to count up blades
    $hashKey = $ch->getDistSwitchName() . $chType;

    // put into the structure
    if (!array_key_exists($hashKey, $distSwitches)) {
        $distSwitches[$hashKey] = (object)array(
            "quantity"    => 0,
            "distSwitch"  => $ch->getDistSwitchName(),
            "chassisType" => $chType
        );
    }
    $distSwitches[$hashKey]->quantity++;
}

// ok, now we can construct the grid by simply looping over the hash
$grid = array();
foreach ($distSwitches as $key => $o) {
    $grid[] = array(
        "quantity"    => $o->quantity,
        "distSwitch"  => $o->distSwitch,
        "chassisType" => $o->chassisType
    );
}

if (isset($export) && $export) {
    include "export_report_capmgmtchassistype.php";
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

