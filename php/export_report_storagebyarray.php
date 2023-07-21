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
for ($i = 0; $i < count($colNames); $i++) {
    $sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
}

// arrays of headers and assocatiated property names
/** @var $dateFrom string */
/** @var $dateTo string */
$heads  = array("SAN Name", "Tier", "Array Name", "Array Model", "Business Service", $dateFrom, $dateTo, "Change (GB)");
$fields = array("sanName", "tier", "arrayName", "arrayModel", "businessService", "gbThen", "gbNow", "gbDelta");

// starting row; keeps track of the excel row we're writing out
$row = 1;

// write the column heads out
for ($i = 0; $i < count($heads); $i++) {
    $sheet->SetCellValue($colNames[$i] . $row, $heads[$i]);
}

for ($i = 0; $i < count($grid); $i++) {
    $g = $grid[$i];
    $row++;

    $sheet->getStyle("E{$row}:{$lastColName}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

    if ($g["type"] == "san" || $g["type"] == "tier" || $g["type"] == "array") {
        $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray(
            array(
                'borders' => array(
                    'top'  => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => '000000')
                    ),
                    'font' => array(
                        'bold' => true
                    )
                )
            )
        );
        if ($g['type'] == "san") {
            $sheet->getStyle("A{$row}:{$lastColName}{$row}")->applyFromArray(
                array(
                    'borders' => array(
                        'top'    => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('rgb' => '000000')
                        ),
                        'bottom' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                            'color' => array('rgb' => '000000')
                        ),
                        'font'   => array(
                            'bold' => true
                        )
                    )
                )
            );
        }
        writeRow($sheet, $g, $row);
    } else if ($g["gbDelta"] != 0) {
        $sheet->getStyle("E{$row}:{$lastColName}{$row}")->applyFromArray($highlightStyle);
        writeRow($sheet, $g, $row);
    } else {
        writeRow($sheet, $g, $row);
    }
}
