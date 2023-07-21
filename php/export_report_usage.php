<?php


// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q');
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
$sheet->getStyle('E1:P1')->getAlignment()->setWrapText(true);
for ($i = 3; $i < count($colNames); $i++) {
    /** @noinspection PhpParamsInspection */
    $sheet->getColumnDimension($colNames[$i])->setWidth(10);
}

// the spacer column, K
/** @noinspection PhpParamsInspection */
$sheet->getColumnDimension('K')->setWidth(5);

// arrays of headers and assocatiated property names
$heads  = array("Site", "Dist Switch", "Chassis",
    "Slots Total", "Slots Prov", "Slots Spare", "Slots Resrvd", "Slots Inv", "Slots Empty", "Slots Avail",
    "spacer",
    "Blades Instal", "Blades Prov", "Blades Spare", "Blades Resrvd", "Blades Avail", "Blades MrkInv");
$fields = array("site", "distSw", "chassis",
    "slotsTotal", "slotsProv", "slotsSpare", "slotsRes", "slotsInv", "slotsEmpty", "slotsAvail",
    "spacer",
    "bladesInst", "bladesProv", "bladesSpare", "bladesRes", "bladesAvail", "bladesMrkInv");

$row = 0;
for ($i = 0; $i < count($grid); $i++) {
    $g = $grid[$i];
    $row++;

    if ($g["type"] == "site") {
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray($headStyle);
        $sheet->getStyle("K{$row}")->applyFromArray($spacerStyle);
        for ($c = 0; $c < 4; $c++) {
            $sheet->getColumnDimension($colNames[$c])->setAutoSize(true);
        }
        $sheet->getStyle("E{$row}:{$lastColName}{$row}")->getAlignment()->setWrapText(true);

        // write the column heads out
        for ($c = 0; $c < count($heads); $c++) {
            if ($heads[$c] != "spacer") $sheet->SetCellValue($colNames[$c] . $row, $heads[$c]);
        }
        $row++;

        // show the site totals
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray($switchStyle);
        $sheet->getStyle("K{$row}")->applyFromArray($spacerStyle);
        writeRow($sheet, $g, $row);
    } else if ($g["type"] == "switch") {
        // show the switch totals
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray($switchStyle);
        $sheet->getStyle("K{$row}")->applyFromArray($spacerStyle);
        writeRow($sheet, $g, $row);
    } else if ($g["type"] == "blank") {
        $sheet->getStyle("K{$row}")->applyFromArray($spacerStyle);
        writeRow($sheet, $g, $row);
    } else {
        $sheet->getStyle("K{$row}")->applyFromArray($spacerStyle);
        writeRow($sheet, $g, $row);
    }
}
