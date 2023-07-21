<?php

// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D');
$lastColName = $colNames[count($colNames) - 1];

// Create new PHPExcel object
$excel = new PHPExcel();

// set the default styles
$excel->setActiveSheetIndex(0);
$sheet = $excel->getActiveSheet();

// style the header row and columns
$borderColor = '555555';
$sheet->getStyle("A1:{$lastColName}1")->applyFromArray(
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
        ),
        'font'    => array(
            'bold' => true
        )
    )
);

// auto size all the columns
for ($i = 0; $i < count($colNames); $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}
// for the following columns, set a fixed width and wrap the heads
foreach (array('B', 'C', 'D') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(16);
    $sheet->getStyle("{$col}1")->getAlignment()->setWrapText(true);
}

// arrays of headers and assocatiated property names
$heads  = array("SAN / Tier", "Useable TB", "Provisioned TB", "Available TB @ 100% Used");
$fields = array("sanOrTier", "useableTb", "provisionedTb", "availableAt100");

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
        // bold the first column (SAN Name)
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        // format the floating point numbers
        $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // color code the % Provisioned column
        if ($g['percentProvisioned'] >= 90) {
            $sheet->getStyle("D{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
        } else if ($g['percentProvisioned'] >= 85) {
            $sheet->getStyle("D{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F9961F');
        } else if ($g['percentProvisioned'] >= 80) {
            $sheet->getStyle("D{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        }

        // color code the TB columns
        /*
        $sheet->getStyle("G{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('00B4F2');
        $sheet->getStyle("H{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFA07A');
        $sheet->getStyle("I{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFFF');
        */

        // indent Tier names
        if (preg_match("/^Tier/", $g['sanOrTier'])) {
            $g['sanOrTier'] = '      ' . $g['sanOrTier'];
        }

        writeRow($sheet, $g, $row);
    }

}
