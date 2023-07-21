<?php
/*******************************************************************************
 *
 * $Id: assign_chassis_to_dist_switch.php 74932 2013-05-03 19:21:35Z rcallaha $
 * $Date: 2013-05-03 15:21:35 -0400 (Fri, 03 May 2013) $
 * $Author: rcallaha $
 * $Revision: 74932 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/bladerunner/trunk/php/assign_chassis_to_dist_switch.php $
 *
 *******************************************************************************
 */

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMChassisTable;

try 
{
	$chassisId = $_POST['chassisId'];
	$switchName = $_POST['switchName'];
	
	$simChassisTable = new HPSIMChassisTable();
	$chassis = $simChassisTable->getById($chassisId);

	$chassis->setDistSwitchName($switchName);
	$simChassisTable->update($chassis);
	
    header('Content-Type: application/json');
	echo json_encode(array("returnCode" => 0));
	exit;
}

catch (Exception $e) {
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

