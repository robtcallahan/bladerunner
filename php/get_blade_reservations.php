<?php

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    // ServiceNow site
    $snSite   = $config->servicenow->site;
    $snConfig = $config->servicenow->{$snSite};
    $snHost   = $snConfig->server;

    $br   = new BladeRunner();
    $rows = $br->getAllBladeReservations();

    $res = array();
    for ($i = 0; $i < count($rows); $i++) {
        $r = $rows[$i];

        $o      = (object)array(
            "id"             => $r->id,
            "bladeId"        => $r->bladeId,
            "chassisId"      => $r->chassisId,
            "chassisName"    => $r->chassisName,
            "bladeName"      => $r->bladeName,
            "slotNumber"     => $r->slotNumber,
            "projectName"    => $r->projectName,
            "taskNumber"     => $r->taskNumber,
            "taskSysId"      => $r->taskSysId,
            "taskUri"        => "https://{$snHost}/nav_to.do?uri=sc_task.do?sys_id={$r->taskSysId}",
            "taskShortDescr" => $r->taskShortDescr,
            "dateReserved"   => $r->dateReserved,
            "userReserved"   => $r->userReserved,
            "dateUpdated"    => $r->dateUpdated,
            "userUpdated"    => $r->userUpdated
        );
        $res [] = $o;
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode"   => 0,
            "errorCode"    => 0,
            "errorText"    => "",
            "total"        => count($res),
            "reservations" => $res
        )
    );
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

