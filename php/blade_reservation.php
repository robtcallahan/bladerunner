<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeReservationTable;
use STS\HPSIM\HPSIMBladeWWNTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMChassisWWNTable;
use STS\HPSIM\HPSIMMgmtProcessorTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
use STS\HPSIM\HPSIMVMTable;

use STS\CMDB\CMDBTaskListTable;

try
{
	// default time zone
	date_default_timezone_set("America/New_York");

	// config file
	$config = $GLOBALS['config'];
	
	// this user
	$actorUserName = $_SERVER["PHP_AUTH_USER"];

	// get the params
	$nodeType    = $_POST['nodeType'];
	$dbId        = $_POST['dbId'];
	$taskSysId   = $_POST['taskSysId'];
	$projectName = $_POST['projectName'];
	$action      = $_POST['action'];
	
	// action must be one of "new", "modify", "complete" or "cancel"
	if (!preg_match("/new|modify|complete|cancel/", $action))
	{
		throw new ErrorException("Unknown action: {$action}");
	}
	
	// nodeType must be "blade"
	if ($nodeType != "blade")
	{
		throw new ErrorException("Node type, {$nodeType}, cannot be reserved");
	}
	
	$bladeId = $dbId;
	
	// check for existing reservation
	$simBladeTable = new HPSIMBladeTable();
	$blade = $simBladeTable->getById($bladeId);
	
	$simChassisTable = new HPSIMChassisTable();
	$chassis = $simChassisTable->getById($blade->getChassisId());
	
	$blResTable = new HPSIMBladeReservationTable();
	$blRes = $blResTable->getOpenByBladeId($bladeId);
	
	$cmdbTaskListTable = new CMDBTaskListTable($useUserCredentials = true);
	
	// check if reservation exists
	if ($action == "new")
	{
		if ($blRes && $blRes->getUserUpdated())
		{
			throw new ErrorException("New is an invalid action for an existing reservation");
		}
		
		// this is a new reservation
		$blRes->setBladeId($bladeId);
		$blRes->setProjectName($projectName);

		$blRes->setUserReserved($actorUserName);
		$blRes->setDateReserved(date("Y-m-d H:i:s"));
		$blRes->setUserUpdated($actorUserName);
		$blRes->setDateUpdated(date("Y-m-d H:i:s"));
		
		// get the Core Task in SN if specified
		if ($taskSysId != "") {
			$blRes->setTaskSysId($taskSysId);
			$cmdbTask = $cmdbTaskListTable->getById($taskSysId);
			$blRes->setTaskNumber($cmdbTask->getNumber());
			$blRes->setTaskShortDescr($cmdbTask->getShortDescription());

			// update the current task in SN
			$workNotes = "The HP blade named '{$blade->getDeviceName()}' " .
				"with serial number '{$blade->getSerialNumber()}' " .
				"in slot '{$blade->getSlotNumber()}' of chassis '{$chassis->getDeviceName()}' " .
				"has been reserved for this task.";
			$json      = '{"work_notes":"' . $workNotes . '"}';
			$cmdbTaskListTable->updateByJson($cmdbTask->getSysId(), $json);
		}
		// save the new record
		$blRes = $blResTable->create($blRes);
	}
	
	// reservation already exists
	else if ($action == "modify")
	{
		if ($blRes->getUserUpdated() == "")
		{
			throw new ErrorException("Reservation does not exist for {$blade->getDeviceName()} ({$blade->getId()})");
		}
		
		// check if the user changed tasks
		if ($taskSysId != "" && $blRes->getTaskSysId() != "" && $blRes->getTaskSysId() != $taskSysId)
		{
			// tasks have changed. Need to update both of them in SN
			// here we'll update the previous once saying it's been cancelled	
			$cmdbTask = $cmdbTaskListTable->getById($blRes->getTaskSysId());
			if ($cmdbTask && $cmdbTask->getSysId())
			{
				$workNotes = "The reservation of HP blade named '{$blade->getDeviceName()}' " .
				             "with serial number '{$blade->getSerialNumber()}' " .
		                     "in slot '{$blade->getSlotNumber()}' of chassis '{$chassis->getDeviceName()}' " .
		                     "has been cancelled.";
		        $json = '{"work_notes":"' . $workNotes . '"}';		
				$cmdbTaskListTable->updateByJson($cmdbTask->getSysId(), $json);
			}
		}
		
		if ($taskSysId != "") {
			// get the current Core Task in SN
			$cmdbTask = $cmdbTaskListTable->getById($taskSysId);

			// update the current task in SN
			$workNotes = "The HP blade named '{$blade->getDeviceName()}' " .
				"with serial number '{$blade->getSerialNumber()}' " .
				"in slot '{$blade->getSlotNumber()}' of chassis '{$chassis->getDeviceName()}' " .
				"has been reserved for this task.";
			$json      = '{"work_notes":"' . $workNotes . '"}';
			$cmdbTaskListTable->updateByJson($cmdbTask->getSysId(), $json);

			// save the new record
			$blRes->setTaskSysId($taskSysId);
			$blRes->setTaskNumber($cmdbTask->getNumber());
			$blRes->setTaskShortDescr($cmdbTask->getShortDescription());
		}
		$blRes->setProjectName($projectName);
		$blRes->setUserUpdated($actorUserName);
		$blRes->setDateUpdated(date("Y-m-d H:i:s"));
		
		$blRes = $blResTable->update($blRes);
	}
	
	else if ($action == "complete")
	{
		if ($blRes->getUserUpdated() == "")
		{
			throw new ErrorException("Reservation does not exist for {$blade->getDeviceName()} ({$blade->getId()})");
		}

		$blRes->setUserUpdated($actorUserName);
		$blRes->setDateUpdated(date("Y-m-d H:i:s"));
		$blRes->setUserCompleted($actorUserName);
		$blRes->setDateCompleted(date("Y-m-d H:i:s"));
		
		// save the new record
		$blRes = $blResTable->update($blRes);
	}

	else if ($action == "cancel")
	{
		if ($blRes->getUserUpdated() == "")
		{
			throw new ErrorException("Reservation does not exist for {$blade->getDeviceName()} ({$blade->getId()})");
		}

		$blRes->setUserUpdated($actorUserName);
		$blRes->setDateUpdated(date("Y-m-d H:i:s"));
		$blRes->setUserCancelled($actorUserName);
		$blRes->setDateCancelled(date("Y-m-d H:i:s"));
		
		// save the new record
		$blRes = $blResTable->update($blRes);
		
		if ($taskSysId != "") {
			// get the Core Task in SN
			$cmdbTask = $cmdbTaskListTable->getById($taskSysId);
			if ($cmdbTask && $cmdbTask->getSysId()) {
				$workNotes = "The reservation of HP blade named '{$blade->getDeviceName()}' " .
					"with serial number '{$blade->getSerialNumber()}' " .
					"in slot '{$blade->getSlotNumber()}' of chassis '{$chassis->getDeviceName()}' " .
					"has been cancelled.";
				$json      = '{"work_notes":"' . $workNotes . '"}';
				$cmdbTaskListTable->updateByJson($cmdbTask->getSysId(), $json);
			}
		}
	}
	else
	{
		throw new ErrorException("Unknown action: {$action}");
	}

    header('Content-Type: application/json');
	print json_encode(
		array(
			"returnCode" => 0
			)
		);
	exit;
}

catch(Exception $e) {
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

