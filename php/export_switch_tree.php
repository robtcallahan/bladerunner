<?php


include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
use STS\HPSIM\HPSIMVMTable;

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenSwitchTable;
use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenVmTable;

// excel style array for the column heads
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
    ),
    'fill'      => array(
        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array(
            'argb' => 'FFADD8E6',
        )
    ),
);

// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O');
$lastColName = $colNames[count($colNames) - 1];

try {
    // read the config file
    $config = $GLOBALS['config'];

    // get the passed parameters
    if (!array_key_exists('nodeId', $_POST)) throw new ErrorException("Node id not specified");
    if (!array_key_exists('nodeType', $_POST)) throw new ErrorException("Node type not specified");
    if (!array_key_exists('nodeDbId', $_POST)) throw new ErrorException("Node database id not specified");

    $nodeId   = $_POST['nodeId'];
    $nodeType = $_POST['nodeType'];
    $nodeDbId = $_POST['nodeDbId'];

    // Create new PHPExcel object
    $excel = new PHPExcel();

    // set the default styles
    $excel->setActiveSheetIndex(0);
    $sheet = $excel->getActiveSheet();

    // set all columns to autosize
    $sheet->getStyle("A1:{$lastColName}1")->applyFromArray($styleArray);
    for ($i = 0; $i < count($colNames); $i++) {
        $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
    }
    $sheet->getStyle('H1:H1000')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('I1:I1000')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

    // instantiate necessary classes
    $ss = new SanScreen();

    $ssSwitchTable = new SanScreenSwitchTable();
    $ssArrayTable  = new SanScreenArrayTable();
    $ssHostTable   = new SanScreenHostTable();
    $ssVmTable     = new SanScreenVmTable();

    $simChassisTable = new HPSIMChassisTable();
    $simBladeTable   = new HPSIMBladeTable();
    $simVmTable      = new HPSIMVMTable();

    $acdcAssetTable = new ACDCAssetTable();

    // keep track of chassis so we only right one line for each
    $trackChassis = array();

    // arrays of headers and assocatiated property names
    $heads  = array("Switch Name", "Switch Blade Number", "Object Type", "NodeName", "Blade Name", "VM Name", "Rack Location", "Port", "Size (GB)", "Business Service", "Subsystem", "Op Supp Mgr", "Op Supp Group", "Environment", "Install Status");
    $fields = array("swName", "bladeNumber", "objectType", "nodeName", "bladeName", "vmName", "rackLocation", "port", "size", "bs", "subsys", "opsSM", "opsSG", "env", "installStatus");

    // write the column heads out
    for ($i = 0; $i < count($heads); $i++) {
        $sheet->SetCellValue($colNames[$i] . 1, $heads[$i]);
    }
    $row = 2;

    // The Switch
    if ($nodeType === "switch" && preg_match("/^(\d+)$/", $nodeId, $m)) {
        $switchId = $m[1];
        $blades   = $ss->getSwitchBladesBySwitchId($switchId);

        for ($i = 0; $i < count($blades); $i++) {
            $b        = $blades[$i];
            $bladeNum = $b->blade;

            $switches = $ss->getSwitchesBySwitchIdAndSlotNumber($switchId, $bladeNum);
            $arrays   = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);
            $hosts    = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);

            $objects = array_merge($arrays, $hosts, $switches);

            usort($objects, 'sortByPortNum');
            $row = writeObjects($sheet, $row, $objects, $switchId, $bladeNum);
        }
    } // All switch arrays
    else if ($nodeType === "arrays-folder" && preg_match("/^(\d+)\/arrays$/", $nodeId, $m)) {
        $switchId = $m[1];

        $objects = $ss->getArraysBySwitchId($switchId);
        for ($i = 0; $i < count($objects); $i++) {
            $objects[$i]->objType = "array";
            $objects[$i]->port    = "";
            $objects[$i]->speed   = "";
        }
        $row = writeObjects($sheet, $row, $objects, $switchId, "");
    } // All switch hosts
    else if ($nodeType === "hosts-folder" && preg_match("/^(\d+)\/hosts$/", $nodeId, $m)) {
        $switchId = $m[1];

        $objects = $ss->getHostsBySwitchId($switchId);
        for ($i = 0; $i < count($objects); $i++) {
            $objects[$i]->objType = "host";
            $objects[$i]->port    = "";
            $objects[$i]->speed   = "";
        }
        $row = writeObjects($sheet, $row, $objects, $switchId, "");
    } // All switch blades
    else if ($nodeType === "swblades-folder" && preg_match("/^(\d+)\/blades$/", $nodeId, $m)) {
        $switchId = $m[1];

        $blades = $ss->getSwitchBladesBySwitchId($switchId);
        for ($i = 0; $i < count($blades); $i++) {
            $b        = $blades[$i];
            $bladeNum = $b->blade;

            $arrays   = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);
            $hosts    = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);
            $switches = $ss->getSwitchesBySwitchIdAndSlotNumber($switchId, $bladeNum);

            $objects = array_merge($arrays, $hosts, $switches);

            usort($objects, 'sortByPortNum');
            $row = writeObjects($sheet, $row, $objects, $switchId, $bladeNum);
        }
    } // Switch blade
    else if ($nodeType === "swblade-folder" && preg_match("/^(\d+)\/blades\/(\d+)$/", $nodeId, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $arrays   = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $hosts    = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $switches = $ss->getSwitchesBySwitchIdAndSlotNumber($switchId, $bladeNum);

        $objects = array_merge($arrays, $hosts, $switches);

        usort($objects, 'sortByPortNum');
        $row = writeObjects($sheet, $row, $objects, $switchId, $bladeNum);
    } // arrays folder in a switch blade
    else if ($nodeType === "swblade-arrays-folder" && preg_match("/^(\d+)\/blades\/(\d+)\/arrays$/", $nodeId, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $objects = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $row     = writeObjects($sheet, $row, $objects, $switchId, $bladeNum);
    } // hosts folder in a switch blade
    else if ($nodeType === "swblade-hosts-folder" && preg_match("/^(\d+)\/blades\/(\d+)\/hosts$/", $nodeId, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $objects = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $row     = writeObjects($sheet, $row, $objects, $switchId, $bladeNum);
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }


    $dateStamp     = date('Y-m-d');
    $excelFileName = "BladeRunner_Switch_Export_{$dateStamp}.xlsx";

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
} catch (Exception $e) {
    print "<pre>";
    printf("%-12s => %s\n", "returnCode", 1);
    printf("%-12s => %s\n", "errorCode", $e->getCode());
    printf("%-12s => %s\n", "errorText", $e->getMessage());
    printf("%-12s => %s\n", "errorFile", $e->getFile());
    printf("%-12s => %s\n", "errorLine", $e->getLine());
    printf("%-12s => \n%s\n", "errorStack", $e->getTraceAsString());
    print "</pre>";
}

function compileObjects($objects, $switchId, $bladeNum) {
    global $ss, $ssSwitchTable, $ssHostTable, $ssVmTable, $simChassisTable,
           $simBladeTable, $simVmTable, $acdcAssetTable,
           $trackChassis;

    $objectsArray = array();
    $sw           = $ssSwitchTable->getById($switchId);

    for ($i = 0; $i < count($objects); $i++) {
        $o = $objects[$i];

        $o->swName       = $sw->getName();
        $o->bladeNumber  = $bladeNum ? "Blade {$bladeNum}" : "";
        $o->objectType   = "";
        $o->nodeName     = "";
        $o->bladeName    = "";
        $o->vmName       = "";
        $o->rackLocation = "";
        //$o->port = "";
        $o->size          = "";
        $o->bs            = "";
        $o->subsys        = "";
        $o->opsSM         = "";
        $o->opsSG         = "";
        $o->env           = "";
        $o->installStatus = "";

        if ($o->objType === "host") {
            $ssHost   = $ssHostTable->getById($o->id);
            $simBlade = $simBladeTable->getByFqdn($ssHost->getName());

            if ($simBlade && $simBlade->getId()) {
                // Chassis
                $o->objectType = "Chassis";
                $simChassis    = $simChassisTable->getById($simBlade->getChassisId());
                $o->nodeName   = $simChassis->getFullDnsName() ? $simChassis->getFullDnsName() : $simChassis->getDeviceName();

                if (!array_key_exists($o->nodeName, $trackChassis)) {
                    $trackChassis[$o->nodeName] = 1;
                    $acdcAsset                  = $acdcAssetTable->getByName($simChassis->getFullDnsName());
                    if ($acdcAsset && $acdcAsset->getLocation()) {
                        $o->rackLocation = $acdcAsset->getLocation();
                    }
                    $objectsArray[] = $o;
                }

                // Blade
                $o->objectType = "Blade";
                $o->bladeName  = $simBlade->getFullDnsName() ? $simBlade->getFullDnsName() : $simBlade->getDeviceName();
                $o->vmName     = "";

                $o->size = round($ss->getTotalHostStorageByHostId($ssHost->getId()), 2);

                $o->bs            = $simBlade->getBusinessService();
                $o->subsys        = $simBlade->getSubsystem();
                $o->opsSM         = $simBlade->getOpsSuppMgr();
                $o->opsSG         = $simBlade->getOpsSuppGrp();
                $o->env           = $simBlade->getEnvironment();
                $o->installStatus = $simBlade->getCmInstallStatus();

                $objectsArray[] = $o;

                // get the VMs for this blade if present
                $vms = $simVmTable->getByBladeId($simBlade->getId());
                for ($j = 0; $j < count($vms); $j++) {
                    $vm = $vms[$j];

                    $o->objectType = "VM";
                    $o->vmName     = $vm->getFullDnsName() ? $vm->getFullDnsName() : $vm->getDeviceName();

                    $o->size = "";

                    $o->bs            = $vm->getBusinessService();
                    $o->subsys        = $vm->getSubsystem();
                    $o->opsSM         = $vm->getOpsSuppMgr();
                    $o->opsSG         = $vm->getOpsSuppGrp();
                    $o->env           = $vm->getEnvironment();
                    $o->installStatus = $vm->getCmInstallStatus();

                    $objectsArray[] = $o;
                }
            } else {
                $o->objectType = "Host";
                $o->nodeName   = $ssHost->getName();

                $shortName = preg_replace("/\..*$/", "", $ssHost->getName());
                $dciAsset  = $dciAssetTable->getByHostname($shortName);
                if ($dciAsset && $dciAsset->getLocation()) {
                    $o->rackLocation = $dciAsset->getLocation();
                }

                $o->size = round($ss->getTotalHostStorageByHostId($ssHost->getId()), 2);

                $o->bs            = $ssHost->getBusinessService();
                $o->subsys        = $ssHost->getSubsystem();
                $o->opsSM         = $ssHost->getOpsSuppMgr();
                $o->opsSG         = $ssHost->getOpsSuppGrp();
                $o->env           = $ssHost->getEnvironment();
                $o->installStatus = $ssHost->getCminstallStatus();

                $objectsArray[] = $o;

                // get the VMs for this hosts if present
                $vms = $ssVmTable->getByHostId($o->id);
                for ($j = 0; $j < count($vms); $j++) {
                    $vm = $vms[$j];

                    $o->objectType = "VM";
                    $o->vmName     = $vm->getName();

                    $o->size = "";

                    $o->bs            = $vm->getBusinessService();
                    $o->subsys        = $vm->getSubsystem();
                    $o->opsSM         = $vm->getOpsSuppMgr();
                    $o->opsSG         = $vm->getOpsSuppGrp();
                    $o->env           = $vm->getEnvironment();
                    $o->installStatus = $vm->getCminstallStatus();

                    $objectsArray[] = $o;
                }
            }
        } else if ($o->objType === "array") {
            $o->objectType = "Array";
            $o->nodeName   = $o->name;

            $shortName = preg_replace("/-.*$/", "", $o->name);
            $dciAsset  = $dciAssetTable->getByHostname($shortName);
            if ($dciAsset && $dciAsset->getLocation()) {
                $o->rackLocation = $dciAsset->getLocation();
            }

            $objectsArray[] = $o;
        } else if ($o->objType === "switch") {
            $o->objectType = "Switch";
            $o->nodeName   = $o->name;

            $dciAsset = $dciAssetTable->getByHostname($o->name);
            if ($dciAsset && $dciAsset->getLocation()) {
                $o->rackLocation = $dciAsset->getLocation();
            }

            $objectsArray[] = $o;
        }
    }
    return $objectsArray;
}

function writeObjects($sheet, $row, $objects, $switchId, $bladeNum) {
    global $ss, $ssSwitchTable, $ssHostTable, $ssVmTable, $simChassisTable,
           $simBladeTable, $simVmTable, $acdcAssetTable,
           $trackChassis;

    $sw = $ssSwitchTable->getById($switchId);

    for ($i = 0; $i < count($objects); $i++) {
        $o = $objects[$i];

        $o->swName      = $sw->getName();
        $o->bladeNumber = $bladeNum ? "Blade {$bladeNum}" : "";
        //$o->objectType = "";
        $o->nodeName     = "";
        $o->bladeName    = "";
        $o->vmName       = "";
        $o->rackLocation = "";
        //$o->port = "";
        $o->size          = "";
        $o->bs            = "";
        $o->subsys        = "";
        $o->opsSM         = "";
        $o->opsSG         = "";
        $o->env           = "";
        $o->installStatus = "";

        if ($o->objType === "host") {
            $ssHost     = $ssHostTable->getById($o->id);
            $simBlade   = $simBladeTable->getByFqdn($ssHost->getName());
            $simChassis = $simChassisTable->getByFullDnsName($ssHost->getName());

            if ($simChassis && $simChassis->getId()) {
                // Chassis
                $o->objectType = "Chassis";
                $o->nodeName   = $simChassis->getFullDnsName() ? $simChassis->getFullDnsName() : $simChassis->getDeviceName();

                //if (!array_key_exists($o->nodeName, $trackChassis)) {
                $trackChassis[$o->nodeName] = 1;
                $acdcAsset                  = $acdcAssetTable->getByName($simChassis->getFullDnsName());
                if ($acdcAsset && $acdcAsset->getLocation()) {
                    $o->rackLocation = $acdcAsset->getLocation();
                }
                writeFields($sheet, $o, $row);
                $row++;
                //}
            } else if ($simBlade && $simBlade->getId()) {
                // Blade
                $o->objectType = "Blade";
                $simChassis    = $simChassisTable->getById($simBlade->getChassisId());
                $o->nodeName   = $simChassis->getFullDnsName() ? $simChassis->getFullDnsName() : $simChassis->getDeviceName();
                $o->bladeName  = $simBlade->getFullDnsName() ? $simBlade->getFullDnsName() : $simBlade->getDeviceName();
                $o->vmName     = "";

                $o->size = round($ss->getTotalHostStorageByHostId($ssHost->getId()), 2);

                $o->bs            = $simBlade->getBusinessService();
                $o->subsys        = $simBlade->getSubsystem();
                $o->opsSM         = $simBlade->getOpsSuppMgr();
                $o->opsSG         = $simBlade->getOpsSuppGrp();
                $o->env           = $simBlade->getEnvironment();
                $o->installStatus = $simBlade->getCmInstallStatus();

                writeFields($sheet, $o, $row);
                $row++;

                // get the VMs for this blade if present
                $vms = $simVmTable->getByBladeId($simBlade->getId());
                for ($j = 0; $j < count($vms); $j++) {
                    $vm = $vms[$j];

                    $o->objectType = "VM";
                    $o->vmName     = $vm->getFullDnsName() ? $vm->getFullDnsName() : $vm->getDeviceName();

                    $o->size = "";

                    $o->bs            = $vm->getBusinessService();
                    $o->subsys        = $vm->getSubsystem();
                    $o->opsSM         = $vm->getOpsSuppMgr();
                    $o->opsSG         = $vm->getOpsSuppGrp();
                    $o->env           = $vm->getEnvironment();
                    $o->installStatus = $vm->getCmInstallStatus();

                    writeFields($sheet, $o, $row);
                    $row++;
                }
            } else {
                $o->objectType = "Host";
                $o->nodeName   = $ssHost->getName();
                $o->bladeName  = "";

                $shortName = preg_replace("/\..*$/", "", $ssHost->getName());
                $acdcAsset = $acdcAssetTable->getByName($ssHost->getName());
                if ($acdcAsset && $acdcAsset->getLocation()) {
                    $o->rackLocation = $acdcAsset->getLocation();
                }

                $o->size = round($ss->getTotalHostStorageByHostId($ssHost->getId()), 2);

                $o->bs            = $ssHost->getBusinessService();
                $o->subsys        = $ssHost->getSubsystem();
                $o->opsSM         = $ssHost->getOpsSuppMgr();
                $o->opsSG         = $ssHost->getOpsSuppGrp();
                $o->env           = $ssHost->getEnvironment();
                $o->installStatus = $ssHost->getCminstallStatus();

                writeFields($sheet, $o, $row);
                $row++;

                // get the VMs for this hosts if present
                $vms = $ssVmTable->getByHostId($o->id);
                for ($j = 0; $j < count($vms); $j++) {
                    $vm = $vms[$j];

                    $o->objectType = "VM";
                    $o->vmName     = $vm->getName();

                    $o->size = "";

                    $o->bs            = $vm->getBusinessService();
                    $o->subsys        = $vm->getSubsystem();
                    $o->opsSM         = $vm->getOpsSuppMgr();
                    $o->opsSG         = $vm->getOpsSuppGrp();
                    $o->env           = $vm->getEnvironment();
                    $o->installStatus = $vm->getCminstallStatus();

                    writeFields($sheet, $o, $row);
                    $row++;
                }
            }
        } else if ($o->objType === "array") {
            $o->objectType = "Array";
            $o->nodeName   = $o->name;

            $shortName = preg_replace("/-.*$/", "", $o->name);
            $acdcAsset = $acdcAssetTable->getByName($o->name);
            if ($acdcAsset && $acdcAsset->getLocation()) {
                $o->rackLocation = $acdcAsset->getLocation();
            }

            writeFields($sheet, $o, $row);
            $row++;
        } else if ($o->objType === "switch") {
            $o->objectType = "Switch";
            $o->nodeName   = $o->name;

            $acdcAsset = $acdcAssetTable->getByName($o->name);
            if ($acdcAsset && $acdcAsset->getLocation()) {
                $o->rackLocation = $acdcAsset->getLocation();
            }

            writeFields($sheet, $o, $row);
            $row++;
        }
    }
    return $row;
}

function writeFields(PHPExcel_Worksheet &$sheet, $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->$f);
        $i++;
    }
}

function sortByPortNum($a, $b) {
    if ($a->port > $b->port) return 1;
    else if ($a->port < $b->port) return -1;
    else return 0;
}
