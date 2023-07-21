<?php

use STS\SANScreen\SANScreenSnapshotListTable;
use STS\SANScreen\SANScreenArraySnapshotTable;
use STS\SANScreen\SANScreenArrayTable;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    $arrayTable = new SANScreenArrayTable();

    // get the from and to dates from the snapshot_list table
    $snapListTable = new SANScreenSnapshotListTable();
    $snapList      = $snapListTable->getAll();

    $snapListTo   = $snapList[0];
    $snapListFrom = $snapList[1];

    $dateFrom = $snapListFrom->getDateStamp();
    $dateTo   = $snapListTo->getDateStamp();

    $snapTable = new SANScreenArraySnapshotTable();
    $snapFrom  = $snapTable->getByDate($dateFrom);
    $snapTo    = $snapTable->getByDate($dateTo);

    // resort so that sterling is listed first
    $chSans = array();
    $stSans = array();
    foreach ($snapFrom as $row) {
        if (preg_match("/^CH/", $row->getSanName())) {
            $chSans[] = $row;
        } else if (preg_match("/^ST/", $row->getSanName())) {
            $stSans[] = $row;
        } else {
            $otherSans[] = $row;
        }
    }
    /** @var SANScreenSnapshot[] $snapFrom */
    $snapFrom = array_merge($stSans, $chSans);
    $snapFrom = array_merge($snapFrom, $otherSans);

    // rollup the allocated storage amounts by san and also by tier
    $sanRollupFrom   = array();
    $tierRollupFrom  = array();
    $arrayRollupFrom = array();
    foreach ($snapFrom as $row) {
        if (!array_key_exists($row->getSanName(), $sanRollupFrom)) {
            $sanRollupFrom[$row->getSanName()]                   = 0;
            $tierRollupFrom[$row->getSanName()][$row->getTier()] = 0;
        }
        if (!array_key_exists($row->getTier(), $tierRollupFrom[$row->getSanName()])) {
            $tierRollupFrom[$row->getSanName()][$row->getTier()] = 0;
        }
        if (!array_key_exists($row->getArrayName(), $arrayRollupFrom)) {
            $arrayRollupFrom[$row->getArrayName()] = 0;
        }
        $sanRollupFrom[$row->getSanName()] += $row->getProvisionedTb();
        $tierRollupFrom[$row->getSanName()][$row->getTier()] += $row->getProvisionedTb();
        $arrayRollupFrom[$row->getArrayName()] += $row->getProvisionedTb();
    }

    $sanRollupTo   = array();
    $tierRollupTo  = array();
    $arrayRollupTo = array();
    foreach ($snapTo as $row) {
        if (!array_key_exists($row->getSanName(), $sanRollupTo)) {
            $sanRollupTo[$row->getSanName()]                   = 0;
            $tierRollupTo[$row->getSanName()][$row->getTier()] = 0;
        }
        if (!array_key_exists($row->getTier(), $tierRollupTo[$row->getSanName()])) {
            $tierRollupTo[$row->getSanName()][$row->getTier()] = 0;
        }
        if (!array_key_exists($row->getArrayName(), $arrayRollupTo)) {
            $arrayRollupTo[$row->getArrayName()] = 0;
        }
        $sanRollupTo[$row->getSanName()] += $row->getProvisionedTb();
        $tierRollupTo[$row->getSanName()][$row->getTier()] += $row->getProvisionedTb();
        $arrayRollupTo[$row->getArrayName()] += $row->getProvisionedTb();
    }

    // TODO: need to check both from and to as new arrays will not be accounted for

    $grid = array();

    $lastSanName   = "";
    $lastTierName  = "";
    $lastArrayName = "";
    foreach ($snapFrom as $from) {
        if ($from->getSanName() != $lastSanName) {
            $grid[]        = array(
                "type"       => "",
                "sanName"    => "",
                "tier"       => "",
                "arrayName"  => "",
                "arrayModel" => "",
                "gbThen"     => "",
                "gbNow"      => "",
                "gbDelta"    => "",
                "divider"    => true
            );
            $lastTierName  = "";
            $lastArrayName = "";
            $grid[]        = array(
                "type"       => "san",
                "sanName"    => $from->getSanName() ? $from->getSanName() : "unknown",
                "tier"       => "",
                "arrayName"  => "",
                "arrayModel" => "",
                "gbThen"     => $sanRollupFrom[$from->getSanName()],
                "gbNow"      => $sanRollupTo[$from->getSanName()],
                "gbDelta"    => $sanRollupTo[$from->getSanName()] - $sanRollupFrom[$from->getSanName()],
                "divider"    => false
            );
        }
        if ($from->getTier() != $lastTierName) {
            $lastArrayName = "";
            $grid[]        = array(
                "type"       => "tier",
                "sanName"    => "",
                "tier"       => $from->getTier() ? $from->getTier() : "unknown",
                "arrayName"  => "",
                "arrayModel" => "",
                "gbThen"     => $tierRollupFrom[$from->getSanName()][$from->getTier()],
                "gbNow"      => $tierRollupTo[$from->getSanName()][$from->getTier()],
                "gbDelta"    => $tierRollupTo[$from->getSanName()][$from->getTier()] - $tierRollupFrom[$from->getSanName()][$from->getTier()],
                "divider"    => false
            );
        }
        if ($from->getArrayName() != $lastArrayName) {
            $array      = $arrayTable->getByName($from->getArrayName());
            $arrayModel = preg_replace("/Symmetrix /", "", $array->getModel());
            $arrayName  = $from->getArrayName();
            if (preg_match("/(\d\d\d\d)$/", $from->getArrayName(), $m)) {
                $arrayName = $m[1];
            }
            $grid[] = array(
                "type"       => "array",
                "sanName"    => "",
                "tier"       => "",
                "arrayName"  => $arrayName,
                "arrayModel" => $arrayModel,
                "gbThen"     => $arrayRollupFrom[$from->getArrayName()],
                "gbNow"      => $arrayRollupTo[$from->getArrayName()],
                "gbDelta"    => $arrayRollupTo[$from->getArrayName()] - $arrayRollupFrom[$from->getArrayName()],
                "divider"    => false
            );
        }

        $lastSanName   = $from->getSanName();
        $lastTierName  = $from->getTier();
        $lastArrayName = $from->getArrayName();
    }

    if (isset($export) && $export) {
        include "export_report_storagebyarray.php";
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

