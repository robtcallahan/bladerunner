<?php

use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMBladeTable;

try {

    if ($node == "/vmwarevms") {
        $simChassisTable = new HPSIMChassisTable();
        $simBladeTable   = new HPSIMBladeTable();

        $rows = $simChassisTable->getNetworks();
        $distSwitches = array();
        foreach ($rows as $row) {
            $distSwitches[] = $row->distSwitchName;
        }

        // sort by dist switch
        function sortByDistSwitch($a, $b) {
            $aST = preg_match("/Sterling/", $a);
            $aDE = preg_match("/Denver/", $a);
            $aCH = preg_match("/Charlotte/", $a);

            $bST = preg_match("/Sterling/", $b);
            $bDE = preg_match("/Denver/", $b);
            $bCH = preg_match("/Charlotte/", $b);

            // Dist switch locations in the following order: Sterling,
            if (($aST && !$bST) || ($aDE && !$bDE && !$bST)) {
                return -1;
            } else if (($aDE && $bST) || ($aCH && !$bCH)) {
                return 1;
            } else {
                $aDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $a);
                $bDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $b);
                $r       = strcmp($aDistSw, $bDistSw);
                return $r;
            }
        }
        usort($distSwitches, 'sortByDistSwitch');

        // read in the esx hypers file
        $esxHypers = json_decode(unserialize(file_get_contents("../data/esx-hypers.ser")));

        $nodes = array();
        // header row
        $nodeObj = array(
            "id"      => "/xenvmwarevms/HEADER",
            "type"    => "header",
            "col1"    => "Distribution Switch Name",
            "col2"    => "Chassis Name",
            "col3"    => "Cluster Name",
            "col4"    => "Hyper Name",
            "col5"    => "Hyper Model",
            "col6"    => "Business Service",
            "col7"    => "VM Name",
            "col8"    => "Total Mem",
            "col9"    => "Prov Mem",
            "col10"   => "Avail Mem",
            "col11"   => "Total VMs",

            "header"  => true,
            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;


        // sort by cluster name
        function sortByClusterName($a, $b) {
            return strcmp($a->ccrName, $b->ccrName);
        }

        $lastDistSwitch = "";
        $totalVMs       = 0;
        foreach ($distSwitches as $distSwitch) {
            $chassises = $simChassisTable->getBySwitchName($distSwitch);

            $lastChassisName = "";
            foreach ($chassises as $chassis) {
                // get the blades for this chassis
                $blades = $simBladeTable->getByChassisId($chassis->getId(), "slotNumber", "asc");

                // get the clusters from all the blades. We'll then need to sort by cluster
                // $clusters->cluster->blades[]
                $clusters = array();
                foreach ($blades as $blade) {
                    // skip non esx blades and those that have been decommed or disposed
                    if (!preg_match("/esx/", $blade->getDeviceName()) || $blade->getCmInstallStatus() == "Decommisioning" || $blade->getCmInstallStatus() == "Disposed") continue;

                    // insure we have this hyper in the structure
                    if (!property_exists($esxHypers, $blade->getFullDnsName())) {
                        continue;
                    }
                    // the hyper
                    $bName = $blade->getFullDnsName();
                    $hyper = $esxHypers->$bName;
                    $hyper->blade = $blade;
                    $vms   = $hyper->vms;

                    // now loop over our VMs to count mem
                    $memProvisioned = 0;
                    foreach ($vms as $vm) {
                        $memProvisioned += property_exists($vm, 'memoryGB') ? $vm->memoryGB : 0;
                    }
                    $hyper->memProvisioned = $memProvisioned;
                    $clusters[] = $hyper;
                }
                usort($clusters, 'sortByClusterName');
                if (count($clusters) == 0) continue;

                // loop over the clusters
                $lastClusterName = "";
                foreach ($clusters as $cluster) {
                    if (preg_match("/ProLiant BL460c (G.*)/", $cluster->blade->getProductName(), $m)) {
                        $productName = $m[1];
                    } else {
                        $productName = $cluster->blade->getProductName();
                    }
                    $nodes[] = array(
                        "id"        => "/vmwarevms/{$distSwitch}/{$chassis->getId()}/{$cluster->blade->getId()}",
                        "type"      => "blade",
                        "chassisId" => $chassis->getId(),
                        "bladeId"   => $blade->getId(),
                        "col1"      => $distSwitch != $lastDistSwitch ? $distSwitch : "",
                        "col2"      => $chassis->getDeviceName() != $lastChassisName ? $chassis->getDeviceName() : "",
                        "col3"      => $cluster->ccrName != $lastClusterName ? $cluster->ccrName : "",
                        "col4"      => $cluster->blade->getDeviceName(),
                        "col5"      => $productName,
                        "col6"      => "",
                        "col7"      => "",
                        "col8"      => $cluster->memorySizeGB,
                        "col9"      => $cluster->memProvisioned,
                        "col10"     => $cluster->memoryAvailableGB,
                        "col11"     => strval(count($cluster->vms)),

                        "editable"  => false,
                        "iconCls"   => $distSwitch != $lastDistSwitch ? 'network' : 'blank',
                        "leaf"      => false
                    );
                    $totalVMs += count($hyper->vms);
                    $lastDistSwitch  = $distSwitch;
                    $lastChassisName = $chassis->getDeviceName();
                    $lastClusterName = $cluster->ccrName;
                }
            }
        }

        // one last row to show totals
        $nodes[] = array(
            "id"        => "/vmwarevms/totals",
            "type"      => "totals",
            "col1"      => "",
            "col2"      => "",
            "col3"      => "",
            "col4"      => "",
            "col5"      => "",
            "col6"      => "",
            "col7"      => "Total VMs",
            "col8"      => "",
            "col9"      => "",
            "col10"     => "",
            "col11"     => $totalVMs,

            "editable" => false,
            "iconCls"  => 'blank',
            "leaf"     => true
        );
    }

    else if (preg_match("/vmwarevms\/([\w\s]+)\/(\d+)\/(\d+)/", $node, $m)) {
        $distSwitch = $m[1];
        $chassisId = $m[2];
        $bladeId = $m[3];

        // get the blade
        $simBladeTable = new HPSIMBladeTable();
        $blade = $simBladeTable->getById($bladeId);

        // read in the esx hypers file
        $esxHypers = json_decode(unserialize(file_get_contents("../data/esx-hypers.ser")));

        // structure to return
        $nodes = array();

        // find the details in the hypers file
        // get a list of VMs from the hyper structure
        if (!property_exists($esxHypers, $blade->getFullDnsName())) {
            header('Content-Type: application/json');
            echo json_encode($nodes);
            exit;
        }

        $bName = $blade->getFullDnsName();
        $hyper = $esxHypers->$bName;
        $vms   = $hyper->vms;

        // now loop over our VMs to create the list of nodes
        foreach ($vms as $vm) {
            $nodes[] = array(
                "id"        => "/vmwarevms/{$distSwitch}/{$chassisId}/{$bladeId}/{$vm->name}",
                "type"      => "vm",
                "chassisId" => $chassisId,
                "bladeId"   => $bladeId,
                "col1"      => "",
                "col2"      => "",
                "col3"      => "",
                "col4"      => "",
                "col5"      => "",
                "col6"      => property_exists($vm, 'businessService') ? $vm->businessService : "",
                "col7"      => $vm->name,
                "col8"      => "",
                "col9"      => property_exists($vm, 'memoryGB') ? $vm->memoryGB : "",
                "col10"     => "",
                "col11"     => "",

                "editable" => false,
                "iconCls"  => 'blank',
                "leaf"     => true
            );
        }
    }

    else if (preg_match("/^\/vmwarevms\/export$/", $node, $m)) {
        $simChassisTable = new HPSIMChassisTable();
        $simBladeTable   = new HPSIMBladeTable();

        $rows         = $simChassisTable->getNetworks();
        $distSwitches = array();
        foreach ($rows as $row) {
            $distSwitches[] = $row->distSwitchName;
        }

        // sort by dist switch
        function sortByDistSwitch($a, $b) {
            $aST = preg_match("/Sterling/", $a);
            $aDE = preg_match("/Denver/", $a);
            $aCH = preg_match("/Charlotte/", $a);

            $bST = preg_match("/Sterling/", $b);
            $bDE = preg_match("/Denver/", $b);
            $bCH = preg_match("/Charlotte/", $b);

            // Dist switch locations in the following order: Sterling,
            if (($aST && !$bST) || ($aDE && !$bDE && !$bST)) {
                return -1;
            } else if (($aDE && $bST) || ($aCH && !$bCH)) {
                return 1;
            } else {
                $aDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $a);
                $bDistSw = preg_replace("/Sterling |Denver |Charlotte /", "", $b);
                $r       = strcmp($aDistSw, $bDistSw);
                return $r;
            }
        }

        usort($distSwitches, 'sortByDistSwitch');

        // read in the esx hypers file
        $esxHypers = json_decode(unserialize(file_get_contents("../data/esx-hypers.ser")));

        $nodes = array();

        // sort by cluster name
        function sortByClusterName($a, $b) {
            return strcmp($a->ccrName, $b->ccrName);
        }

        $lastDistSwitch = "";
        $totalVMs       = 0;
        foreach ($distSwitches as $distSwitch) {
            $chassises = $simChassisTable->getBySwitchName($distSwitch);

            $lastChassisName = "";
            foreach ($chassises as $chassis) {
                // get the blades for this chassis
                $blades = $simBladeTable->getByChassisId($chassis->getId(), "slotNumber", "asc");

                // get the clusters from all the blades. We'll then need to sort by cluster
                // $clusters->cluster->blades[]
                $clusters = array();
                foreach ($blades as $blade) {
                    // skip non esx blades and those that have been decommed or disposed
                    if (!preg_match("/esx/", $blade->getDeviceName()) || $blade->getCmInstallStatus() == "Decommisioning" || $blade->getCmInstallStatus() == "Disposed") continue;

                    // insure we have this hyper in the structure
                    if (!property_exists($esxHypers, $blade->getFullDnsName())) {
                        continue;
                    }
                    // the hyper
                    $bName        = $blade->getFullDnsName();
                    $hyper        = $esxHypers->$bName;
                    $hyper->blade = $blade;

                    $clusters[] = $hyper;
                }
                usort($clusters, 'sortByClusterName');
                if (count($clusters) == 0) continue;

                // loop over the clusters
                $lastClusterName = "";
                foreach ($clusters as $cluster) {
                    if (preg_match("/ProLiant BL460c (G.*)/", $cluster->blade->getProductName(), $m)) {
                        $productName = $m[1];
                    } else {
                        $productName = $cluster->blade->getProductName();
                    }
                    $nodes[] = array(
                        "distSwitch"      => $distSwitch != $lastDistSwitch ? $distSwitch : "",
                        "chassisName"     => $chassis->getDeviceName() != $lastChassisName ? $chassis->getDeviceName() : "",
                        "clusterName"     => $cluster->ccrName != $lastClusterName ? $cluster->ccrName : "",
                        "hyperName"       => $cluster->blade->getDeviceName(),
                        "hyperModel"      => $productName,
                        "businessService" => "",
                        "vmName"          => "",
                        "totalMemGB"      => $cluster->memorySizeGB,
                        "availMemGB"      => $cluster->memoryAvailableGB,
                        "totalVMs"        => strval(count($cluster->vms)),
                    );
                    $totalVMs += count($hyper->vms);

                    $vms = $cluster->vms;
                    foreach ($vms as $vm) {
                        $nodes[] = array(
                            "distSwitch"      => "",
                            "chassisName"     => "",
                            "clusterName"     => "",
                            "hyperName"       => "",
                            "hyperModel"      => "",
                            "businessService" => property_exists($vm, 'businessService') ? $vm->businessService : "",
                            "vmName"          => $vm->name,
                            "totalMemGB"      => property_exists($vm, 'memoryGB') ? $vm->memoryGB : "",
                            "availMemGB"      => "",
                            "totalVMs"        => "",
                        );
                    }

                    $lastDistSwitch  = $distSwitch;
                    $lastChassisName = $chassis->getDeviceName();
                    $lastClusterName = $cluster->ccrName;
                }
            }
        }

        // one last row to show totals
        $nodes[] = array(
            "distSwitch"      => "",
            "chassisName"     => "",
            "clusterName"     => "",
            "hyperName"       => "",
            "hyperModel"      => "",
            "businessService" => "",
            "vmName"          => "Total VMs",
            "totalMemGB"      => "",
            "availMemGB"      => "",
            "totalVMs"        => $totalVMs,
        );

        $heads  = array("Dist Switch Name", "Chassis Name", "Cluster Name", "Hyper Name", "Hyper Model",
            "Business Service", "VM Name", "Total Mem(GB)", "Avail Mem(GB)", "Total VMs");
        $fields = array("distSwitch", "chassisName", "clusterName", "hyperName", "hyperModel",
            "businessService", "vmName", "totalMemGB", "availMemGB", "totalVMs");
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

function getNeumaticAPICurl($method, $vSphereSite="") {
    global $config, $output;

    $url = "https://neumatic.ops.neustar.biz/{$method}";
    if ($vSphereSite) {
        $url .= "?vSphereSite={$vSphereSite}";
    }

    $crypt = new \STS\Util\Obfuscation();
    $username = $config->ldapWebUsername;
    $password = $crypt->decrypt($config->ldapWebPassword);

    // create a Curl instance and authenticate to the NeuMatic API
    $curl = new \STS\Util\Curl();
    $curl->setUsernamePassword($username, $password);
    $curl->setUrl($url);
    $curl->send();
    $response = $curl->getBody();

    try {
        $json = json_decode($response);
    } catch (\ErrorException $e) {
        throw new \ErrorException("Unable to JSON decode the response from VMWare: " . $e->getMessage());
    }
    $json->url = $url;
    return $json;
}

function timing($startTime, $msg) {
    $endTime = time();
    $elapsedSecs = $endTime - $startTime;
    return sprintf("[%02d:%02d] %s", floor($elapsedSecs / 60), $elapsedSecs % 60, $msg);
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

    $colNames = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
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
    $excelFileName = "BladeRunner VMware Report {$dateStamp}.xlsx";

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
