<?php

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenStoragePoolTable;

$ss           = new SANScreen();
$ssArrayTable = new SANScreenArrayTable();
// I love this variable name
$ssPoolTable = new SANScreenStoragePoolTable();

// get all the arrays
$arrays = $ssArrayTable->getAllOrderBySan();

// initialize a structure to display rows in particular order and cannot be sorted in the ususal way
// each san will contain an array of storage arrays
$sans  = array(
    "ST Corp SAN",
    "CH Corp SAN",
    "ST NPAC SAN",
    "CH NPAC SAN",
    "DN Corp SAN",
    "SD NIS SAN",
    "NY NIS SAN",
    "ST EA"
);
$tiers = array(
    "Tier 1",
    "Tier 2",
    "Tier 3",
);

// create a rollup structure so that we can sum the amounts
$rollup = array();
foreach ($sans as $sanName) {
    $rollup[$sanName] = array();
    foreach ($tiers as $tier) {
        $rollup[$sanName][$tier] = array(
            "useableTb"      => 0,
            "provisionedTb"  => 0,
            "availableAt100" => 0
        );
    }
}

// loop over each storage array, perform calculations and assing to sanOrder structure
foreach ($arrays as $array) {
    if ($array->getModel() == 'SVC') continue;

    if (!in_array($array->getSanName(), $sans)) {
        throw new \ErrorException("Unknown SAN name found in array: " . $array->getSanName());
    }

    // get the array capacity amounts from the storage pool table
    $arrayCap = $ssPoolTable->getCapacityByArrayId($array->getId());

    // calculate % provisioned
    $percentProvisioned = $arrayCap->totalProvisionedTb / $arrayCap->totalUseableTb;

    // first check to see if we need to change any Open status values to Closed
    // set to Closed if % provisioned >= 90%
    if ($array->getStatus() == "Open" && $percentProvisioned >= 90) {
        $array->setStatus("Closed");
        $array = $ssArrayTable->update($array);
    }

    // need to exclude arrays that are not Open - for now
    if ($array->getStatus() !== "Open") continue;

    $availableAt100 = $arrayCap->totalUseableTb - $arrayCap->totalProvisionedTb;

    $rollup[$array->getSanName()][$array->getTier()]["useableTb"] += $arrayCap->totalUseableTb;
    $rollup[$array->getSanName()][$array->getTier()]["provisionedTb"] += $arrayCap->totalProvisionedTb;
    $rollup[$array->getSanName()][$array->getTier()]["availableAt100"] += $availableAt100;
}


// ok, now we can construct the grid by simply looping over the hash
$grid        = array();
$lastSanName = "";
$firstTime   = true;
foreach ($rollup as $sanName => $tiers) {
    if ($sanName != $lastSanName) {
        if (!$firstTime) {
            $grid[] = array(
                "sanOrTier"          => "",
                "useableTb"          => "",
                "provisionedTb"      => "",
                "availableAt100"     => "",
                "percentProvisioned" => "",
                "divider"            => true
            );
        }
        $grid[]    = array(
            "sanOrTier"          => $sanName,
            "useableTb"          => "",
            "provisionedTb"      => "",
            "availableAt100"     => "",
            "percentProvisioned" => "",
            "divider"            => false
        );
        $firstTime = false;
    }

    foreach (array_keys($tiers) as $tier) {
        // make sure there's data in this here tier
        if ($rollup[$sanName][$tier]["useableTb"]) {
            // calculate % provisioned
            $percentProvisioned = $rollup[$sanName][$tier]["provisionedTb"] / $rollup[$sanName][$tier]["useableTb"] * 100;
            $grid[]             = array(
                "sanOrTier"          => $tier,
                "useableTb"          => round($rollup[$sanName][$tier]["useableTb"], 2),
                "provisionedTb"      => round($rollup[$sanName][$tier]["provisionedTb"], 2),
                "availableAt100"     => round($rollup[$sanName][$tier]["availableAt100"], 2),
                "percentProvisioned" => round($percentProvisioned, 2),
                "divider"            => false
            );
        }

        $lastSanName = $sanName;
        $firstTime   = false;
    }
}

if (isset($export) && $export) {
    include "export_report_storageutilbysan.php";
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

