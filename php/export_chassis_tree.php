<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBlade;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeWWNTable;
use STS\HPSIM\HPSIMChassis;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMChassisWWNTable;
use STS\HPSIM\HPSIMMgmtProcessorTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
use STS\HPSIM\HPSIMVM;
use STS\HPSIM\HPSIMVMTable;

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
    if (!array_key_exists('nodeType', $_POST)) throw new ErrorException("Node type not specified");
    if (!array_key_exists('nodeDbId', $_POST)) throw new ErrorException("Node database id not specified");

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

    // set the comments and short description columns to a width of 4" and auto wrap
    $sheet->getStyle('J1:K500')->getAlignment()->setWrapText(true);
    $sheet->getColumnDimension('J')->setAutoSize(false);
    /** @noinspection PhpParamsInspection */
    $sheet->getColumnDimension('J')->setWidth(40);
    $sheet->getColumnDimension('K')->setAutoSize(false);
    /** @noinspection PhpParamsInspection */
    $sheet->getColumnDimension('K')->setWidth(40);

    // instantiate necessary classes
    $simChassisTable = new HPSIMChassisTable();
    $simBladeTable   = new HPSIMBladeTable();
    $simVmTable      = new HPSIMVMTable();

    // arrays of headers and assocatiated property names
    $heads  = array("Chassis", "Slot", "Blade", "VM", "Model", "Business Service", "Subsystem", "Op Supp Mgr", "Op Supp Group", "Comments", "Short Description", "CPU Phys x Cores", "Memory", "Environment", "Hardware Status");
    $fields = array("chassisName", "slotNumber", "bladeName", "vmName", "model", "businessService", "subsystem", "opsSuppMgr", "opsSuppGrp", "comments", "shortDescr", "cpu", "memory", "environment", "cmInstallStatus");

    // write the column heads out
    for ($i = 0; $i < count($heads); $i++) {
        $sheet->SetCellValue($colNames[$i] . 1, $heads[$i]);
    }

    // create an array of chassis, either one element if type == chassis, or a list of them if type == dist switch
    if ($nodeType === "distSwitch") {
        $chassis = $simChassisTable->getBySwitchName($nodeDbId);
    } else if ($nodeType === "chassis") {
        $chassis   = array();
        $chassis[] = $simChassisTable->getById($nodeDbId);
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }


    // excel row index; row 2 since the header row was output already
    $row = 2;

    // loop over the array of chassis for our output
    for ($i = 0; $i < count($chassis); $i++) {
        /** @var $ch HPSIMChassis */
        $ch = $chassis[$i];

        // get the chassis name and remove _crap if present
        $chassisName = $ch->getDeviceName();

        if (preg_match("/^([\w\d]+)_.*$/", $chassisName, $m)) {
            $chassisName = $m[1];
        }

        // get a list of blades from the chassis
        $blades = $simBladeTable->getByChassisId($ch->getId(), "slotNumber", "asc");

        $targetSlot  = 0;      // next slot number that should be reported
        $currentSlot = null;  // keep track of the last slot that was populated so we can fill in the rest
        $blade       = null;
        // loop over the blades
        for ($j = 0; $j < count($blades); $j++) {
            /** @var $blade HPSIMBlade */
            $blade = $blades[$j];
            $targetSlot++;

            // denote empty slots
            while ($blade->getSlotNumber() > $targetSlot) {
                $blade->set("chassisName", $ch->getFullDnsName());
                $blade->set("bladeName", "Empty");
                $blade->set("vmName", "");
                $blade->set("model", "");
                $blade->set("cpu", "");
                $blade->set("memory", "");
                $blade->set("slotNumber", $targetSlot);

                writeBladeFields($sheet, $blade, $row);
                $row++;
                $targetSlot++;
            }

            // actual blade here
            $blade->set("chassisName", $ch->getFullDnsName());
            $blade->set("bladeName", $blade->getFullDnsName() ? $blade->getFullDnsName() : $blade->getDeviceName());
            $blade->set("vmName", "");
            $blade->set("model", $blade->getProductName());
            $blade->set("cpu", $blade->getNumCpus() ? "{$blade->getNumCpus()} x {$blade->getNumCoresPerCpu()}" : "");
            $blade->set("memory", $blade->getFullDnsName() != "" ? $blade->getMemorySizeGB() : "");
            $blade->set("slotNumber", $targetSlot);

            writeBladeFields($sheet, $blade, $row);
            $row++;

            $vms = $simVmTable->getByBladeId($blade->getId());
            if (count($vms) > 0) {
                for ($k = 0; $k < count($vms); $k++) {
                    /** @var $vm HPSIMVM */
                    $vm = $vms[$k];

                    // assign chassis, blade and vm names
                    $vm->set("chassisName", $ch->getFullDnsName());
                    $vm->set("bladeName", $blade->getFullDnsName());
                    $vm->set("vmName", $vm->getFullDnsName());
                    $vm->set("model", "");
                    $vm->set("cpu", "");
                    $vm->set("memory", $vm->getMemorySize());
                    $vm->set("slotNumber", "");

                    writeVmFields($sheet, $vm, $row);
                    $row++;
                }
            }
            $currentSlot = $blade->getSlotNumber();
        }

        // any remaining empty slots
        while ($currentSlot < 16) {
            $currentSlot++;
            $blade->set("chassisName", $ch->getFullDnsName());
            $blade->set("bladeName", "Empty");
            $blade->set("vmName", "");
            $blade->set("model", "");
            $blade->set("cpu", "");
            $blade->set("memory", "");
            $blade->set("slotNumber", $currentSlot);

            writeBladeFields($sheet, $blade, $row);
            $row++;
        }
    }

    $dateStamp     = date('Y-m-d');
    $excelFileName = "BladeRunner_Chassis_Export_{$dateStamp}.xlsx";

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

function writeBladeFields(PHPExcel_Worksheet &$sheet, HPSIMBlade $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}

function writeVmFields(PHPExcel_Worksheet &$sheet, HPSIMVM $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}
