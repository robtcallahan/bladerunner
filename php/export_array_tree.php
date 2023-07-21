<?php

include __DIR__ . "/../config/global.php";

use STS\CMDB\CMDBServerTable;
use STS\CMDB\CMDBSubsystemTable;

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenHost;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenVm;
use STS\SANScreen\SANScreenVmTable;

use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMBladeTable;

use STS\Util\SysLog;

$sysLog = SysLog::singleton('BladeRunner');
$sysLog->debug();

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
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
$lastColName = $colNames[count($colNames) - 1];

try {
    // read the config file
    $config = $GLOBALS['config'];

    // get the passed parameters
    if (!array_key_exists('nodeType', $_POST)) {
        throw new ErrorException("Node type not specified");
    }
    if (!array_key_exists('nodeDbId', $_POST)) {
        throw new ErrorException("Node database id not specified");
    }

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

    // instantiate necessary classes
    $ss           = new SanScreen();
    $ssHostTable  = new SanScreenHostTable();
    $ssVmTable    = new SanScreenVmTable();
    $ssArrayTable = new SanScreenArrayTable();

    $bladeTable   = new HPSIMBladeTable();
    $chassisTable = new HPSIMChassisTable();

    $cmdbServerTable = new CMDBServerTable();
    $cmdbSubsysTable = new CMDBSubsystemTable();

    // arrays of headers and assocatiated property names
    $heads  = array("Array Name", "Chassis Name", "Host Name", "VM Name", "Model Number", "Business Service", "Subsystem", "Op Supp Mgr", "Environment", "Size(GB)");
    $fields = array("array", "chassis", "host", "vm", "modelNumber", "businessService", "subsystem", "opsSuppMgr", "environment", "sizeGB");

    // write the column heads out
    for ($i = 0; $i < count($heads); $i++) {
        $sheet->SetCellValue($colNames[$i] . 1, $heads[$i]);
    }

    // create an array of chassis, either one element if type == chassis, or a list of them if type == dist switch
    if ($nodeType === "array") {
        $ssArray = $ssArrayTable->getById($nodeDbId);
        $ssHosts = $ssHostTable->getByArrayId($nodeDbId);
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }


    // excel row index; row 2 since the header row was output already
    $row = 2;

    // loop over the array of chassis for our output
    for ($i = 0; $i < count($ssHosts); $i++) {
        $h = $ssHosts[$i];

        $h->set("array", $ssArray->getSerialNumber());
        $chassisName = "";

        $sysLog->debug("Checking " . $h->getCmdbName() . " to see if blade");
        // check if this is a blade host
        $blade = $bladeTable->getByFqdn($h->getCmdbName());
        if ($blade->getId()) {
            $sysLog->debug("Blade " . $blade->getDeviceName() . " found");
            $sysLog->debug("Trying to get chassis");
            $chassis = $chassisTable->getById($blade->getChassisId());
            if ($chassis->getDeviceName()) {
                $chassisName = $chassis->getDeviceName();
                $sysLog->debug("Chassis " . $chassisName . " found");
            } else {
                $sysLog->debug("Chassis not found");
            }
        } else {
            $sysLog->debug("Blade not found");
        }

        $h->set("chassis", $chassisName);
        $h->set("host", $h->getCmdbName());
        $h->set("vm", "");
        $h->set("sizeGB", $ss->getHostArrayStorageByArrayIdAndHostId($ssArray->getId(), $h->getId()));

        writeHostFields($sheet, $h, $row);
        $row++;

        $vms = $ssVmTable->getByHostId($h->getId());
        for ($j = 0; $j < count($vms); $j++) {
            $vm = $vms[$j];
            $vm->set("array", $ssArray->getSerialNumber());
            $vm->set("chassis", $chassisName);
            $vm->set("host", $h->getCmdbName());
            $vm->set("vm", $vm->getName());
            $vm->set("modelNumber", "");
            $vm->set("sizeGB", "");
            writeVmFields($sheet, $vm, $row);
            $row++;
        }
    }

    $dateStamp     = date('Y-m-d');
    $excelFileName = "BladeRunner_Array_Export_{$dateStamp}.xlsx";

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
} catch (\ErrorException $e) {
    print "<pre>";
    printf("%-12s => %s\n", "returnCode", 1);
    printf("%-12s => %s\n", "errorCode", $e->getCode());
    printf("%-12s => %s\n", "errorText", $e->getMessage());
    printf("%-12s => %s\n", "errorFile", $e->getFile());
    printf("%-12s => %s\n", "errorLine", $e->getLine());
    printf("%-12s => \n%s\n", "errorStack", $e->getTraceAsString());
    print "</pre>";
}

function writeHostFields(PHPExcel_Worksheet &$sheet, SANScreenHost $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}

function writeVmFields(PHPExcel_Worksheet &$sheet, SANScreenVm $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}
