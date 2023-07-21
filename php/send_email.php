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

    $emailTo = "rob.callahan@neustar.biz";
    #$emailTo = $_POST['to'];

    $emailFrom    = $actor->get("email");
    $emailSubject = $_POST['emailSubject'];
    $emailCc      = array_key_exists('cc', $_POST) ? $_POST['cc'] : '';
    $emailBcc     = array_key_exists('bcc', $_POST) ? $_POST['bcc'] : '';
    $emailBody    = $_POST['emailBody'];

    $headers = join("\r\n", array(
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=us-ascii",
        "From: {$emailFrom}",
        "Reply-To: {$emailFrom}",
        "X-Priority: 1",
        "X-MSMail-Priority: High",
        "X-Mailer: PHP/" . phpversion()
    ));

    if ($emailCc) $headers .= "Cc: {$emailCc}\r\n";
    if ($emailBcc) $headers .= "Bcc: {$emailBcc}\r\n";

    mail($emailTo, $emailSubject, $emailBody, $headers);

    header('Content-Type: application/json');
    print json_encode(
        array(
            "returnCode" => 0,
            "errorCode"  => 0,
            "errorText"  => "",
            "output"     => ""
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
            "errorStack" => $e->getTraceAsString(),
            "output"     => ""
        )
    );
    exit;
}

