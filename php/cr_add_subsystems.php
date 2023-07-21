<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBChangeRequestTable;
use STS\CMDB\CMDBTaskCITable;
use STS\CMDB\CMDBRelatedImpactedServiceTable;

use STS\Util\SysLog;

try {
	// config file
	$config = $GLOBALS['config'];

	// initialize syslog
	$sysLog = new SysLog($config->appName);
	$sysLog->setLogLevel($config->logLevel);
	$sysLog->debug("Debug starting...");

	// this user
	$actorUserName = $_SERVER["PHP_AUTH_USER"];

	// initialize the real-time log output file
	$rtLogFileName = "{$config->tmpDir}/rt-{$actorUserName}.log";
	$rtLog = fopen($rtLogFileName, "a");
	
	// initialize the return message string and default tabs
	$logStr = "";
	$tab = " &nbsp; &nbsp; &nbsp; ";
	$line = "----------------------------------------";

	// get the passed params
	$nodeType = $_POST['nodeType'];
	$crId = $_POST['crId'];
	$subSysIdsHash = json_decode($_POST['subSysIdsHash']);
	$removeCisSubsystems = $_POST['removeCisSubsystems'];
	$startTime = $_POST['startTime'];

	$numSubsystems = count(get_object_vars($subSysIdsHash));
	logMsg("Performing bulk insert of " . $numSubsystems . " subsystems...<br>");

	// necessary classes
	$useUserCredentials = true;
	$crTable = new CMDBChangeRequestTable($useUserCredentials);
	$ciTable = new CMDBTaskCITable($useUserCredentials);
	$riTable = new CMDBRelatedImpactedServiceTable($useUserCredentials);

	// cr details
	$cr = $crTable->getById($crId);

	// get the existing subsystems for the CR from the Related Impacted Service table
	$relServicesAr = array();
	$relServicesAr = $riTable->getByCRId($crId);

	// make it into an array we can use
	$relServicesHash = array();
	foreach($relServicesAr as $relService) {
		$relServicesHash[$relService->getSubsystemId()] = $relService->getSubsystemId();
	}

	// add the subsystems in one multiple insert
	$ssIndex = 1;
	$records = array();
	foreach ($subSysIdsHash as $ssId => $ssObj) {
		logMsg("[" . $ssIndex . " of " . $numSubsystems . "] " . $ssObj->name . "<br>");
		if($removeCisSubsystems || !in_array($ssId, $relServicesHash)) {
			$records[] = array(
				"u_change" => $crId,
				"u_cmdb_subsystem" => $ssId
			);
		}
		$ssIndex++;
	}
	$json = array("records" => $records);
	$riTable->createMultiple(json_encode($json));

	// we're all done. here's the summary information
	logMsg("Complete<br><br>");

	logMsg("CR update completed successfully<br><br>");
	$endTime = time();
	$elapsedSecs = $endTime - $startTime;
	$elapsedFormat = sprintf("%02d:%02d", floor($elapsedSecs / 60), $elapsedSecs % 60);
	logMsg("Start Time: " . date("Y-m-d H:i:s", $startTime) . "<br>");
	logMsg("End Time: " . date("Y-m-d H:i:s", $endTime) . "<br>");
	logMsg("Elapsed Time: " . $elapsedFormat . "<br><br>");

	logMsg("Click OK to close this window<br>");
	fclose($rtLog);
	$logStr = file_get_contents($rtLogFileName);
	unlink($rtLogFileName);

	print json_encode(
		array(
			"success" => 0,
			"log" => $logStr
			)
		);
	exit;
}

catch(\Exception $e) {
    header('Content-Type: application/json');
	print json_encode(
		array(
			"success"    => 1,
			"errorCode"  => $e->getCode(),
			"errorText"  => $e->getMessage(),
			"errorFile"  => $e->getFile(),
			"errorLine"  => $e->getLine(),
			"errorStack" => $e->getTraceAsString()
			)
		);
	exit;
}

function logMsg($logMsg)
{
	global $rtLog, $logStr;
	
	fwrite($rtLog, $logMsg);
	$logStr .= $logMsg;
}

