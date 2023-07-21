<?php

use STS\HPSIM\HPSIMBladeSnapshotListTable;
use STS\HPSIM\HPSIMBladeSnapshotTable;
use STS\HPSIM\HPSIMSnapshotListTable;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    // get the from and to dates from the snapshot_list table
    $snapListTable = new HPSIMSnapshotListTable();
    $snapList      = $snapListTable->getAllByType('blade');

    $snapListTo = $snapList[0];
    $snapListFrom = $snapList[1];

    $dateFrom = $snapListFrom->getDateStamp();
    $dateTo   = $snapListTo->getDateStamp();

    $snapTable = new HPSIMBladeSnapshotTable();
    $snapFrom  = $snapTable->getByDate($dateFrom);
    $snapTo    = $snapTable->getByDate($dateTo);

    // re-sort so that sterling is listed first
    $chDistSwitches = array();
    $stDistSwitches = array();
    $otherDistSwitches = array();
    foreach ($snapFrom as $row) {
        if (preg_match("/^Charlotte/", $row->getDistSwitchName())) {
            $chDistSwitches[] = $row;
        } else if (preg_match("/^Sterling/", $row->getDistSwitchName())) {
            $stDistSwitches[] = $row;
        } else {
            $otherDistSwitches[] = $row;
        }
    }
    /** @var SANScreenSnapshot[] $snapFrom */
    $snapFrom = array_merge($stDistSwitches, $chDistSwitches);
    $snapFrom = array_merge($snapFrom, $otherDistSwitches);

    // rollup the allocated storage amounts by san and also by tier
    $distSwRollupFrom   = array();
    $bsRollupFrom  = array();
    /** @var $row HPSIMBladeSnapshot */
    foreach ($snapFrom as $row) {
        $distSwitch = $row->getDistSwitchName() != "" ? $row->getDistSwitchName() : "- Undefined -";
        $businessService = $row->getBusinessService() != "" ? $row->getBusinessService() : "- Undefined -";

        if (!array_key_exists($distSwitch, $distSwRollupFrom)) {
            $distSwRollupFrom[$distSwitch] = 0;
            $bsRollupFrom[$distSwitch] = array();
        }
        if (!array_key_exists($businessService, $bsRollupFrom[$distSwitch])) {
            $bsRollupFrom[$distSwitch][$businessService] = 0;
        }
        $distSwRollupFrom[$distSwitch] += 1;
        $bsRollupFrom[$distSwitch][$businessService] += 1;
    }

    $distSwRollupTo   = array();
    $bsRollupTo = array();
    /**
     * create a hash that we can compare from/to business services
     * we'll remove elements in this hash as we loop thru the "from" BSs
     * then we'll add any remaining BSs in this hash back to the report
     */
    $bsHash = array();
    /** @var $row HPSIMBladeSnapshot */
    foreach ($snapTo as $row) {
        $distSwitch = $row->getDistSwitchName() != "" ? $row->getDistSwitchName() : "- Undefined -";
        $businessService = $row->getBusinessService() != "" ? $row->getBusinessService() : "- Undefined -";

        if (!array_key_exists($distSwitch, $distSwRollupTo)) {
            $distSwRollupTo[$distSwitch] = 0;
            $bsRollupTo[$distSwitch] = array();
        }
        if (!array_key_exists($distSwitch, $bsHash)) {
            $bsHash[$distSwitch] = array();
        }
        if (!array_key_exists($businessService, $bsRollupTo[$distSwitch])) {
            $bsRollupTo[$distSwitch][$businessService] = 0;
        }
        if (!array_key_exists($businessService, $bsHash[$distSwitch])) {
            $bsHash[$distSwitch][$businessService] = $row;
        }
        $distSwRollupTo[$distSwitch] += 1;
        $bsRollupTo[$distSwitch][$businessService] += 1;
    }

    $grid = array();

    $lastDistSwitch   = "";
    $lastBusinessService  = "";
    foreach ($bsRollupFrom as $distSwitch => $businessServices) {
        $distSwitch = $from->getDistSwitchName() != "" ? $from->getDistSwitchName() : "- Undefined -";
        $businessService = $from->getBusinessService() != "" ? $from->getBusinessService() : "- Undefined -";

        if ($distSwitch != $lastDistSwitch) {
            if ($lastDistSwitch != "") {
                // write out any new BSs for this distribution switch if they are left in the bsHash
                if ($lastDistSwitch != "" && count($bsHash[$lastDistSwitch]) > 0) {
                    foreach ($bsHash[$lastDistSwitch] as $bs => $row) {
                        $grid[]        = array(
                            "type"            => "businessService",
                            "distSwitch"         => "",
                            "businessService"    => $bs,
                            "countThen"          => 0,
                            "countNow"           => $bsRollupTo[$lastDistSwitch][$bs],
                            "countDelta"         => $bsRollupTo[$lastDistSwitch][$bs],
                            "divider"         => false
                        );
                    }
                }
            }
            $grid[]        = array(
                "type"            => "",
                "distSwitch"         => "",
                "businessService"    => "",
                "countThen"          => "",
                "countNow"           => "",
                "countDelta"         => "",
                "divider"         => true
            );
            $grid[]        = array(
                "type"            => "distSwitch",
                "distSwitch"         => $distSwitch,
                "businessService"    => "",
                "countThen"          => $distSwRollupFrom[$distSwitch],
                "countNow"           => $distSwRollupTo[$distSwitch],
                "countDelta"         => $distSwRollupTo[$distSwitch] - $distSwRollupFrom[$distSwitch],
                "divider"         => false
            );
        }

        /*
        if ($businessService != $lastBusinessService) {
            if ($lastBusinessService != "") {

            }
        */
            // now we have to check to see if this is a new business service in which case we will
            // not have a count for last week. Also, if last week's BS is not available we also need
            // to handle that. In both cases the BS will be blank for the missing from or to
            if (array_key_exists($businessService, $bsRollupTo[$distSwitch])) {
                $countThen = $bsRollupFrom[$distSwitch][$businessService];
                $countNow = $bsRollupTo[$distSwitch][$businessService];
                $countDelta = $bsRollupTo[$distSwitch][$businessService] - $bsRollupFrom[$distSwitch][$businessService];

                // remove from our hash
                unset($bsHash[$distSwitch][$businessService]);
            } else {
                $countThen = $bsRollupFrom[$distSwitch][$businessService];
                $countNow = 0;
                $countDelta = 1 - $bsRollupFrom[$distSwitch][$businessService];
            }

            $grid[]        = array(
                "type"            => "businessService",
                "distSwitch"         => "",
                "businessService"    => $businessService,
                "countThen"          => $countThen,
                "countNow"           => $countNow,
                "countDelta"         => $countDelta,
                "divider"         => false
            );
        //}

        $lastDistSwitch   = $distSwitch;
        $lastBusinessService  = $businessService ;
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

