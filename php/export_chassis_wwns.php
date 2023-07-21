<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBlade;
use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeWWNTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMChassisWWN;
use STS\HPSIM\HPSIMChassisWWNTable;
use STS\HPSIM\HPSIMMgmtProcessorTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
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
$colNames = array('A', 'B', 'C', 'D', 'E');

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

    $sheet->getStyle("A1:" . $colNames[count($colNames) - 1] . "1")->applyFromArray($styleArray);
    for ($i = 0; $i < count($colNames); $i++) {
        $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
    }

    // instantiate necessary classes
    $simChassisTable = new HPSIMChassisTable();
    $simBladeTable   = new HPSIMBladeTable();
    $chassisWwnTable = new HPSIMChassisWWNTable();
    $bladeWwnTable   = new HPSIMBladeWWNTable();

    // arrays of headers and assocatiated property names
    $heads  = array("Chassis", "Slot", "Blade", "WWN", "IP Address");
    $fields = array("chassisName", "slotNumber", "bladeName", "wwn", "deviceAddress");

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
    } else if ($nodeType === "all") {
        $chassis = $simChassisTable->getAll("deviceName", "asc");
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }


    // excel row index; row 2 since the header row was output already
    $row = 2;

    // loop over the array of chassis for our output
    for ($i = 0; $i < count($chassis); $i++) {
        $ch = $chassis[$i];

        // first get a list of chassis WWNs and write those out first
        $wwns = $chassisWwnTable->getByChassisId($ch->getId());
        for ($j = 0; $j < count($wwns); $j++) {
            $wwn = $wwns[$j];
            $wwn->set("chassisName", $ch->getFullDnsName());
            $wwn->set("slotNumber", "-");
            $wwn->set("bladeName", "-");
            $wwn->set("wwn", $wwn->getWwn());
            $wwn->set("deviceAddress", "");

            writeChassisWwnFields($sheet, $wwn, $row);
            $row++;
        }

        // get a list of blades from the chassis
        $blades = $simBladeTable->getByChassisId($ch->getId(), "slotNumber", "asc");

        $targetSlot  = 0;      // next slot number that should be reported
        $currentSlot = null;  // keep track of the last slot that was populated so we can fill in the rest
        $blade       = null;
        // loop over the blades
        for ($j = 0; $j < count($blades); $j++) {
            $blade = $blades[$j];
            $targetSlot++;

            // denote empty slots
            while ($blade->getSlotNumber() > $targetSlot) {
                $blade->set("chassisName", $ch->getFullDnsName());
                $blade->set("slotNumber", $targetSlot);
                $blade->set("bladeName", "Empty");
                $blade->set("wwn", "");
                $blade->set("deviceAddress", "");

                writeBladeFields($sheet, $blade, $row);
                $row++;
                $targetSlot++;
            }

            // actual blade here
            $blade->set("chassisName", $ch->getFullDnsName());
            $blade->set("slotNumber", $targetSlot);
            $blade->set("bladeName", $blade->getFullDnsName() ? $blade->getFullDnsName() : $blade->getDeviceName());

            $wwns = $bladeWwnTable->getByBladeId($blade->getId());
            for ($k = 0; $k < count($wwns); $k++) {
                $wwn = $wwns[$k];
                $blade->set("wwn", $wwn->getWwn());
                writeBladeFields($sheet, $blade, $row);
                $row++;
            }

            $currentSlot = $blade->getSlotNumber();
        }

        // any remaining empty slots
        while ($currentSlot < 16) {
            $currentSlot++;
            $blade->set("chassisName", $ch->getFullDnsName());
            $blade->set("slotNumber", $currentSlot);
            $blade->set("bladeName", "Empty");
            $blade->set("wwn", "");
            $blade->set("deviceAddress", "");

            writeBladeFields($sheet, $blade, $row);
            $row++;
        }
    }

    $dateStamp     = date('Y-m-d');
    $excelFileName = "BladeRunner_WWNs_{$dateStamp}.xlsx";

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

function writeChassisWwnFields(PHPExcel_Worksheet &$sheet, HPSIMChassisWWN $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}

function writeBladeFields(PHPExcel_Worksheet &$sheet, HPSIMBlade $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}
