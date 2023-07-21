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

// going to create a hash by model, cores, cpus and mem so we can count unique types
$dataHash = array();

// loop over each
for ($i = 0; $i < count($blades); $i++) {
    $b = $blades[$i];

    // skip if this blade has a reservation on it
    if (array_key_exists($b->getId(), $resHash)) continue;

    // get the chassis to obtain the site and skip non-Core Hosting
    $ch = $simChassisTable->getById($b->getChassisId());
    if (preg_match("/^(\w+)\s/", $ch->getDistSwitchName(), $m)) {
        $site = $m[1];
    } else {
        $site = 'unknown';
        error_log("distSwitchName=" . $ch->getDistSwitchName());
    }

    // skip DECE, Mobile Cloud, Ultra & NT chassis
    if (preg_match("/(DECE|Mobile Cloud)/", $ch->getDistSwitchName())) continue;
    if (preg_match("/^(stnt|chnt|stul)/", $ch->getDeviceName())) continue;

    if ($b->getProductName() == "") continue;

    // construct the hash value by concating these 3 field values
    $hashKey = preg_replace("/ProLiant/", "", $b->getProductName()) . $b->getNumCoresPerCpu() . $b->getNumCpus() . $b->getMemorySizeGB();

    // model color: G1,G5 in red
    if (preg_match("/G1|G5/", $b->getProductName())) {
        $modelCss = "br-grid-cell-error";
    } else {
        $modelCss = "";
    }

    /*
     * standard configs color:
     *  Gen8, 2x6, 128
     *  Gen8, 2x6, 96
     *  G7, 2x6, 96
     */
    $shortModel   = substr($b->getProductName(), strpos($b->getProductName(), 'G'));
    $configString = $shortModel . "," . $b->getNumCpus() . "x" . $b->getNumCoresPerCpu() . "," . $b->getMemorySizeGB();
    if ($configString == 'Gen8,2x6,128' || $configString == 'Gen8,2x6,96' || $configString == 'G7,2x6,96') {
        $configCss = "br-grid-cell-ok";
    } else {
        $configCss = "";
    }

    // put into the structure
    if (!array_key_exists($hashKey, $dataHash)) {
        $dataHash[$hashKey] = (object)array(
            "quantity"   => 0,
            "site"       => $site,
            "distSwitch" => $ch->getDistSwitchName(),
            "chassis"    => $ch->getDeviceName(),
            "model"      => $b->getProductName(),
            "shortModel" => $shortModel,
            "numCores"   => $b->getNumCoresPerCpu(),
            "numCpus"    => $b->getNumCpus(),
            "memGB"      => $b->getMemorySizeGB(),
            "modelCss"   => $modelCss,
            "configCss"  => $configCss
        );
    }
    $dataHash[$hashKey]->quantity++;
}

// ok, now we can construct the grid by simply looping over the hash
$grid = array();
foreach ($dataHash as $key => $o) {
    $cpuString = "{$o->numCpus} X {$o->numCores}";

    $grid[] = array(
        "quantity"   => $o->quantity,
        "model"      => $o->model,
        "shortModel" => $o->shortModel,
        "cpu"        => $cpuString,
        "memGB"      => $o->memGB,
        "site"       => $o->site,
        "divider"    => false,
        "modelCss"   => $o->modelCss,
        "configCss"  => $o->configCss
    );
}

/**
 * sorting function as per request:
 *  Model (Z to A)
 *  CPU (Z to A)
 *  Memory (Largest to smallest)
 *  Site (Z to A)
 */
function sortBlades($a, $b) {
    $cmp = strcmp($b['shortModel'], $a['shortModel']);
    if ($cmp != 0) {
        return $cmp;
    } else {
        $cmp = strcmp($b['cpu'], $a['cpu']);
        if ($cmp != 0) {
            return $cmp;
        } else {
            $cmp = $b['memGB'] - $a['memGB'];
            if ($cmp != 0) {
                return $cmp;
            } else {
                return strcmp($b['site'], $a['site']);
            }
        }
    }
}

// call the sort
usort($grid, 'sortBlades');

// add the divider flag
$firstTime = true;
$lastCpu   = "";
$lastModel = "";
$gridMod   = array();
for ($i = 0; $i < count($grid); $i++) {
    if (!$firstTime && ($grid[$i]['shortModel'] != $lastModel || $grid[$i]['cpu'] != $lastCpu)) {
        $gridMod[] = array(
            "quantity"   => "",
            "model"      => "",
            "shortModel" => "",
            "cpu"        => "",
            "memGB"      => "",
            "site"       => "",
            "divider"    => true,
            "modelCss"   => "",
            "configCss"  => ""
        );
    }
    $gridMod[] = $grid[$i];

    $firstTime = false;
    $lastModel = $grid[$i]['shortModel'];
    $lastCpu   = $grid[$i]['cpu'];
}
$grid = $gridMod;

if (isset($export) && $export) {
    include "export_report_bladeInventoryRollup.php";
} else {
    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "total"      => count($gridMod),
            "grid"       => $gridMod
        )
    );
}

