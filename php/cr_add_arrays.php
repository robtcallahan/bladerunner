<?php
/*******************************************************************************
 *
 * $Id: add_hosts_to_cr.php 74825 2013-04-30 18:33:29Z rcallaha $
 * $Date: 2013-04-30 14:33:29 -0400 (Tue, 30 Apr 2013) $
 * $Author: rcallaha $
 * $Revision: 74825 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/php/add_hosts_to_cr.php $
 *
 *******************************************************************************
 */

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBChangeRequestTable;
use STS\CMDB\CMDBTaskCITable;

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

	// get the passed parameters
	$crId = $_POST['crId'];
    $arrays = json_decode($_POST['arrays']);
	$removeCisSubsystems = $_POST['removeCisSubsystems'];

	logMsg("Performing bulk insert of " . (count($arrays)) . " arrays...");

	// necessary classes
	$useUserCredentials = true;
	$crTable = new CMDBChangeRequestTable($useUserCredentials);
	$ciTable = new CMDBTaskCITable($useUserCredentials);

	// cr details
	$cr = $crTable->getById($crId);

	// array for existing hosts in CR
	$ciArrays = array();

	// grab all existing hosts in the CR
	$ciItems = array();
	$ciItems = $ciTable->getByTaskId($crId);

	// Push the sys_id of the host from the ci_item field into ciHosts
	foreach ($ciItems as $ciItem)
	{
		$ciArrays[$ciItem->getCiItem()] = $ciItem->getCiItem();
	}

	// add the arrays in one multiple insert
	$records = array();
	for ($i=0; $i<count($arrays); $i++)
	{
		$array = $arrays[$i];
		if($removeCisSubsystems || !in_array($array->sysId, $ciArrays)) {
			$records[] = array(
				"task" => $crId,
				"ci_item" => $array->sysId
				);
		}
	}
	$json = array("records" => $records);
	$ciTable->createMultiple(json_encode($json));
	logMsg("complete<br>");

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

