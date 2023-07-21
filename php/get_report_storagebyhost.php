<?php

use STS\SANScreen\SANScreenSnapshotListTable;
use STS\SANScreen\SANScreenHostSnapshotTable;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    // get the from and to dates from the snapshot_list table
    $snapListTable = new SANScreenSnapshotListTable();
    $snapList      = $snapListTable->getAll();

    /** @var $snapListTo SanScreenSnapshotList */
    $snapListTo = $snapList[0];
    /** @var $snapListFrom SanScreenSnapshotList */
    $snapListFrom = $snapList[1];

    $dateTo   = $snapListTo->getDateStamp();
    $dateFrom = $snapListFrom->getDateStamp();

    $snapTable = new SANScreenHostSnapshotTable();
    $snapFrom  = $snapTable->getByDate($dateFrom);

    $grid = array();
    for ($i = 0; $i < count($snapFrom); $i++) {
        /** @var $from SanScreenSnapshot */
        $from = $snapFrom[$i];

        $to = $snapTable->getByDateAndHostAndArray($dateTo, $from->getHostName(), $from->getArrayName());
        if ($to->getAllocatedGb() != $from->getAllocatedGb()) {
            $change = $to->getAllocatedGb() - $from->getAllocatedGb();
            $grid[] = array(
                "hostName"         => $change < 0 ? $from->getHostName() : $to->getHostName(),
                "gbThen"           => $from->getAllocatedGb(),
                "gbNow"            => $to->getAllocatedGb(),
                "allocatedGbDelta" => $change,
                "arrayName"        => $change < 0 ? $from->getArrayName() : $to->getArrayName(),
                "businessService"  => $change < 0 ? $from->getBusinessService() : $to->getBusinessService(),
                "subsystem"        => $change < 0 ? $from->getSubsystem() : $to->getSubsystem()
            );
        }
    }

    if (isset($export) && $export) {
        include "export_report_storagebyhost.php";
    } else {
        header('Content-Type: application/json');
        echo json_encode(
            array(
                "returnCode" => 0,
                "dateFrom"   => $dateFrom,
                "dateTo"     => $dateTo,
                "total"      => count($grid),
                "grid"       => $grid
            )
        );
    }
    exit;
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

