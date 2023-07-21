<?php

include __DIR__ . "/../config/global.php";

use STS\Login\LoginTable;
use STS\Login\UserTable;

try {
    // get the user and update the page view
    $userName  = $_SERVER["PHP_AUTH_USER"];
    $userTable = new UserTable();
    $actor     = $userTable->getByUserName($userName);

    $loginTable = new LoginTable();
    $loginTable->record($actor->getId());

    $logins = $loginTable->getAll();

    $data = array();
    for ($i = 0; $i < count($logins); $i++) {
        $l = $logins[$i];

        $browser  = "unknown";
        $platform = "unknown";
        $dept     = "unknown";
        if (preg_match("/MS(IE)|(Chrome)|(Safari)|(Firefox)|(Opera)/", $l->getUserAgent(), $m)) {
            $browser = $m[count($m) - 1];
        }
        if (preg_match("/(Windows)|(Mac)/", $l->getUserAgent(), $m)) {
            $platform = $m[count($m) - 1];
        }
        $dept   = preg_replace("/^\d+ - /", "", $l->getDept());
        $data[] = (object)array(
            "id"        => $l->getId(),
            "empId"     => $l->getEmpId(),
            "lastName"  => $l->getLastName(),
            "firstName" => $l->getFirstName(),
            "username"  => $l->getUserName(),
            "title"     => $l->getTitle(),
            "dept"      => $dept,
            "numLogins" => $l->getNumLogins(),
            "lastLogin" => $l->getLastLogin(),
            "ipAddr"    => $l->getIpAddr(),
            "browser"   => $browser,
            "platform"  => $platform
        );
    }

    header('Content-Type: application/json');
    echo json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "count"      => count($data),
            "logins"     => $data
        )
    );
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
}
