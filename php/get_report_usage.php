<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeReservationTable;
use STS\HPSIM\HPSIMChassisTable;

// start with the distribution switches
$simChassisTable = new HPSIMChassisTable();
$rows            = $simChassisTable->getNetworks();
$switches        = array();
$bladeResTable   = new HPSIMBladeReservationTable();

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

$lastSite       = null;
$lastSwitchName = null;
$siteRowIndex   = 0;
$swRowIndex     = 0;

$siteTotals = initTotals();

for ($i = 0; $i < count($switches); $i++) {
    $fullSwitchName = $switches[$i];

    $site       = preg_match("/Charlotte/", $fullSwitchName) ? "CH" : "ST";
    $switchName = preg_replace("/^(Sterling |Charlotte )/", "", $fullSwitchName);

    if ($i > 0) {
        // show the switch totals
        $grid[$swRowIndex] = assignRow("switch", "", $lastSwitchName, "", $swTotals);

        // show the site totals
        if ($site != $lastSite) {
            $grid[$siteRowIndex] = assignRow("site", $lastSite, "", "", $siteTotals);
        }

        // skip a row
        $grid[] = initRow();
    }

    // reset the switch total counts
    $swTotals = initTotals();

    // check if different site
    if ($site != $lastSite) {
        $x         = initRow();
        $x["type"] = "site";
        $x["site"] = $site;
        $grid[]    = $x;

        // reset the site total counts
        $siteRowIndex = count($grid) - 1;
        $siteTotals   = initTotals();
    }

    // row with switch name only
    $x           = initRow();
    $x["type"]   = "switch";
    $x["distSw"] = $switchName;
    $grid[]      = $x;
    $swRowIndex  = count($grid) - 1;

    $chassis = $simChassisTable->getBySwitchName($fullSwitchName);

    for ($j = 0; $j < count($chassis); $j++) {
        $ch = $chassis[$j];

        // skip the Ultra and NT chassis
        if (preg_match("/^(stnt|chnt|stul)/", $ch->getDeviceName())) continue;

        $hhCount = $simChassisTable->getHalfHeightBladeCount($ch->getId());
        $fhCount = $simChassisTable->getFullHeightBladeCount($ch->getId());

        $slotsPopulated = $hhCount + ($fhCount * 2);

        $slotsTotal = 16;   // total slots in the chassis
        $slotsProv  = 0;    // slots that have provisioned blades: HP SIM and CMDB both have FQDNs
        $slotsSpare = 0;    // slots that have spare blades: CMDB name has "spare" in it
        $slotsRes   = 0;    // slots that have reserved blades: marked as reserved in BladeRunner
        $slotsInv   = 0;    // slots that have inventory blades: CMDB name has "inventory" in it
        $slotsEmpty = $slotsTotal - $slotsPopulated;
        $slotsAvail = 0;    // sum of empty slots and slots with inventory blades installed

        $bladesInst  = $hhCount + ($fhCount * 2);
        $bladesProv  = 0;    // provisioned blades: HP SIM and CMDB both have FQDNs
        $bladesSpare = 0;    // spare blades: CMDB name has "spare" in it
        $bladesRes   = 0;    // reserved blades: marked as reserved in BladeRunner
        $bladesInv   = 0;    // inventory blades: CMDB name has "inventory" in it

        // get the list of blades for this chassis, need to look at their names
        $simBladeTable = new HPSIMBladeTable();
        $blades        = $simBladeTable->getByChassisId($ch->getId());
        for ($k = 0; $k < count($blades); $k++) {
            $b = $blades[$k];
            if ($b->getIsInventory()) {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $slotsInv += 2;
                    $bladesInv += 2;
                } else {
                    $slotsInv++;
                    $bladesInv++;
                }
            } else if ($b->getIsSpare()) {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $slotsSpare += 2;
                    $bladesSpare += 2;
                } else {
                    $slotsSpare++;
                    $bladesSpare++;
                }
            }

            $bladeRes = $bladeResTable->getOpenByBladeId($b->getId());
            if ($bladeRes && $bladeRes->getId() != "") {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $slotsRes += 2;
                    $bladesRes += 2;
                } else {
                    $slotsRes++;
                    $bladesRes++;
                }
            }

            // check if fullDnsName in HP SIM matches the name in CMDB
            if ($b->getFullDnsName() == $b->getCmdbName() && $b->getPowerStatus() == "On") {
                if (preg_match("/BL68.*G5/", $b->getProductName())) { // full height blade
                    $slotsProv += 2;
                    $bladesProv += 2;
                } else {
                    $slotsProv++;
                    $bladesProv++;
                }
            }
        }
        $slotsAvail = $slotsInv + $slotsEmpty;

        $grid[] = array(
            "type"        => "chassis",
            "site"        => "",
            "distSw"      => "",
            "chassis"     => $ch->getDeviceName(),
            "chassisId"   => $ch->getId(),

            "slotsTotal"  => $slotsTotal,
            "slotsProv"   => $slotsProv,
            "slotsSpare"  => $slotsSpare,
            "slotsRes"    => $slotsRes,
            "slotsInv"    => $slotsInv,
            "slotsEmpty"  => $slotsEmpty,
            "slotsAvail"  => $slotsAvail,

            "bladesInst"  => $bladesInst,
            "bladesProv"  => $bladesProv,
            "bladesSpare" => $bladesSpare,
            "bladesRes"   => $bladesRes,
            "bladesInv"   => $bladesInv
        );

        $swTotals = (object)array(
            "slotsTotal"  => $swTotals->slotsTotal + $slotsTotal,
            "slotsProv"   => $swTotals->slotsProv + $slotsProv,
            "slotsSpare"  => $swTotals->slotsSpare + $slotsSpare,
            "slotsRes"    => $swTotals->slotsRes + $slotsRes,
            "slotsInv"    => $swTotals->slotsInv + $slotsInv,
            "slotsEmpty"  => $swTotals->slotsEmpty + $slotsEmpty,
            "slotsAvail"  => $swTotals->slotsAvail + $slotsAvail,

            "bladesInst"  => $swTotals->bladesInst + $bladesInst,
            "bladesProv"  => $swTotals->bladesProv + $bladesProv,
            "bladesSpare" => $swTotals->bladesSpare + $bladesSpare,
            "bladesRes"   => $swTotals->bladesRes + $bladesRes,
            "bladesInv"   => $swTotals->bladesInv + $bladesInv
        );

        $siteTotals = (object)array(
            "slotsTotal"  => $siteTotals->slotsTotal + $slotsTotal,
            "slotsProv"   => $siteTotals->slotsProv + $slotsProv,
            "slotsSpare"  => $siteTotals->slotsSpare + $slotsSpare,
            "slotsRes"    => $siteTotals->slotsRes + $slotsRes,
            "slotsInv"    => $siteTotals->slotsInv + $slotsInv,
            "slotsEmpty"  => $siteTotals->slotsEmpty + $slotsEmpty,
            "slotsAvail"  => $siteTotals->slotsAvail + $slotsAvail,

            "bladesInst"  => $siteTotals->bladesInst + $bladesInst,
            "bladesProv"  => $siteTotals->bladesProv + $bladesProv,
            "bladesSpare" => $siteTotals->bladesSpare + $bladesSpare,
            "bladesRes"   => $siteTotals->bladesRes + $bladesRes,
            "bladesInv"   => $siteTotals->bladesInv + $bladesInv
        );
    }

    $lastSite       = $site;
    $lastSwitchName = $switchName;
}

// show the switch totals
$grid[$swRowIndex] = assignRow("switch", "", $lastSwitchName, "", $swTotals);

// show the site totals
$grid[$siteRowIndex] = assignRow("site", $site, "", "", $siteTotals);

// skip a row
$grid[] = initRow();

if (isset($export) && $export) {
    include "export_report_usage.php";
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

function assignRow($type, $site, $distSw, $chassis, $o) {
    return array(
        "type"        => $type,
        "site"        => $site,
        "distSw"      => $distSw,
        "chassis"     => $chassis,

        "slotsTotal"  => $o->slotsTotal,
        "slotsProv"   => $o->slotsProv,
        "slotsSpare"  => $o->slotsSpare,
        "slotsRes"    => $o->slotsRes,
        "slotsInv"    => $o->slotsInv,
        "slotsEmpty"  => $o->slotsEmpty,
        "slotsAvail"  => $o->slotsAvail,

        "bladesInst"  => $o->bladesInst,
        "bladesProv"  => $o->bladesProv,
        "bladesSpare" => $o->bladesSpare,
        "bladesRes"   => $o->bladesRes,
        "bladesInv"   => $o->bladesInv
    );
}

function initTotals() {
    return (object)array(
        "slotsTotal"  => 0,
        "slotsProv"   => 0,
        "slotsSpare"  => 0,
        "slotsRes"    => 0,
        "slotsInv"    => 0,
        "slotsEmpty"  => 0,
        "slotsAvail"  => 0,

        "bladesInst"  => 0,
        "bladesProv"  => 0,
        "bladesSpare" => 0,
        "bladesRes"   => 0,
        "bladesInv"   => 0
    );
}

function initRow() {
    return array(
        "type"        => "blank",
        "site"        => "",
        "distSw"      => "",
        "chassis"     => "",
        "chassisId"   => "",

        "slotsTotal"  => "",
        "slotsProv"   => "",
        "slotsSpare"  => "",
        "slotsRes"    => "",
        "slotsInv"    => "",
        "slotsEmpty"  => "",
        "slotsAvail"  => "",

        "bladesInst"  => "",
        "bladesProv"  => "",
        "bladesSpare" => "",
        "bladesRes"   => "",
        "bladesInv"   => ""
    );
}

