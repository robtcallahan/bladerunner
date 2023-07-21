<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeReservationTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMSwitchTable;

// start with the distribution switches
$simChassisTable = new HPSIMChassisTable();
$simSwitchTable  = new HPSIMSwitchTable();
$bladeResTable   = new HPSIMBladeReservationTable();

$rows     = $simChassisTable->getNetworks();
$switches = array();

// requested to show sterling first then charlotte; here's my attempt at that
$list = "Sterling";
$i    = -1;
while (1) {
    $i++;
    if ($i >= count($rows)) {
        if ($list == "Charlotte") break;
        $i    = 0;
        $list = "Charlotte";
    }
    $r      = $rows[$i];
    $swName = $r->distSwitchName;
    if (!preg_match("/^{$list}/", $swName)) continue;

    // skip DECE & Mobile Cloud dist switches
    if (preg_match("/(DECE|Mobile Cloud)/", $swName)) continue;

    $switches[] = $swName;
}

// now get all the chassis for each switch and build the grid rows that will be returned
$grid = array();
// reset the switch total counts
$swTotals       = initTotals();
$lastSwitchName = null;

for ($i = 0; $i < count($switches); $i++) {
    $fullSwitchName = $switches[$i];
    $switchName     = preg_replace("/^(Sterling |Charlotte )/", "", $fullSwitchName);

    if ($switchName != $lastSwitchName) {
        if ($i > 0) {
            // show the switch totals and skip a row
            $grid[] = assignRow("total", $swTotals);
            $grid[] = initRow();

            // reset the switch total counts
            $swTotals = initTotals();
        }
        // show the switch name and skip a row
        $row           = initRow();
        $row['type']   = "switch";
        $row['distSw'] = $switchName;
        $grid[]        = $row;
    }

    $chassis = $simChassisTable->getBySwitchName($fullSwitchName);

    for ($j = 0; $j < count($chassis); $j++) {
        $ch = $chassis[$j];

        // skip the Ultra and NT chassis
        if (preg_match("/^(stnt|chnt|stul)/", $ch->getDeviceName())) continue;

        // get the active switch. need this to define the "chassis type" which is based upon the switch type
        $chassisSwitches = $simSwitchTable->getByChassisId($ch->getId());
        $swProductName   = $chassisSwitches[0]->getProductName();
        if (preg_match("/Cisco/", $swProductName)) {
            $chassisType = "Cisco";
        } else if (preg_match("/Flex/", $swProductName)) {
            $chassisType = "Flex Fabric";
        } else if (preg_match("/VC|Virtual Connect/", $swProductName)) {
            $chassisType = "VC";
        } else if (preg_match("/ProCurve/", $swProductName)) {
            $chassisType = "ProCurve";
        } else {
            $chassisType = $swProductName;
        }


        // get half-height (hh) and full-height (fh) blade counts
        $hhCount = $simChassisTable->getHalfHeightBladeCount($ch->getId());
        $fhCount = $simChassisTable->getFullHeightBladeCount($ch->getId());

        // populated slots
        $slotsPopulated = $hhCount + ($fhCount * 2);

        $total     = 16; // total slots in the chassis
        $spare     = 0; // slots that have spare blades: CMDB name has "spare" in it
        $empty     = $total - $slotsPopulated;
        $reserved  = 0; // reserved blades: marked as reserved in BladeRunner
        $inventory = 0; // inventory blades: CMDB name has "inventory" in it

        // get the list of blades for this chassis, need to look at their names
        $simBladeTable = new HPSIMBladeTable();
        $blades        = $simBladeTable->getByChassisId($ch->getId());
        $bladeTypeHash = array();
        for ($k = 0; $k < count($blades); $k++) {
            $b = $blades[$k];

            if (preg_match("/(G.*)$/", $b->getProductName(), $m)) {
                // remove "en" from Gen8 so that it's just G8
                $t                 = preg_replace("/en/", '', $m[1]);
                $bladeTypeHash[$t] = 1;
            } else {
                $bladeTypeHash['G?'] = 1;
            }

            if ($b->getIsInventory()) {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $inventory += 2;
                } else {
                    $inventory++;
                }
            } else if ($b->getIsSpare()) {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $spare += 2;
                } else {
                    $spare++;
                }
            }

            $bladeRes = $bladeResTable->getOpenByBladeId($b->getId());
            if ($bladeRes && $bladeRes->getId() != "") {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $reserved += 2;
                } else {
                    $reserved++;
                }
            }
        }

        // conver the blade type hash to an array, sort it and then implode back to a comma-separated string
        $keys = array_keys($bladeTypeHash);
        sort($keys);
        $bladeTypes = implode(',', $keys);


        $grid[] = array(
            "type"        => "chassis",
            "site"        => "",
            "distSw"      => "",
            "chassis"     => $ch->getDeviceName(),
            "chassisId"   => $ch->getId(),
            "chassisType" => $chassisType,
            "bladeTypes"  => $bladeTypes,

            "total"       => $total,
            "spare"       => $spare,
            "empty"       => $empty,
            "inventory"   => $inventory,
            "reserved"    => $reserved
        );

        $swTotals = (object)array(
            "total"     => $swTotals->total + $total,
            "spare"     => $swTotals->spare + $spare,
            "empty"     => $swTotals->empty + $empty,
            "inventory" => $swTotals->inventory + $inventory,
            "reserved"  => $swTotals->reserved + $reserved
        );
    }

    $lastSwitchName = $switchName;
}

$grid[] = assignRow("total", $swTotals);

if (isset($export) && $export) {
    include "export_report_capacity.php";
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

function assignRow($type, $o) {
    return array(
        "type"        => $type,
        "site"        => "",
        "distSw"      => "",
        "chassis"     => "",
        "chassisId"   => "",
        "chassisType" => "",
        "bladeTypes"  => "",
        "total"       => $o->total,
        "spare"       => $o->spare,
        "empty"       => $o->empty,
        "inventory"   => $o->inventory,
        "reserved"    => $o->reserved
    );
}

function initTotals() {
    return (object)array(
        "total"     => 0,
        "spare"     => 0,
        "empty"     => 0,
        "inventory" => 0,
        "reserved"  => 0
    );
}

function initRow() {
    return array(
        "type"        => "blank",
        "site"        => "",
        "distSw"      => "",
        "chassis"     => "",
        "chassisId"   => "",
        "chassisType" => "",

        "bladeTypes"  => "",

        "total"       => "",
        "spare"       => "",
        "empty"       => "",
        "inventory"   => "",
        "reserved"    => ""
    );
}

