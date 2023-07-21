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

    /** @var $snapList SanScreenSnapshotList */
    $snapList = $snapList[0];

    // SAN Name, Tier, Business Service, Allocated GBs
    $snapTable = new SANScreenHostSnapshotTable();
    $rows      = $snapTable->getReportBusinessServiceBySan($snapList->getDateStamp());

    // resort so that sterling is listed first
    $chSans = array();
    $stSans = array();
    foreach ($rows as $row) {
        if (preg_match("/^CH/", $row->sanName)) {
            $chSans[] = $row;
        } else {
            $stSans[] = $row;
        }
    }
    $rows = array_merge($stSans, $chSans);

    // rollup the allocated storage amounts by san and also by tier
    $sanRollup  = array();
    $tierRollup = array();
    foreach ($rows as $row) {
        if (!array_key_exists($row->sanName, $sanRollup)) {
            $sanRollup[$row->sanName]              = 0;
            $tierRollup[$row->sanName][$row->tier] = 0;
        }
        if (!array_key_exists($row->tier, $tierRollup[$row->sanName])) {
            $tierRollup[$row->sanName][$row->tier] = 0;
        }
        $sanRollup[$row->sanName] += $row->allocatedGb;
        $tierRollup[$row->sanName][$row->tier] += $row->allocatedGb;
    }

    $grid        = array();
    $lastSanName = "";
    $lastTier    = "";
    foreach ($rows as $row) {
        if ($row->sanName != $lastSanName) {
            $lastTier = "";
            $grid[]   = array(
                "type"            => "storageTotal",
                "sanName"         => $row->sanName ? $row->sanName : "unknown",
                "tier"            => "",
                "businessService" => "",
                "provisionedGb"   => number_format($sanRollup[$row->sanName])
            );
        }
        if ($row->tier != $lastTier) {
            $grid[] = array(
                "type"            => "storageTotal",
                "sanName"         => "",
                "tier"            => $row->tier ? $row->tier : "unknown",
                "businessService" => "",
                "provisionedGb"   => number_format($tierRollup[$row->sanName][$row->tier])
            );
        }
        $grid[]      = array(
            "type"            => "",
            "sanName"         => "",
            "tier"            => "",
            "businessService" => $row->businessService ? $row->businessService : "unknown",
            "provisionedGb"   => number_format($row->allocatedGb)
        );
        $lastSanName = $row->sanName;
        $lastTier    = $row->tier;
    }

    if (isset($export) && $export) {
        include "export_report_storagebsbysan.php";
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

