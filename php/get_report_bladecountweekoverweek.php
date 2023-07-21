<?php

use STS\HPSIM\HPSIMSnapshotListTable;
use STS\HPSIM\HPSIMBladeSnapshotAggregateTable;
use STS\HPSIM\HPSIMBladeSnapshotAggregate;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    $debug = false;

    // get the from and to dates from the snapshot_list table
    $snapListTable = new HPSIMSnapshotListTable();
    $snapList      = $snapListTable->getAllByType('blade');

    $aggTable = new HPSIMBladeSnapshotAggregateTable();
    $aggAll = array();
    $datesArray = array();
    for ($i=3; $i>=0; $i--) {
        $agg = $aggTable->getByDate($snapList[$i]->getDateStamp());
        $aggAll = array_merge($aggAll, $agg);
        $datesArray[] = $snapList[$i]->getDateStamp();
    }

    $aggHash = array();
    /** @var HPSIMBladeSnapshotAggregate $agg */
    foreach ($aggAll as $agg) {
        if (!array_key_exists($agg->getDistSwitchName(), $aggHash)) {
            $aggHash[$agg->getDistSwitchName()] = array();
        }
        if (!array_key_exists($agg->getBusinessService(), $aggHash[$agg->getDistSwitchName()])) {
            $aggHash[$agg->getDistSwitchName()][$agg->getBusinessService()] = array();
        }
        if (!array_key_exists($agg->getDateStamp(), $aggHash[$agg->getDistSwitchName()][$agg->getBusinessService()])) {
            $aggHash[$agg->getDistSwitchName()][$agg->getBusinessService()][$agg->getDateStamp()] = array();
        }
        $aggHash[$agg->getDistSwitchName()][$agg->getBusinessService()][$agg->getDateStamp()] = $agg->toObject();
    }
    #header('Content-Type: application/json');
    #print json_encode($aggHash);
    #exit;

    $dateHash = array();
    $data = array();
    foreach ($aggHash as $distSwitch => $bsObj) {
        foreach ($bsObj as $bs => $dateObj) {
            for ($i=3; $i>=0; $i--) {
                $dateHash[$snapList[$i]->getDateStamp()] = 1;
            }

            foreach ($dateObj as $date => $counts) {
                $data[] = array(
                    "distSwitch" => $distSwitch,
                    "businessService" => $bs,
                    "date" => $date,
                    "builds" => intval($counts->builds),
                    "decoms" => intval($counts->decoms)
                );
                unset($dateHash[$date]);
            }
            foreach ($dateHash as $date => $one) {
                $data[] = array(
                    "distSwitch" => $distSwitch,
                    "businessService" => $bs,
                    "date" => $date,
                    "builds" => 0,
                    "decoms" => 0
                );
            }
        }
    }


    function sortData($a, $b) {
        $r = strcmp($a['distSwitch'], $b['distSwitch']);
        if (!$r) {
            $r = strcmp($a['businessService'], $b['businessService']);
            if (!$r) {
                return strcmp($a['date'], $b['date']);
            } else {
                return $r;
            }
        } else {
            return $r;
        }
    }

    usort($data, 'sortData');

    $grid = array();
    #$lastDistSwitch = $data[0]['distSwitch'];
    $lastDistSwitch = "";
    $firstTime = true;
    for ($i=0; $i<count($data ); $i++) {
        $row = $data[$i];
        $row2 = $data[$i+1];

        if ($row['distSwitch'] !== $lastDistSwitch && !$firstTime) {
            $grid[] = array("divider" => true);
        }
        $grid[] = array(
            "distSwitch" => $row['distSwitch'] !== $lastDistSwitch ? $row['distSwitch'] : "&nbsp;",
            "businessService" => $row['businessService'],
            "date1" => $row['date'],
            "builds1" => $row['builds'],
            "decoms1" => $row['decoms'],
            "date2" => $data[$i+1]['date'],
            "builds2" => $data[$i+1]['builds'],
            "decoms2" => $data[$i+1]['decoms'],
            "date3" => $data[$i+2]['date'],
            "builds3" => $data[$i+2]['builds'],
            "decoms3" => $data[$i+2]['decoms'],
            "date4" => $data[$i+3]['date'],
            "builds4" => $data[$i+3]['builds'],
            "decoms4" => $data[$i+3]['decoms'],
            "divider" => false
        );
        $lastDistSwitch = $row['distSwitch'];
        $firstTime = false;
        $i += 3;
    }
    if (isset($export) && $export) {
        include "export_report_bladecountweekoverweek.php";
    } else {
        header('Content-Type: application/json');
        echo json_encode(
            array(
                "returnCode" => 0,
                "dates"      => $datesArray,
                "total"      => count($grid),
                "grid"       => $grid
            )
        );
        exit;
    }
}
catch (Exception $e) {
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

