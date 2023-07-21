<?php


// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
$lastColName = $colNames[count($colNames) - 1];

// Create new PHPExcel object
$excel = new PHPExcel();

// set the default styles 
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

// style the header row and columns
$sheet->getStyle("A1:{$lastColName}1")->applyFromArray($headStyle);
for ($i = 0; $i < 3; $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}
$sheet->getStyle('E1:H1')->getAlignment()->setWrapText(true);
for ($i = 3; $i < count($colNames); $i++) {
    /** @noinspection PhpParamsInspection */
    $sheet->getColumnDimension($colNames[$i])->setWidth(10);
}

// arrays of headers and assocatiated property names
$heads  = array("Dist Switch", "Chassis", "Chassis Type", "Blade Types", "Spares", "Empty Slots", "Blade Inventory", "Blades Reserved");
$fields = array("distSw", "chassis", "chassisType", "bladeTypes", "spare", "empty", "inventory", "reserved");

$row = 0;
for ($i = 0; $i < count($grid); $i++) {
    $g = $grid[$i];
    $row++;

    if ($g["type"] == "switch") {
        // show the switch totals
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray($switchStyle);
    } else if ($g["type"] == "total") {
        $sheet->getStyle("E{$row}:{$lastColName}{$row}")->applyFromArray($boldStyle);
    }
    writeRow($sheet, $g, $row);
}
