<?php

// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D', 'E');
$lastColName = $colNames[count($colNames) - 1];

// Create new PHPExcel object
$excel = new PHPExcel();

// set the default styles 
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

$borderColor = '555555';

// style the header row and columns
$sheet->getStyle("A1:{$lastColName}1")->applyFromArray($headStyle);
for ($i = 0; $i < 4; $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}

// arrays of headers and assocatiated property names
$heads  = array("Inv Qty", "Model", "CPU", "Memory", "Site");
$fields = array("quantity", "model", "cpu", "memGB", "site");

// starting row; keeps track of the excel row we're writing out
$row = 1;

// write the column heads out
for ($i = 0; $i < count($heads); $i++) {
    $sheet->SetCellValue($colNames[$i] . $row, $heads[$i]);
}

for ($i = 0; $i < count($grid); $i++) {
    $g = $grid[$i];
    $row++;

    // set the cell borders
    $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray(
        array(
            'borders' => array(
                'top'    => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => $borderColor)
                ),
                'bottom' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => $borderColor)
                ),
                'left'   => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => $borderColor)
                ),
                'right'  => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => $borderColor)
                )
            )
        )
    );

    if ($g['divider']) {
        $x = array();
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('DDDDDD');
        for ($j = 0; $j < count($fields); $j++) {
            $x[$fields[$j]] = "";
        }
        $sheet->getRowDimension($row)->setRowHeight(5);
        writeRow($sheet, $x, $row);
    } else {
        // code the standard configs
        if ($g['configCss'] != "") {
            $sheet->getStyle("A{$row}:{$lastColName}{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFFF');
        }

        // code the old models
        if ($g['modelCss'] != "") {
            $sheet->getStyle("B{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF3300');
        }

        writeRow($sheet, $g, $row);
    }
}