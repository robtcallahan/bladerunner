<?php

include __DIR__ . "/../config/global.php";

use STS\Login\UserTable;
use STS\Login\PageViewTable;

try {
    // get the user and update the page view
    $userName  = $_SERVER["PHP_AUTH_USER"];
    $userTable = new UserTable();
    $actor     = $userTable->getByUserName($userName);

    $pvTable = new PageViewTable();
    $pvTable->record($actor->getId());

    $emailFrom    = $actor->get("email");
    $emailSubject = $_POST['emailSubject'];
    $emailBody    = $_POST['emailBody'];
    $emailTo      = "Rob.Callahan@neustar.biz";

    $headers = join("\r\n", array(
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=us-ascii",
        "From: {$emailFrom}",
        "Reply-To: {$emailFrom}",
        "X-Priority: 1",
        "X-MSMail-Priority: High",
        "X-Mailer: PHP/" . phpversion()
    ));


    mail($emailTo, $emailSubject, $emailBody, $headers);
} catch (Exception $e) {
    header('Content-Type: application/json');
    print json_encode(
        array(
            "returnCode" => 1,
            "errorCode"  => $e->getCode(),
            "errorText"  => $e->getMessage(),
            "errorFile"  => $e->getFile(),
            "errorLine"  => $e->getLine(),
            "errorStack" => $e->getTraceAsString(),
            "output"     => ""
        )
    );
    exit;
}

print json_encode(
    array(
        "returnCode" => 0,
        "errorCode"  => 0,
        "errorText"  => "",
        "output"     => ""
    )
);
exit;

