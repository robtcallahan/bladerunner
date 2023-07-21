<?php

include __DIR__ . "/../config/global.php";

try 
{
	$query = $_POST['query'];
	$view = array_key_exists("view", $_POST) ? $_POST['view'] : "";
	
	$br = new BladeRunner();	
	if (preg_match("/:/", $query))
	{
		$hosts = $br->getWwnsAndNodeIds($query, $view);
	}
	else
	{
		$hosts = $br->getHostNamesAndNodeIds($query, $view);
	}
	
    header('Content-Type: application/json');
	echo json_encode(
		array(
			"returnCode" => 0,
			"errorCode"  => 0,
			"errorText"  => "",
			"total"      => count($hosts),
			"hosts"      => $hosts
			)
		);
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
