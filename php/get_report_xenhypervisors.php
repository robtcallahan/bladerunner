<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMVMTable;

try {
    $simChassisTable = new HPSIMChassisTable();
    $simBladeTable   = new HPSIMBladeTable();
    $simVMTable      = new HPSIMVMTable();

    $rows         = $simChassisTable->getNetworks();
    $distSwitches = array();

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
        $distSwitches[] = $swName;
    }

    $grid = array();

    $lastDistSwitch = "";
    $firstTime      = true;
    $totalVMs       = 0;
    foreach ($distSwitches as $distSwitch) {
        $chassises = $simChassisTable->getBySwitchName($distSwitch);

        $lastChassisName = "";
        foreach ($chassises as $chassis) {
            $blades = $simBladeTable->getByChassisId($chassis->getId(), "slotNumber", "asc");

            $lastBladeName = "";
            foreach ($blades as $blade) {
                // skip decommed or disposed vms
                if ($blade->getCmInstallStatus() == "Decommissioning" || $blade->getCmInstallStatus() == "Disposed") continue;

                // if this is not a hypervisor ('xm' or 'kvm' in the name), then just move along
                if (preg_match("/kvm|xm/", $blade->getDeviceName())) {
                    $bsList = array();
                    $first        = true;
                    $vmsString    = "";
                    $totalVMMemGB = 0;
                    $vms = $simVMTable->getByBladeId($blade->getId());
                    foreach ($vms as $vm) {
                        if (!$first) $vmsString .= ", ";
                        $vmMemGB = $vm->getMemorySize() >= 1024 ? $vm->getMemorySize() / 1024 : $vm->getMemorySize();
                        $totalVMMemGB += $vmMemGB;
                        $vmsString .= $vm->getDeviceName() . " (" . $vmMemGB . ")";
                        if ($vm->getBusinessService()) $bsList[] = $vm->getBusinessService();
                        $first = false;
                    }
                    $hyperMemGBTotal = $blade->getMemorySizeGB() > 1024 ? $blade->getMemorySizeGB() / 1024 / 1024 : $blade->getMemorySizeGB();

                    // remove duplicates from the BS list
                    $businessServices = array_unique($bsList);
                    sort($businessServices);

                    // now loop over our business services to create the grid
                    foreach ($businessServices as $bsName) {
                        if ($distSwitch != $lastDistSwitch && !$firstTime) {
                            $grid[] = array("divider" => true);
                        }

                        if ($blade->getDeviceName() != $lastBladeName) {
                            $grid[] = array(
                                "distSwitch"      => $distSwitch != $lastDistSwitch ? $distSwitch : "",
                                "chassisId"       => $chassis->getDeviceName() != $lastChassisName ? $chassis->getId() : 0,
                                "chassisName"     => $chassis->getDeviceName() != $lastChassisName ? $chassis->getDeviceName() : "",
                                "bladeId"         => $blade->getId(),
                                "hyperName"       => $blade->getDeviceName(),
                                "hyperModel"      => $blade->getProductName(),
                                "businessService" => $bsName,
                                "hyperMemGBTotal" => $hyperMemGBTotal,
                                "hyperMemGBFree"  => strval(intval($hyperMemGBTotal) - intval($totalVMMemGB)),
                                "totalVMs"        => strval(count($vms)),
                                "vms"             => $vmsString,
                                "divider"         => false,
                                "blankIntFields"  => false
                            );
                            $totalVMs += count($vms);
                        } else {
                            $grid[] = array(
                                "distSwitch"      => $distSwitch != $lastDistSwitch ? $distSwitch : "",
                                "chassisId"       => $chassis->getDeviceName() != $lastChassisName ? $chassis->getId() : 0,
                                "chassisName"     => $chassis->getDeviceName() != $lastChassisName ? $chassis->getDeviceName() : "",
                                "bladeId"         => 0,
                                "hyperName"       => "",
                                "hyperModel"      => "",
                                "businessService" => $bsName,
                                "hyperMemGBTotal" => "",
                                "hyperMemGBFree"  => "",
                                "totalVMs"        => "",
                                "vms"             => "",
                                "divider"         => false,
                                "blankIntFields"  => true
                            );
                        }
                        $lastBladeName   = $blade->getDeviceName();
                        $lastChassisName = $chassis->getDeviceName();
                        $lastDistSwitch  = $distSwitch;
                        $firstTime       = false;
                    }
                }
            }
        }
    }

    // add a final row to show the total VMs count
    $grid[] = array(
        "distSwitch"      => "Total VMs",
        "chassisId"       => 0,
        "chassisName"     => "-",
        "bladeId"         => 0,
        "hyperName"       => "-",
        "hyperModel"      => "-",
        "businessService" => "-",
        "hyperMemGBTotal" => "",
        "hyperMemGBFree"  => "",
        "totalVMs"        => number_format($totalVMs),
        "vms"             => "-",
        "divider"         => false,
        "blankIntFields"  => true
    );


    if (isset($export) && $export) {
        include "export_report_xenhypervisors.php";
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
