<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMVMTable;

try {

    // distribution switches
    if ($node == "/xenvms") {
        // data access objects
        $chassisTable = new HPSIMChassisTable();
        $networks     = $chassisTable->getNetworks();

        $nodes = array();
        // header row
        $nodeObj = array(
            "id"      => "/xenvms/HEADER",
            "type"    => "header",
            "col1"    => "Distribution Switch Name",
            #"col2" => "Device Name(s)",
            "col2"    => "Num Chassis",
            "col3"    => "CPUs",
            "col4"    => "Mem (GB)",
            "col5"    => "Free Mem (GB)",
            "col6"    => "Num VMs",
            "col7"    => "Total VM CPUs",
            "col8"    => "Total VM Mem (GB)",
            "col9"    => "",
            "col10"   => "",

            "header"  => true,
            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // request to show sterling first then charlotte; here's my attempt at that
        $list = "Sterling";
        $i    = -1;
        while (1) {
            $i++;
            if ($i >= count($networks)) {
                if ($list == "Charlotte") break;
                $i = 0;
                if ($list == "Sterling") {
                    $list = "Denver";
                } else {
                    $list = "Charlotte";
                }
            }
            $n      = $networks[$i];
            $swName = $n->distSwitchName;
            if (!preg_match("/^{$list}/", $swName)) continue;

            $totals  = getSwitchSummary($swName);
            $nodeObj = array(
                "id"      => "/xenvms/{$swName}",
                "dbId"    => $swName,  // normally this would be the id of the table, but we don't have a dist switch table...yet
                "type"    => "distSwitch",
                "col1"    => $swName,
                "col2"    => $totals['numChassis'],
                "col3"    => $totals['totalCPUs'],
                "col4"    => $totals['totalMemGB'],
                "col5"    => $totals['freeMemGB'],
                "col6"    => $totals['numVMs'],
                "col7"    => $totals['vmsCPUs'],
                "col8"    => $totals['vmsMemGB'],
                "col9"    => "",
                "col10"   => "",

                "iconCls" => 'network',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
        // add Unassigned
        $nodeObj = array(
            "id"      => "Unassigned",
            "dbId"    => "Unassigned",
            "type"    => "distSwitch",
            "col1"    => "Unassigned",
            "col2"    => "&nbsp;",
            "col3"    => "&nbsp;",
            "col4"    => "&nbsp;",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;",
            "col9"    => "",
            "col10"   => "",

            "iconCls" => 'network',
            "leaf"    => false
        );
        $nodes[] = $nodeObj;
    } // Chassis
    else if (preg_match("/^\/xenvms\/([A-Za-z\s]+)$/", $node, $m)) {
        $switchName = array_key_exists(1, $m) ? $m[1] : null;

        // data access objects
        $chassisTable = new HPSIMChassisTable();
        if ($switchName) {
            $chassis = $chassisTable->getBySwitchName($switchName);
        } else {
            $switchName = "all";
            $chassis    = $chassisTable->getAll("deviceName", "asc");
        }

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "/xenvms/{$switchName}/HEADER",
            "type"    => "header",
            "col1"    => "Chassis Name",
            "col2"    => "Num Blades",
            "col3"    => "&nbsp;",
            "col4"    => "&nbsp;",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;",
            "col9"    => "",
            "col10"   => "",

            "header"  => true,
            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        foreach ($chassis as $ch) {
            $totals  = getChassisSummary($ch);
            $nodeObj = array(
                "id"       => "/xenvms/{$switchName}/{$ch->getId()}",
                "dbId"     => $ch->getId(),
                "type"     => "chassis",
                "col1"     => $ch->getDeviceName(),
                "col2"     => $totals['numBlades'],
                "col3"     => $totals['totalCPUs'],
                "col4"     => $totals['totalMemGB'],
                "col5"     => $totals['freeMemGB'],
                "col6"     => $totals['numVMs'],
                "col7"     => $totals['vmsCPUs'],
                "col8"     => $totals['vmsMemGB'],
                "col9"    => "",
                "col10"   => "",

                "editable" => false,
                "iconCls"  => 'chassis',
                "leaf"     => false
            );
            $nodes[] = $nodeObj;
        }
    } // Blades
    else if (preg_match("/^\/xenvms\/([A-Za-z\s]+)\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $bladeTable = new HPSIMBladeTable();
        $blades     = $bladeTable->getByChassisId($chassisId, "slotNumber", "asc");

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "/xenvms/{$switchName}/{$chassisId}/HEADER",
            "type"    => "header",
            "col1"    => "Blade Name",
            "col2"    => "-",
            "col3"    => "&nbsp;",
            "col4"    => "&nbsp;",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;",
            "col9"    => "",
            "col10"   => "",

            "header"  => true,
            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        foreach ($blades as $b) {
            $totals = getBladeSummary($b);

            $nodeObj = array(
                "id"       => "/xenvms/{$switchName}/{$chassisId}/{$b->getId()}",
                "dbId"     => $b->getId(),
                "type"     => "blade",
                "col1"     => $b->getDeviceName(),
                "col2"     => "-",
                "col3"     => $totals['totalCPUs'],
                "col4"     => $totals['totalMemGB'],
                "col5"     => $totals['freeMemGB'],
                "col6"     => $totals['numVMs'],
                "col7"     => $totals['vmsCPUs'],
                "col8"     => $totals['vmsMemGB'],
                "col9"    => "",
                "col10"   => "",

                "editable" => true,
                "iconCls"  => "blade-active",
                "leaf"     => $bladeTable->getVmCount($b->getId()) == 0 && $bladeTable->getWwnCount($b->getId()) == 0 ? true : false
            );
            $nodes[] = $nodeObj;
        }
    } // Blade VMs
    else if (preg_match("/^\/xenvms\/([A-Za-z\s]+)\/(\d+)\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $bladeTable = new HPSIMBladeTable();
        $blade      = $bladeTable->getById($bladeId);

        $vmTable = new HPSIMVMTable();
        $vms     = $vmTable->getByBladeId($bladeId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/{$bladeId}/HEADER",
            "type"    => "header",
            "col1"    => "VM Name",
            "col2"    => "&nbsp;",
            "col3"    => "&nbsp;",
            "col4"    => "&nbsp;",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;",
            "col9"    => "",
            "col10"   => "",

            "header"  => true,
            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        foreach ($vms as $vm) {
            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/{$bladeId}/{$vm->getId()}",
                "dbId"    => $vm->getId(),
                "type"    => "vm",
                "col1"    => $vm->getDeviceName() ? $vm->getDeviceName() : $vm->getFullDnsName(),
                "col2"    => "-",
                "col3"    => "-",
                "col4"    => "-",
                "col5"    => "-",
                "col6"    => "-",
                "col7"    => $vm->getNumberOfCpus(),
                "col8"    => $vm->getMemorySize(),
                "col9"    => "",
                "col10"   => "",

                "iconCls" => "vm",
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Report: hypervisors
    else if (preg_match("/^\/xenvms\/export$/", $node, $m)) {
        $grid = array();

        $chassisTable = new HPSIMChassisTable();
        $bladeTable   = new HPSIMBladeTable();
        $vmTable      = new HPSIMVMTable();

        $networks = $chassisTable->getNetworks();

        $list = "Sterling";
        $i    = -1;
        while (1) {
            $i++;
            $comments[] = "i = " . $i . ", list = " . $list;
            if ($i >= count($networks)) {
                if ($list == "Charlotte") break;
                $i = 0;
                if ($list == "Sterling") {
                    $list = "Denver";
                } else {
                    $list = "Charlotte";
                }
            }
            $switchName = $networks[$i]->distSwitchName;
            if (!preg_match("/^{$list}/", $switchName)) continue;

            $totals  = getSwitchSummary($switchName);
            $nodes[] = array(
                "switchName"  => $switchName,
                "numChassis"  => $totals['numChassis'],
                "chassisName" => "-",
                "numBlades"   => "-",
                "bladeName"   => "-",
                "numVMs"      => $totals['numVMs'],
                "vmName"      => "-",
                "cpus"        => $totals['totalCPUs'],
                "totalMemGB"  => $totals['totalMemGB'],
                "freeMemGB"   => $totals['freeMemGB'],
                "vmsCPUs"     => $totals['vmsCPUs'],
                "vmsMemGB"    => $totals['vmsMemGB']
            );

            $chassis = $chassisTable->getBySwitchName($switchName);
            foreach ($chassis as $ch) {
                $totals  = getChassisSummary($ch);
                $nodes[] = array(
                    "switchName"  => $switchName,
                    "numChassis"  => "-",
                    "chassisName" => $ch->getDeviceName(),
                    "numBlades"   => $totals['numBlades'],
                    "bladeName"   => "-",
                    "numVMs"      => $totals['numVMs'],
                    "vmName"      => "-",
                    "cpus"        => $totals['totalCPUs'],
                    "totalMemGB"  => $totals['totalMemGB'],
                    "freeMemGB"   => $totals['freeMemGB'],
                    "vmsCPUs"     => $totals['vmsCPUs'],
                    "vmsMemGB"    => $totals['vmsMemGB']
                );

                $blades = $bladeTable->getByChassisId($ch->getId(), "slotNumber", "asc");
                foreach ($blades as $b) {
                    $totals  = getBladeSummary($b);
                    $nodes[] = array(
                        "switchName"  => $switchName,
                        "numChassis"  => "-",
                        "chassisName" => $ch->getDeviceName(),
                        "numBlades"   => "-",
                        "bladeName"   => $b->getDeviceName(),
                        "numVMs"      => $totals['numVMs'],
                        "vmName"      => "-",
                        "cpus"        => $totals['totalCPUs'],
                        "totalMemGB"  => $totals['totalMemGB'],
                        "freeMemGB"   => $totals['freeMemGB'],
                        "vmsCPUs"     => $totals['vmsCPUs'],
                        "vmsMemGB"    => $totals['vmsMemGB']
                    );

                    $vms = $vmTable->getByBladeId($b->getId());
                    foreach ($vms as $vm) {
                        $nodes[] = array(
                            "switchName"  => $switchName,
                            "numChassis"  => "-",
                            "chassisName" => $ch->getDeviceName(),
                            "numBlades"   => "-",
                            "bladeName"   => $b->getDeviceName(),
                            "numVMs"      => "-",
                            "vmName"      => $vm->getDeviceName() ? $vm->getDeviceName() : $vm->getFullDnsName(),
                            "cpus"        => "-",
                            "totalMemGB"  => "-",
                            "freeMemGB"   => "-",
                            "vmsCPUs"     => $vm->getNumberOfCpus(),
                            "vmsMemGB"    => $vm->getMemorySize()
                        );
                    }
                }
            }
        }

        $heads  = array("Dist SwitchName", "Num Chassis", "Chassis Name", "Num Blades", "Blade Name",
            "Num VMs", "VM Name", "Total CPUs", "Total Mem(GB)", "Free Mem(GB)", "Total VM CPUs", "Total VM Mem(GB)");
        $fields = array("switchName", "numChassis", "chassisName", "numBlades", "bladeName",
            "numVMs", "vmName", "cpus", "totalMemGB", "freeMemGB", "vmsCPUs", "vmsMemGB");
        exportData($nodes, $heads, $fields);
        exit;
    }


    header('Content-Type: application/json');
    echo json_encode($nodes);
    exit;

} catch (Exception $e) {
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


function exportData($grid, $heads, $fields) {
    global $config;

    $alignRight = array(
    	'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)
    );

    $styleArray = array(
        'font'      => array(
            'bold' => true,
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ),
        'borders'   => array(
            'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
            )
        )
    );

    $headStyle = array(
    	'font'      => array(
    		'bold' => true
    	),
    	'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
    	'borders'   => array(
    		'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
    	),
    	'fill'      => array(
    		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
    		'color' => array('rgb' => '99CCFF')
    	)
    );

    $colNames = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L');
    $excel    = new PHPExcel();
    $excel->setActiveSheetIndex(0);
    $sheet = $excel->getActiveSheet();

    $sheet->getStyle("A1:" . $colNames[count($colNames) - 1] . "1")->applyFromArray($headStyle);
    for ($i = 0; $i < count($colNames); $i++) {
        $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
    }

    $row = 1;
    // write the column heads out
    for ($i = 0; $i < count($heads); $i++) {
        $sheet->SetCellValue($colNames[$i] . $row, $heads[$i]);
    }

    for ($i = 0; $i < count($grid); $i++) {
        $g = $grid[$i];
        $row++;
        $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($alignRight);

        $n = 0;
       	foreach ($fields as $f) {
       		if ($f == "spacer") {
       			$sheet->SetCellValue($colNames[$n] . $row, "");
       		} else {
       			$sheet->SetCellValue($colNames[$n] . $row, $g[$f]);
       		}
       		$n++;
       	}
    }

    $dateStamp     = date('Y-m-d');
   	$excelFileName = "BladeRunner Hypervisors {$dateStamp}.xlsx";

   	// Save Excel 2007 file
   	$excelWriter = new PHPExcel_Writer_Excel2007($excel);
   	$excelWriter->save("{$config->exportDir}/{$excelFileName}");

   	$data = file_get_contents("{$config->exportDir}/{$excelFileName}");
   	header("Pragma: public");
   	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
   	header("Content-Type: application/ms-excel");
   	header("Content-Length: " . (strlen($data) * 8));
   	header('Content-Disposition: attachment; filename="' . $excelFileName . '"');

   	// echo and exit
   	echo $data;
}

/**
 * @param $chassis STS\HPSIM\HPSIMChassis
 */
function getSwitchSummary($switchName) {
    $chassisTable = new HPSIMChassisTable();
    $chassis      = $chassisTable->getBySwitchName($switchName);

    $data = array(
        "numChassis" => count($chassis),
        "numBlades"  => 0,
        "totalCPUs"  => 0,
        "totalMemGB" => 0,
        "freeMemGB"  => 0,
        "numVMs"     => 0,
        "vmsCPUs"    => 0,
        "vmsMemGB"   => 0
    );

    foreach ($chassis as $ch) {
        $totals = getChassisSummary($ch);
        $data['numBlades'] += $totals['numBlades'];
        $data['totalCPUs'] += $totals['totalCPUs'];
        $data['totalMemGB'] += $totals['totalMemGB'];
        $data['freeMemGB'] += $totals['freeMemGB'];
        $data['numVMs'] += $totals['numVMs'];
        $data['vmsCPUs'] += $totals['vmsCPUs'];
        $data['vmsMemGB'] += $totals['vmsMemGB'];
    }
    return $data;
}

/**
 * @param $chassis STS\HPSIM\HPSIMChassis
 */
function getChassisSummary($chassis) {
    $bladeTable = new HPSIMBladeTable();
    $blades     = $bladeTable->getByChassisId($chassis->getId());

    $data = array(
        "numBlades"  => count($blades),
        "totalCPUs"  => 0,
        "totalMemGB" => 0,
        "freeMemGB"  => 0,
        "numVMs"     => 0,
        "vmsCPUs"    => 0,
        "vmsMemGB"   => 0
    );

    foreach ($blades as $blade) {
        $totals = getBladeSummary($blade);
        $data['totalCPUs'] += $totals['totalCPUs'];
        $data['totalMemGB'] += $totals['totalMemGB'];
        $data['freeMemGB'] += $totals['freeMemGB'];
        $data['numVMs'] += $totals['numVMs'];
        $data['vmsCPUs'] += $totals['vmsCPUs'];
        $data['vmsMemGB'] += $totals['vmsMemGB'];
    }
    return $data;
}

/**
 * @param $blade STS\HPSIM\HPSIMBlade
 */
function getBladeSummary($blade) {
    $data = array(
        "totalCPUs"  => $blade->getNumCpus() * $blade->getNumCoresPerCpu(),
        "totalMemGB" => $blade->getMemorySizeGB() > 1024 ? $blade->getMemorySizeGB() / 1024 / 1024 : $blade->getMemorySizeGB(),
        "freeMemGB"  => 0,
        "numVMs"     => 0,
        "vmsCPUs"    => 0,
        "vmsMemGB"   => 0,
    );
    // if this is not a hypervisor ('xm' or 'kvm' in the name), then just move along
    if (preg_match("/kvm|xm/", $blade->getDeviceName())) {
        $vmTable = new HPSIMVMTable();
        $vms     = $vmTable->getByBladeId($blade->getId());

        $data['numVMs'] = count($vms);
        foreach ($vms as $vm) {
            $data['vmsCPUs'] += $vm->getNumberOfCpus();
            $data['vmsMemGB'] += $vm->getMemorySize() >= 1024 ? $vm->getMemorySize() / 1024 : $vm->getMemorySize();
        }
        $data['freeMemGB'] += (intval($data['totalMemGB']) - intval($data['vmsMemGB']));
    }
    return $data;
}

function curlGetUrl($url, $post = null) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "{$_SERVER['PHP_AUTH_USER']}:{$_SERVER['PHP_AUTH_PW']}");
    //curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if (is_array($post)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}

