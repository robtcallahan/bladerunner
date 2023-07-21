<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;

use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenSwitchTable;

use STS\CMDB\CMDBStorageDeviceTable;
use STS\CMDB\CMDBSANSwitchTable;

use STS\CMDB\CMDBChangeRequestTable;
use STS\CMDB\CMDBTaskCITable;
use STS\CMDB\CMDBRelatedImpactedServiceTable;

use STS\Util\SysLog;

try
{
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

	logMsg("<br>{$line}<br>");

	// instantiate the classes
	$ssSwitchTable = new SANScreenSwitchTable();
	$cmdbSANSwitchTable = new CMDBSANSwitchTable();

	// get the passed parameters
	$nodeType = $_POST['nodeType'];
	$dbId = $_POST['dbId'];
	$crId = $_POST['crId'];
    $removeCisSubsystems = $_POST['removeCisSubsystems'];

	// necessary classes
	$useUserCredentials = true;
	$crTable = new CMDBChangeRequestTable($useUserCredentials);
	$ciTable = new CMDBTaskCITable($useUserCredentials);
	$riTable = new CMDBRelatedImpactedServiceTable($useUserCredentials);

	// cr details
	$cr = $crTable->getById($crId);

	$targetSysId = null;
	$targetName = null;
	switch ($nodeType) {
		case "blade":
			logMsg("Looking up this blade in the CMDB...<br>");

			$simBladeTable = new HPSIMBladeTable();
			$blade = $simBladeTable->getById($dbId);
			$targetName = $blade->getFullDnsName();

			$cmdbServerTable = new STS\CMDB\CMDBServerTable();
			$snServer = $cmdbServerTable->getByNameStartsWith($targetName);

			if ($snServer->getSysId()) {
				logMsg("{$tab}{$snServer->getName()} blade found<br><br>");
				$targetSysId = $snServer->getSysId();
				$targetName = $snServer->getName();
			}
			break;

		case "chassis":
			// get a list of blades from the chassis
			logMsg("Looking up this chassis in the CMDB...<br>");

			$simChassisTable = new HPSIMChassisTable();
			$chassis = $simChassisTable->getById($dbId);
			$targetName = $chassis->getFullDnsName();

			$cmdbServerTable = new STS\CMDB\CMDBServerTable();
			$snServer = $cmdbServerTable->getByNameStartsWith($targetName);

			if ($snServer->getSysId()) {
				logMsg("{$tab}{$snServer->getName()} chassis found<br><br>");
				$targetSysId = $snServer->getSysId();
				$targetName = $snServer->getName();
			}
			break;

		case "array":
			logMsg("Looking up this array in the CMDB...<br>");

			$ssArrayTable = new SANScreenArrayTable();
			$ssArray = $ssArrayTable->getById($dbId);
			$targetName = $ssArray->getName();

			$cmdbSDTable = new CMDBStorageDeviceTable();
			$snArray = $cmdbSDTable->getBySerialNumber($ssArray->getSerialNumber());

			if ($snArray->getSysId())
			{
				logMsg("{$tab}{$ssArray->getSerialNumber()} array found<br><br>");
				$targetSysId = $snArray->getSysId();
				$targetName = $ssArray->getSerialNumber();
			}
			break;

		case "switch":
			logMsg("Looking up this SAN Switch in the CMDB...<br>");

			// san switch details
			$ssSwitchTable = new SANScreenSwitchTable();
			$ssSwitch = $ssSwitchTable->getById($dbId);
			$targetName = $ssSwitch->getName();

			// find the switch in cmdb
			$snSANSwitch = $cmdbSANSwitchTable->getByNameStartsWith($targetName);
			if ($snSANSwitch->getSysId())
			{
				logMsg("{$tab}{$snSANSwitch->getName()} SAN switch found<br><br>");
				$targetSysId = $snSANSwitch->getSysId();
				$targetName = $snSANSwitch->getName();
			}
			break;

		default:
			throw new ErrorException("Unknown node type: {$nodeType}");
	}

	if ($targetSysId == null)
	{
		logMsg("{$tab}{$targetName} SAN switch could not be found.<br>");
		logMsg("{$tab}It will not be added to the CR's CIs field.<br>");
		logMsg("{$tab}You may wish to do this manually.<br><br>");
	}

	// if it was found, then add it to the cr
	else
	{
		logMsg("Adding {$targetName} to the CI field...");
		$targetCIs = array();
		$existingCI = $cr->getAdditionalCisId();

		if (preg_match("/,/", $existingCI))
		{
			$targetCIs = explode(",", $existingCI);
		}
		else
		{
			$targetCIs = array($existingCI);
		}

		// Are we blowing away the u_additional_cis field?
		if($removeCisSubsystems){
			$targetCIs = array($targetSysId);
		}else{
			$targetCIs[] = $targetSysId;
		}

		// Generate the CSV with unique array values, so we don't get dupes.
		$targetCIsCSV = implode(',', array_unique($targetCIs));
		$json = '{"u_additional_cis":"' . $targetCIsCSV . '"}';
		$crTable->updateByJson($cr->getSysId(), $json);
		logMsg("complete<br><br>");
	}

	if ($removeCisSubsystems)
	{
		// remove all existing host CIs
		logMsg("Removing existing 'Affected CIs'...");
		$ciTable->deleteMultiple("task={$crId}");
		logMsg("complete<br>");

		// remove all existing subsystems
		logMsg("Removing existing 'Related Impacted Subsystems...");
		$riTable->deleteMultiple("u_change={$crId}");
		logMsg("complete<br>");
	}

    header('Content-Type: application/json');
	print json_encode(
		array(
			"success" => 0
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

