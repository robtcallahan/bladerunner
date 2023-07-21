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
$sanOrder = array(
    "ST Corp SAN" => array(),
    "CH Corp SAN" => array(),
    "ST NPAC SAN" => array(),
    "CH NPAC SAN" => array(),
    "DN Corp SAN" => array(),
    "SD NIS SAN"  => array(),
    "NY NIS SAN"  => array(),
    "ST EA"       => array()
);

// loop over each storage array, perform calculations and assing to sanOrder structure
foreach ($arrays as $array) {
    if ($array->getModel() == 'SVC') continue;

    if (!array_key_exists($array->getSanName(), $sanOrder)) {
        throw new \ErrorException("Unknown SAN name found in array: " . $array->getSanName());
    }

    // get the array capacity amounts from the storage pool table
    $arrayCap = $ssPoolTable->getCapacityByArrayId($array->getId());

    // calculate % provisioned
    $percentProvisioned = round($arrayCap->totalProvisionedTb / $arrayCap->totalUseableTb * 100, 2);

    // first check to see if we need to change any Open status values to Closed
    // set to Closed if % provisioned >= 90%
    if ($array->getStatus() == "Open" && $percentProvisioned >= 90) {
        $array->setStatus("Closed");
        $array = $ssArrayTable->update($array);
    }

    if ($array->getStatus() == "TR" || $array->getStatus() == "Closed") {
        $availableAt90 = 0.00;
    } else {
        $availableAt90 = round(($arrayCap->totalUseableTb * 0.9) - $arrayCap->totalProvisionedTb, 2);
    }
    $sanOrder[$array->getSanName()][] = array(
        "sanName"            => $array->getSanName(),
        "arrayName"          => $array->getName(),
        "arrayModel"         => $array->getModel(),
        "tier"               => $array->getTier(),
        "status"             => $array->getStatus(),
        "rawTb"              => round($arrayCap->totalRawTb, 2),
        "useableTb"          => round($arrayCap->totalUseableTb, 2),
        "provisionedTb"      => round($arrayCap->totalProvisionedTb, 2),
        "availableTb"        => round($arrayCap->totalAvailableTb, 2),
        "percentProvisioned" => $percentProvisioned,
        "availableAt90"      => $availableAt90,
        "divider"            => false
    );
}

// ok, now we can construct the grid by simply looping over the hash
$grid        = array();
$lastSanName = "";
$firstTime   = true;
foreach ($sanOrder as $sanName => $arrays) {
    for ($i = 0; $i < count($arrays); $i++) {
        if ($sanName != $lastSanName && !$firstTime) {
            // write a copy of the current row with divider set to true
            // we'll gray out the cells on the JavaScript side to make a visual divider
            // the data values don't matter
            $arrays[$i]['divider'] = true;
            $grid[]                = $arrays[$i];
            // set divider back to false since we're going to write out the actual data now
            $arrays[$i]['divider'] = false;
        }
        $grid[] = $arrays[$i];

        $lastSanName = $sanName;
        $firstTime   = false;
    }
}

if (isset($export) && $export) {
    include "export_report_storageutilbyarray.php";
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

