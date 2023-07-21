<?php

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
$colNames    = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U');
$lastColName = $colNames[count($colNames) - 1];

// Create new PHPExcel object
$excel = new PHPExcel();

// set the default styles 
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

// style the header row and columns
$sheet->getStyle("A1:" . $colNames[count($colNames) - 1] . "1")->applyFromArray($headStyle);
for ($i = 0; $i < count($colNames); $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}


// arrays of headers and assocatiated property names
$heads  = array("Cluster Name", "Dist Switch Name", "Chassis Name", "Hyper Name", "Hyper Model",
    "Total Physical Mem Capacity(GB)", "Total Mem Provisioned(GB)", "Avail Physical Mem",
    "Total Mem Utilized (GB)", "Total Mem Utilized(%)", "Total Mem UnUtilized",
    "Total VM Capacity", "Total VMs Provisioned", "Total VMs Aailable",
    "Total Physical CPU Capacity", "Total CPU Provisioned", "Available Physical CPUs",
    "Total CPU Utilized", "Total CPU Utilized(%)", "Total CPU UnUtilized",
    "Slots Available");
$fields = array("clusterName", "distSwitch", "chassisName", "hyperName", "hyperModel",
    "memTotalGB", "memProvGB", "memAvailGB",
    "memUtilizedGB", "memUtilizedPct", "memUnutilizedGB",
    "vmsCapacity", "vmsTotal", "vmsAvailable",
    "cpuCapacity", "cpuProv", "cpuAvail",
    "cpuUtilized", "cpuUtilizedPct", "cpuUnutilized",
    "slotsAvailable");

// starting row; keeps track of the excel row we're writing out
$row = 1;

// write the column heads out
for ($i = 0; $i < count($heads); $i++) {
    $sheet->SetCellValue($colNames[$i] . $row, $heads[$i]);
}

foreach ($grid as $g) {
    $row++;
    if (array_key_exists('divider', $g) && $g['divider']) {
        $sheet->getStyle("A{$row}:" . $colNames[count($colNames) - 1] . $row)->applyFromArray($spacerStyle);
        $excel->getActiveSheet()->getRowDimension($row)->setRowHeight(5);
    }
    $sheet->getStyle("F{$row}:H{$row}")->applyFromArray($alignRight);
    if (array_key_exists('hyperMemGBTotal', $g) && $g['hyperMemGBTotal'] < 96) {
        $excelRow = $excel->getActiveSheet()->getStyle("F{$row}");
        $excelRow->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        $excelRow->getFill()
                 ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
                 ->getStartColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
    }

    writeESXRow($sheet, $g, $row);
}

function writeESXRow(PHPExcel_Worksheet &$sheet, $g, $row) {
    global $fields, $colNames;

    $BORDERSTYLE = array(
        'borders' => array(
            'outline' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN,
                'color' => array('argb' => '766f6e'),
                )
        )
    );
    $n = 0;

    foreach ($fields as $f) {
        if ($f == "spacer" || (array_key_exists('divider', $g) && $g['divider'])) {
            $sheet->SetCellValue($colNames[$n] . $row, "");
            $n++;
            continue;
        }

        if (preg_match("/mem[TPA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('89BAD5');
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('82B3CF');
            }
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray($BORDERSTYLE);
        } else if (preg_match("/cpu[CPA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('94B1B9');
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('91A5A5');
            }
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray($BORDERSTYLE);
        } else if (preg_match("/vms[CTA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('7CABBA');
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('6D9DA7');
            }
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray($BORDERSTYLE);
        }

        $sheet->SetCellValue("{$colNames[$n]}{$row}", $g[$f]);
        $n++;
    }
}
