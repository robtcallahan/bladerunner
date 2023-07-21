<?php

use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMVMTable;
use STS\HPSIM\HPSIM;

const TOTALVMCAPACITY = 40;

try {
    $simChassisTable = new HPSIMChassisTable();
    $simBladeTable   = new HPSIMBladeTable();
    $simVMTable      = new HPSIMVMTable();
    $hpsim           = new HPSIM();

    $results = $simBladeTable->getAll();

    /**
     * @var $a \STS\HPSIM\HPSIMBlade
     * @var $b \STS\HPSIM\HPSIMBlade
     */
    function sortByClusterName($a, $b) {
        $cmp = strcmp($a->getCcrName(), $b->getCcrName());
        if ($cmp != 0) return $cmp;
        $cmp = strcmp($a->get('chassisName'), $b->get('chassisName'));
        if ($cmp != 0) return $cmp;
        return strcmp($a->getDeviceName(), $b->getDeviceName());
    }

    $blades = array();
    foreach ($results as $blade) {
        // skip non esx blades and those that have been decommed or disposed
        if (!preg_match("/esx/", $blade->getDeviceName()) || $blade->getCmInstallStatus() == "Decommissioning" || $blade->getCmInstallStatus() == "Disposed") continue;

        $chassis = $simChassisTable->getById($blade->getChassisId());
        $slotsAvailable = $hpsim->getChassisSlotsAvailable($chassis->getId());
        $blade->set('slotsAvailable', $slotsAvailable);
        $blade->set('chassisId', $chassis->getId());
        $blade->set('chassisName', $chassis->getDeviceName());
        $blade->set('distSwitch', $chassis->getDistSwitchName());

        if (preg_match("/ProLiant BL460c (G.*)/", $blade->getProductName(), $m)) {
            $productName = $m[1];
        } else {
            $productName = $blade->getProductName();
        }

        $blade->set('cpuTotalMHz', $blade->getNumCpus() * $blade->getNumCoresPerCpu() * $blade->getCpuSpeedMHz());
        // get the esx blades VMs
        $vms = $simVMTable->getByBladeId($blade->getId());

        // now loop over our VMs to count mem
        $memProvGB  = 0;
        $memUtilGB  = 0;
        $cpuProv    = 0;
        $cpuUtilMHz = 0;
        foreach ($vms as $vm) {
            $memProvGB += $vm->getMemorySize() ? $vm->getMemorySize() : 0;
            $memUtilGB += $vm->getGuestMemUsageMB() ? $vm->getGuestMemUsageMB() / 1024 : 0;
            $cpuProv += $vm->getNumberOfCpus() ? $vm->getNumberOfCpus() : 0;
            $cpuUtilMHz += $vm->getOverallCpuUsageMHz() ? $vm->getOverallCpuUsageMHz() : 0;
        }
        $blade->set('numVMs', count($vms));
        $blade->set('memProvGB', $memProvGB);

        #$blade->set('memUtilGB', $memUtilGB);
        $blade->set('memUtilGB', floatval($blade->getOverallMemoryUsageGB()));
        $blade->set('memUtilPct', floatval($blade->getMemoryUsagePercent()));

        $blade->set('cpuProv', $cpuProv);
        $blade->set('cpuUtilMHz', $cpuUtilMHz);

        $blade->set('numCPUs', $blade->getNumCpus() * $blade->getNumCoresPerCpu());

        // calculate CPU utilization: have MHz and want to convert to real number of CPUs utilized
        // ==> overallCpuUsageMHz / (numCPUs(= num physical CPUs * CoresPerCPU) * CPUSpeedMHz) => percentage; => % num CPUs
        if ($blade->get('numCPUs') * $blade->getCpuSpeedMHz() != 0) {
            #$blade->set('cpuUtil', $blade->get('numCPUs') * $blade->getCpuSpeedMHz() != 0 ? round($blade->get('cpuUtilMHz') / ($blade->get('numCPUs') * $blade->getCpuSpeedMHz()), 2) : 0);
            $cpuUtil = $blade->get('numCPUs') * ($blade->getOverallCpuUsage() / ($blade->get('numCPUs') * $blade->getCpuSpeedMHz()));
            $blade->set('cpuUtil', round($cpuUtil,2));
        } else {
            $blade->set('cpuUtil', -1);
        }
        $blade->set('cpuUtilPct', $blade->getCpuUsagePercent());

        $blades[] = $blade;
    }
    usort($blades, 'sortByClusterName');

    $grid = array();

    /*
     * Columns:
     *
     * Dist Switch Name, Chassis Name, Cluster Name, Hyper Name, Hyper Model,
     * Total Physical Memory Capacity (GB), Total Mem Provisioned (GB), Avail Physical Mem(GB),
     * Total Mem Util (GB), Total Mem Util (Pct), Total Mem UnUtil,
     * Total VMs Provisioned, Total VM capacity(always 40), Total VMs available,
     * Total Physical CPU Capacity, Total CPU Provisioned, Available Physical CPUs,
     * Total CPU Util, Total CPU Util (Pct), Total CPU UnUtil,
     * Slots available
     */
    $lastClusterName = "";
    $lastChassisName = "";
    $firstTime       = true;

    $clusterTotals = array(
        "memTotalGB"      => 0,
        "memProvGB"       => 0,
        "memAvailGB"      => 0,
        "memUtilizedGB"   => 0,
        "memUtilizedPct"  => 0,
        "memUnutilizedGB" => 0,
        "vmsTotal"        => 0,
        "vmsCapacity"     => 0,
        "vmsAvailable"    => 0,
        "cpuCapacity"     => 0,
        "cpuProv"         => 0,
        "cpuAvail"        => 0,
        "cpuUtilized"     => 0,
        "cpuUtilizedPct"  => 0,
        "cpuUnutilized"   => 0,
        "slotsAvailable"  => 0
    );

    $row = 0;
    /** @var HPSIMBlade $blade */
    foreach ($blades as $blade) {
        if ($blade->get('ccrName') != $lastClusterName && !$firstTime) {
            // print out totals for this cluster
            $grid[$row] = array(
                "clusterName"     => "",
                "distSwitch"      => "",
                "chassisName"     => "",
                "hyperName"       => "",
                "hyperModel"      => "",
                "memTotalGB"      => $clusterTotals['memTotalGB'],
                "memProvGB"       => $clusterTotals['memProvGB'],
                "memAvailGB"      => $clusterTotals['memAvailGB'],
                "memUtilizedGB"   => $clusterTotals['memUtilizedGB'],
                "memUtilizedPct"  => $clusterTotals['memUtilizedPct'],
                "memUnutilizedGB" => $clusterTotals['memUnutilizedGB'],
                "vmsTotal"        => $clusterTotals['vmsTotal'],
                "vmsCapacity"     => $clusterTotals['vmsCapacity'],
                "vmsAvailable"    => $clusterTotals['vmsAvailable'],
                "cpuCapacity"     => $clusterTotals['cpuCapacity'],
                "cpuProv"         => $clusterTotals['cpuProv'],
                "cpuAvail"        => $clusterTotals['cpuAvail'],
                "cpuUtilized"     => $clusterTotals['cpuUtilized'],
                "cpuUtilizedPct"  => $clusterTotals['cpuUtilizedPct'],
                "cpuUnutilized"   => $clusterTotals['cpuUnutilized'],
                "slotsAvailable"  => $clusterTotals['slotsAvailable'],
                "divider"         => false,
                "clusterSubtotal" => true,
            );
            $row++;
            // reset counters
            $clusterTotals = array(
                "memTotalGB"      => 0,
                "memProvGB"       => 0,
                "memAvailGB"      => 0,
                "memUtilizedGB"   => 0,
                "memUtilizedPct"  => 0,
                "memUnutilizedGB" => 0,
                "vmsTotal"        => 0,
                "vmsCapacity"     => 0,
                "vmsAvailable"    => 0,
                "cpuCapacity"     => 0,
                "cpuProv"         => 0,
                "cpuAvail"        => 0,
                "cpuUtilized"     => 0,
                "cpuUtilizedPct"  => 0,
                "cpuUnutilized"   => 0,
                "slotsAvailable"  => 0
            );
            // add a divider line
            $grid[$row] = array("divider" => true);
            $row++;
        }

        // print out the row
        $grid[$row] = array(
            "clusterName"     => $blade->getCcrName() != $lastClusterName ? $blade->getCcrName() : "",
            "distSwitch"      => $blade->getCcrName() != $lastClusterName || $blade->getCcrName() == "" ? $blade->get('distSwitch') : "",
            "chassisName"     => $blade->get('chassisName') != $lastChassisName ? $blade->get('chassisName') : "",
            "hyperName"       => $blade->getDeviceName(),
            "hyperModel"      => $productName,

            "memTotalGB"      => intval($blade->getMemorySizeGB()),
            "memProvGB"       => $blade->get('memProvGB'),
            "memAvailGB"      => $blade->getMemorySizeGB() - $blade->get('memProvGB'),

            "memUtilizedGB"   => round($blade->get('memUtilGB'), 2),
            "memUtilizedPct"  => intval($blade->getMemoryUsagePercent()),
            "memUnutilizedGB" => round($blade->getMemorySizeGB() - $blade->get('memUtilGB'), 2),

            "vmsTotal"        => $blade->get('numVMs'),
            "vmsCapacity"     => TOTALVMCAPACITY,
            "vmsAvailable"    => TOTALVMCAPACITY - $blade->get('numVMs'),

            "cpuCapacity"     => $blade->get('numCPUs'),
            "cpuProv"         => $blade->get('cpuProv'),
            "cpuAvail"        => $blade->get('numCPUs') - $blade->get('cpuProv'),

            "cpuUtilized"     => floatval($blade->get('cpuUtil')),
            "cpuUtilizedPct"  => intval($blade->get('cpuUtilPct')),
            "cpuUnutilized"   => floatval(round($blade->get('numCPUs') - $blade->get('cpuUtil'), 2)),

            "slotsAvailable"  => $blade->get('chassisName') != $lastChassisName ? $blade->get('slotsAvailable') : "",

            "chassisId"       => intval($blade->get('chassisId')),
            "bladeId"         => intval($blade->getId()),

            "divider"         => false,
            "blankIntFields"  => true
        );

        // add to our cluster subtotals
        $clusterTotals['memTotalGB'] += $grid[$row]['memTotalGB'];
        $clusterTotals['memProvGB'] += $grid[$row]['memProvGB'];
        $clusterTotals['memAvailGB'] += $grid[$row]['memAvailGB'];
        $clusterTotals['memUtilizedGB'] += $grid[$row]['memUtilizedGB'];
        $clusterTotals['memUtilizedPct'] += $grid[$row]['memUtilizedPct'];
        $clusterTotals['memUnutilizedGB'] += $grid[$row]['memUnutilizedGB'];
        $clusterTotals['vmsTotal'] += $grid[$row]['vmsTotal'];
        $clusterTotals['vmsCapacity'] += $grid[$row]['vmsCapacity'];
        $clusterTotals['vmsAvailable'] += $grid[$row]['vmsAvailable'];
        $clusterTotals['cpuCapacity'] += $grid[$row]['cpuCapacity'];
        $clusterTotals['cpuProv'] += $grid[$row]['cpuProv'];
        $clusterTotals['cpuAvail'] += $grid[$row]['cpuAvail'];
        $clusterTotals['cpuUtilized'] += $grid[$row]['cpuUtilized'];
        $clusterTotals['cpuUtilizedPct'] += $grid[$row]['cpuUtilizedPct'];
        $clusterTotals['cpuUnutilized'] += $grid[$row]['cpuUnutilized'];
        $clusterTotals['slotsAvailable'] += $grid[$row]['slotsAvailable'];
        $row++;

        $lastChassisName = $blade->get('chassisName');
        $lastClusterName = $blade->getCcrName();
        $firstTime       = false;
    }

    if (isset($export) && $export) {
        include "export_report_esxhypervisors.php";
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
