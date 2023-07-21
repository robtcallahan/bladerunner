<?php

include __DIR__ . "/../config/global.php";

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenHost;
use STS\SANScreen\SANScreenHostTable;


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
$colNames = array('A', 'B', 'C', 'D');

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
    $ss           = new SanScreen();
    $ssArrayTable = new SanScreenArrayTable();
    $ssHostTable  = new SanScreenHostTable();

    // arrays of headers and assocatiated property names
    $heads  = array("Array", "Host", "IP Address", "WWN");
    $fields = array("arrayName", "name", "ip", "wwn");

    // write the column heads out
    for ($i = 0; $i < count($heads); $i++) {
        $sheet->SetCellValue($colNames[$i] . 1, $heads[$i]);
    }

    // create an array of chassis, either one element if type == chassis, or a list of them if type == dist switch
    if ($nodeType === "array") {
        $arr    = $ssArrayTable->getById($nodeDbId);
        $arrays = array($arr);
    } else if ($nodeType === "all") {
        $arrays = $ssArrayTable->getAll("name", "asc");
    } else {
        throw new ErrorException("Unknown node type: {$nodeType}");
    }


    // excel row index; row 2 since the header row was output already
    $row = 2;

    // loop over the array of chassis for our output
    for ($i = 0; $i < count($arrays); $i++) {
        $ar = $arrays[$i];

        // get a list of hosts from the array
        $hosts = $ssHostTable->getByArrayId($ar->getId());

        // loop over the hosts
        for ($j = 0; $j < count($hosts); $j++) {
            $host = $hosts[$j];

            // get the host WWNs
            $ports = $ss->getHostPortsByHostId($host->getId());

            // loop over the ports
            for ($k = 0; $k < count($ports); $k++) {
                $p = $ports[$k];

                $host->set("arrayName", $ar->getName());
                $host->set("wwn", $p->hpWwn);

                writeFields($sheet, $host, $row);
                $row++;
            }
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

function writeFields(PHPExcel_Worksheet &$sheet, SANScreenHost $o, $row) {
    global $fields, $colNames;

    $i = 0;
    foreach ($fields as $f) {
        $sheet->SetCellValue($colNames[$i] . $row, $o->get($f));
        $i++;
    }
}

