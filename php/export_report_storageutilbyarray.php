<?php

// array of letters for use as column names
$colNames    = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
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
foreach (array('H', 'I', 'J', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(12);
    $sheet->getStyle("{$col}1")->getAlignment()->setWrapText(true);
}

// arrays of headers and assocatiated property names
$heads  = array("SAN Name", "Array Name", "Array Model",
    "Tier", "Status", "Raw TB", "Useable TB", "Provisioned TB",
    "Available TB @ 100% Used", "% Provisioned", "Available TB @ 90% Used"
);
$fields = array("sanName", "arrayName", "arrayModel",
    "tier", "status", "rawTb", "useableTb", "provisionedTb",
    "availableTb", "percentProvisioned", "availableAt90"
);

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
        $sheet->getStyle("F{$row}:K{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        // color code the % Provisioned column
        if ($g['percentProvisioned'] >= 90) {
            $sheet->getStyle("J{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF0000');
        } else if ($g['percentProvisioned'] >= 85) {
            $sheet->getStyle("J{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F9961F');
        } else if ($g['percentProvisioned'] >= 80) {
            $sheet->getStyle("J{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
        }

        // set a percent number format for column J
        $sheet->getStyle("J{$row}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_PERCENTAGE_00);
        // divide by 100 so that excel can convert to a percentage
        $g['percentProvisioned'] /= 100;

        // color code the TB columns
        $sheet->getStyle("G{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('00B4F2');
        $sheet->getStyle("H{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFA07A');
        $sheet->getStyle("I{$row}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFFF');

        writeRow($sheet, $g, $row);
    }

}
