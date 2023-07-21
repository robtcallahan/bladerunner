<?php


// array of letters for use as column names
$colNames    = array('A', 'B', 'C');
$lastColName = $colNames[count($colNames) - 1];

// Create new PHPExcel object
$excel = new PHPExcel();

// set the default styles 
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

// style the header row and columns
$sheet->getStyle("A1:{$lastColName}1")->applyFromArray($headStyle);
for ($i = 0; $i < count($colNames); $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}

// arrays of headers and assocatiated property names
$heads  = array("Qty", "Dist Switch", "Chassis Type");
$fields = array("quantity", "distSwitch", "chassisType");

// starting row; keeps track of the excel row we're writing out
$row = 1;

// write the column heads out
for ($i = 0; $i < count($heads); $i++) {
    $sheet->SetCellValue($colNames[$i] . $row, $heads[$i]);
}

for ($i = 0; $i < count($grid); $i++) {
    $g = $grid[$i];
    $row++;
    writeRow($sheet, $g, $row);
}
