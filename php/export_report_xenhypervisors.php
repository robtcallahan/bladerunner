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
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
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
$heads  = array("Dist SwitchName", "Chassis Name", "Hypervisor Name", "Hypervisor Model", "Business Service", "Total Mem (GB)", "Avail Mem (GB)", "Total VMs", "VMs");
$fields = array("distSwitch", "chassisName", "hyperName", "hyperModel", "businessService", "hyperMemGBTotal", "hyperMemGBFree", "totalVMs", "vms");

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

    writeRow($sheet, $g, $row);
}
