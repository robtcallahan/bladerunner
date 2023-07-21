<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBTaskListTable;
use STS\CMDB\CMDBUserTable;

try {
    // this user
    $actorUserName = $_SERVER["PHP_AUTH_USER"];
    $cmdbUserTable = new CMDBUserTable();
    $actor         = $cmdbUserTable->getByUserName($actorUserName);

    $query = array_key_exists('query', $_POST) ? $_POST['query'] : "";

    $cmdbTaskListTable = new CMDBTaskListTable();
    $cmdbTasks         = $cmdbTaskListTable->getCoreHostingTasks($actor);

    $tasks = array();
    for ($i = 0; $i < count($cmdbTasks); $i++) {
        $t = $cmdbTasks[$i];

        // for testing only
        // if ($t->getNumber() != "TASK0046961" && $t->getNumber() != "TASK0046962") continue;

        $o        = (object)array(
            "sysId"      => $t->getSysId(),
            "number"     => $t->getNumber(),
            "shortDescr" => $t->getShortDescription(),
            "openedBy"   => $t->getOpenedBy(),
            "openedAt"   => $t->getOpenedAt()
        );
        $tasks [] = $o;
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "total"      => count($tasks),
            "tasks"      => $tasks
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

