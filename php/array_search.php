<?php

include __DIR__ . "/../config/global.php";

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenArrayTable;

use STS\CMDB\CMDBStorageDeviceTable;

try 
{
	$query = $_POST['query'];
	
	$ss = new SanScreen();	
    $ssArrayTable = new SANScreenArrayTable();

    // get an array of storage arrays
    $ssArrays = $ssArrayTable->getAll();

	if (preg_match("/:/", $query))
	{
		$wwnsResult = $ss->getWwnsAndNodeIds($query);
        error_log("num=" . count($wwnsResult));
        $data = array();
        foreach ($wwnsResult as $wwn) {
            if (preg_match("/^(\d+)\//", $wwn['node'], $m)) {
                $ssArrayId = $m[1];
                $ssArray = $ssArrayTable->getById($ssArrayId);
                if (array_key_exists($ssArray->getSerialNumber(), $cmdbArrayHash)) {
                    $data[] = array(
                        "name" => $wwn['name'],
                        "node" => $cmdbArrayHash[$ssArray->getSerialNumber()] . "/" . $wwn['node']
                    );
                }
            }
        }
	}
	else
	{
        // get a list of arrays and their node ids
        $arrays = array();
        foreach ($ssArrays as $array) {
            // skip any that don't match our query string (either name or serial number)
            $nameMatch = preg_match("/{$query}/", $array->getName());
            $snMatch = preg_match("/{$query}/", $array->getSerialNumber());
            if ($nameMatch || $snMatch) {
                // lookup the array in our local db to obtain it's artifical key
                $arrays[] = array (
                    "name" => $nameMatch ? $array->getName() : $array->getSerialNumber(),
                    "node" => $array->getSanName() . "/" . $array->getTier() . "/" . $array->getId()
                );
            }
        }

        // get a list of hosts and their node ids from the passed search string
		$hostsResult = $ss->getHostNamesAndNodeIds($query);
        $hosts = array();
        foreach ($hostsResult as $host) {
            if (preg_match("/^(\d+)\//", $host['node'], $m)) {
                $ssArrayId = $m[1];
                $ssArray = $ssArrayTable->getById($ssArrayId);
                $hosts[] = array(
                    "name" => $host['name'],
                    "node" => $ssArray->getSanName() . "/" . $ssArray->getTier() . "/" . $host['node']
                );
            }
        }


        // get a list of VMs and their node ids
		$vmsResult = $ss->getVMsAndNodeIds($query);
        $vms = array();
        foreach ($vmsResult as $vm) {
            if (preg_match("/^(\d+)\//", $vm['node'], $m)) {
                $ssArrayId = $m[1];
                $ssArray = $ssArrayTable->getById($ssArrayId);
                $vms[] = array(
                    "name" => $vm['name'],
                    "node" => $ssArray->getSanName() . "/" . $ssArray->getTier() . "/" . $vm['node']
                );
            }
        }

        // merge the arrays and sort by name
		$data = array_merge($arrays, $hosts, $vms);
		function sortByName($a, $b) { return strcmp($a['name'], $b['name']); }
		usort($data, 'sortByName');
	}
	
    header('Content-Type: application/json');
	echo json_encode(
		array(
			"returnCode" => 0,
			"errorCode"  => 0,
			"errorText"  => "",
			"total"      => count($data),
			"data"       => $data
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

