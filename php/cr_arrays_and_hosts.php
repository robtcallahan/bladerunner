<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenSwitchTable;

use STS\CMDB\CMDBChangeRequestTable;

use STS\Util\SysLog;

try {
	// config file
	$config = $GLOBALS['config'];
	$dryMode = $config->dryMode;
	$startTime = time();

	// initialize syslog
	$sysLog = new SysLog($config->appName);
	$sysLog->setLogLevel($config->logLevel);
	$sysLog->debug("Debug starting...");

	// this user
	$actorUserName = $_SERVER["PHP_AUTH_USER"];

	// get the params
	$nodeType = $_POST['nodeType'];
	$dbId = $_POST['dbId'];
	$crId = $_POST['crId'];
	$removeCIAndSubSystems = ($_POST['removeCisSubsystems']  === 'true'); // convert to boolean via string compare

	// initialize the real-time log output file
	$rtLogFileName = "{$config->tmpDir}/rt-{$actorUserName}.log";
	$rtLog = fopen($rtLogFileName, "w");
	
	// initialize the return message string and default tabs
	$logStr = "";
	$tab = " &nbsp; &nbsp; &nbsp; ";
	$line = "----------------------------------------";

    if ($dryMode) logMsg("<strong>NOTE: Dry Mode is enabled so no updates will be performed.</strong><br><br>");

	// look up the CR and echo out the info
	$useUserCredentials = true;
	$crTable = new CMDBChangeRequestTable($useUserCredentials);

	logMsg("Looking up Change Request...");
	$cr = $crTable->getById($crId);

	logMsg("<br>{$tab}Found {$cr->get('number')}<br>");
	logMsg("{$tab} Created On: {$cr->getSysCreatedOn()}<br>");
	logMsg("{$tab} Created By: {$cr->getSysCreatedBy()}<br>");
	logMsg("{$tab} Change Owner: {$cr->getChangeOwner()}<br>");
	logMsg("{$tab} Requested By: {$cr->getRequestedBy()}<br>");
	logMsg("{$tab} Short Descr: {$cr->getShortDescription()}<br><br>");


	logMsg("Getting a list of CIs for this CR...<br>");

	$arraysToProcess = array();
	$hostsToProcess = array();

	switch ($nodeType) {
		case "blade":
			$simBladeTable = new HPSIMBladeTable();
			$b = $simBladeTable->getById($dbId);

			$hostsToProcess[] = array(
				"id" => $b->getId(),
				"name" => $b->getFullDnsName()
			);
			break;

		case "chassis":
			// get a list of blades from the chassis
			$simBladeTable = new HPSIMBladeTable();
			$blades = $simBladeTable->getByChassisId($dbId);

			for ($i=0; $i<count($blades); $i++) {
				$hostsToProcess[] = array(
					"id" => $blades[$i]->getId(),
					"name" => $blades[$i]->getFullDnsName()
				);
			}
			break;

		case "array":
			$ssHostTable = new SANScreenHostTable();
			$arrayHosts = $ssHostTable->getByArrayId($dbId);
			for ($i=0; $i<count($arrayHosts); $i++)
			{
				$hostsToProcess[] = array(
					"id" => $arrayHosts[$i]->getId(),
					"name" => $arrayHosts[$i]->getName()
				);
			}
			break;

		case "switch":
			$ss            = new SANScreen();
			$ssSwitchTable = new SANScreenSwitchTable();

			$ssSwitch = $ssSwitchTable->getById($dbId);
			$ssArrays = $ss->getArraysBySwitchId($ssSwitch->getId());
			$ssHosts  = $ss->getHostsBySwitchId($ssSwitch->getId());

			for ($i=0; $i<count($ssArrays); $i++) {
				$arraysToProcess[] = array(
					"id" => $ssArrays[$i]->getId(),
					"name" => $ssArrays[$i]->getName()
				);
			}
			for ($i=0; $i<count($ssHosts); $i++) {
				$hostsToProcess[] = array(
					"id" => $ssHosts[$i]->getId(),
					"name" => $ssHosts[$i]->getName()
				);
			}
			break;

		default:
			throw new ErrorException("Unknown node type: {$nodeType}");
	}

	logMsg("Found " . count($arraysToProcess) . " arrays & " . count($hostsToProcess) . " hosts<br>");
	if (count($hostsToProcess) >= 400) {
		logMsg($tab . "OMG! This is going to take forever! Maybe you should go out for lunch.<br>");
	} else if (count($hostsToProcess) >= 100) {
		logMsg($tab . "Wow! This will take a long time. Check back later.<br>");
	} else if (count($hostsToProcess) >= 50) {
		logMsg($tab . "Hmm, this will take a few minutes. Get some coffee.<br>");
	}
	logMsg("<br>");
	logMsg("Looking up each item in the CMDB...<br>");

    header('Content-Type: application/json');
	print json_encode(
		array(
			"success"   => 0,
			"startTime" => $startTime,
			"arrays"    => $arraysToProcess,
			"hosts"     => $hostsToProcess
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

