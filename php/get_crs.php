<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBUserTable;
use STS\CMDB\CMDBChangeRequestTable;
use STS\Util\SysLog;

try {
    $sysLog = SysLog::singleton('BladeRunner');
    $sysLog->debug();

    // param passed by the combo box
    $query = $_POST['query'];

    // this user
    $actorUserName = $_SERVER["PHP_AUTH_USER"];

    // get the actor's sys_id
    $cmdbUserTable = new CMDBUserTable();
    $actor         = $cmdbUserTable->getByUserName($actorUserName);
    $actorId       = $actor->getSysId();
    $sysLog->debug("actor name=" . $actor->getLastName() . "(" . $actorId . ")");

    // build an appropriate query
    $sysparmQuery = "active=true" .
        "^approval=not requested" .
        "^stateNOT IN5,3,10" .
        "^requested_by={$actorId}" .
        "^ORu_change_owner={$actorId}" .
        "^ORassigned_to={$actorId}";

    if ($query) {
        $sysparmQuery = "numberSTARTSWITH{$query}^" . $sysparmQuery;
    }

    $crTable = new CMDBChangeRequestTable();
    $crs     = $crTable->getByQueryString(rawurlencode($sysparmQuery));

    $results = array();
    for ($i = 0; $i < count($crs); $i++) {
        $cr        = $crs[$i];
        $results[] = array(
            "id"        => $cr->getSysId(),
            "name"      => $cr->getNumber(),
            "createdOn" => $cr->getSysCreatedOn(),
            "createdBy" => $cr->getSysCreatedBy(),
            "owner"     => $cr->getChangeOwner(),
            "requestor" => $cr->getRequestedBy(),
            "descr"     => $cr->getShortDescription()
        );
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "total"      => count($results),
            "crs"        => $results
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

