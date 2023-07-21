<?php

use STS\SANScreen\SANScreenSnapshotListTable;
use STS\SANScreen\SANScreenHostSnapshotTable;
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

    $snapTable = new SANScreenHostSnapshotTable();
    $snapFrom  = $snapTable->getByDateAndGroupByBusinessServiceAndArray($dateFrom);
    $snapTo    = $snapTable->getByDateAndGroupByBusinessServiceAndArray($dateTo);

    // resort so that sterling is listed first
    $chSans = array();
    $stSans = array();
    foreach ($snapFrom as $row) {
        if (preg_match("/^CH/", $row->getSanName())) {
            $chSans[] = $row;
        } else {
            $stSans[] = $row;
        }
    }
    /** @var SANScreenSnapshot[] $snapFrom */
    $snapFrom = array_merge($stSans, $chSans);

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
        $sanRollupFrom[$row->getSanName()] += $row->getAllocatedGb();
        $tierRollupFrom[$row->getSanName()][$row->getTier()] += $row->getAllocatedGb();
        $arrayRollupFrom[$row->getArrayName()] += $row->getAllocatedGb();
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
        $sanRollupTo[$row->getSanName()] += $row->getAllocatedGb();
        $tierRollupTo[$row->getSanName()][$row->getTier()] += $row->getAllocatedGb();
        $arrayRollupTo[$row->getArrayName()] += $row->getAllocatedGb();
    }

    // TODO: need to check both from and to as new arrays will not be accounted for

    $grid = array();

    $lastSanName   = "";
    $lastTierName  = "";
    $lastArrayName = "";
    foreach ($snapFrom as $from) {
        if ($from->getSanName() != $lastSanName) {
            $lastTierName  = "";
            $lastArrayName = "";
            $grid[]        = array(
                "type"            => "san",
                "sanName"         => $from->getSanName() ? $from->getSanName() : "unknown",
                "tier"            => "",
                "arrayName"       => "",
                "arrayModel"      => "",
                "businessService" => "",
                "gbThen"          => $sanRollupFrom[$from->getSanName()],
                "gbNow"           => $sanRollupTo[$from->getSanName()],
                "gbDelta"         => $sanRollupTo[$from->getSanName()] - $sanRollupFrom[$from->getSanName()]
            );
        }
        if ($from->getTier() != $lastTierName) {
            $lastArrayName = "";
            $grid[]        = array(
                "type"            => "tier",
                "sanName"         => "",
                "tier"            => $from->getTier() ? $from->getTier() : "unknown",
                "arrayName"       => "",
                "arrayModel"      => "",
                "businessService" => "",
                "gbThen"          => $tierRollupFrom[$from->getSanName()][$from->getTier()],
                "gbNow"           => $tierRollupTo[$from->getSanName()][$from->getTier()],
                "gbDelta"         => $tierRollupTo[$from->getSanName()][$from->getTier()] - $tierRollupFrom[$from->getSanName()][$from->getTier()]
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
                "type"            => "array",
                "sanName"         => "",
                "tier"            => "",
                "arrayName"       => $arrayName,
                "arrayModel"      => $arrayModel,
                "businessService" => "",
                "gbThen"          => $arrayRollupFrom[$from->getArrayName()],
                "gbNow"           => $arrayRollupTo[$from->getArrayName()],
                "gbDelta"         => $arrayRollupTo[$from->getArrayName()] - $arrayRollupFrom[$from->getArrayName()]
            );
        }

        $to = $snapTable->getByDateAndArrayAndBusinessService($dateTo, $from->getArrayName(), $from->getBusinessService());

        $change = $to->getAllocatedGb() - $from->getAllocatedGb();

        $businessService = $change < 0 ? $from->getBusinessService() : $to->getBusinessService();
        if ($businessService == "") {
            $businessService = 'unknown';
        }

        $grid[] = array(
            "type"            => "",
            "sanName"         => "",
            "tier"            => "",
            "arrayName"       => "",
            "arrayModel"      => "",
            "businessService" => $businessService,
            "gbThen"          => $from->getAllocatedGb(),
            "gbNow"           => $to->getAllocatedGb(),
            "gbDelta"         => $to->getAllocatedGb() - $from->getAllocatedGb()
        );

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

