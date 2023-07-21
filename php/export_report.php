<?php

include __DIR__ . "/../config/global.php";

// excel style array for the column heads
$headStyle = array(
	'font'      => array(
		'bold' => true
	),
	'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT),
	'borders'   => array(
		'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
	),
	'fill'      => array(
		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array('rgb' => '99CCFF')
	)
);

$alignRight = array(
	'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)
);

$switchStyle = array(
	'font'    => array('bold' => true),
	'borders' => array(
		'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
	),
	'fill'    => array(
		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array('rgb' => 'FFFFCC')
	)
);

$spacerStyle = array(
	'borders' => array(
		'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)
	),
	'fill'    => array(
		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array('rgb' => 'C0C0C0')
	)
);

$subtotalStyle = array(
	'borders' => array(
		'top'    => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => '000000')
		),
		'bottom' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => '000000')
		),
		'left' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => 'CCCCCC')
		),
		'right' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => 'CCCCCC')
		)
	),
	'fill'    => array(
		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array('rgb' => 'F5F5F5')
	),
	'font'    => array(
		'bold' => true
	)
);



$boldStyle = array(
	'font'    => array(
		'bold' => true
	)
);

$highlightStyle = array(
	'borders' => array(
		'allborders' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('rgb' => 'CCCCCC')
		)
	),
	'fill'    => array(
		'type'  => PHPExcel_Style_Fill::FILL_SOLID,
		'color' => array('rgb' => 'FDFFA0')
	)
);

try {
	// read the config file
	$config = $GLOBALS['config'];

	// get the requested report type
	$reportType = $_POST['reportType'];

	switch ($reportType) {
		case "usage":
			$title  = "Chassis Usage";
			$export = true;
			include "get_report_usage.php";
			break;
        case "capacity":
            $title  = "Chassis Capacity";
            $export = true;
            include "get_report_capacity.php";
            break;
		case "capmgmtsw":
			$title  = "Chassis Cap Mgmt Switch";
			$export = true;
			include "get_report_capmgmtsw.php";
			break;
		case "bladeInventoryRollup":
			$title  = "Blade Inventory Roll-up";
			$export = true;
			include "get_report_bladeInventoryRollup.php";
			break;
		case "capmgmtchassitype":
			$title  = "Chassis Cap Mgmt Chassis Type";
			$export = true;
			include "get_report_capmgmtchassistype.php";
			break;

        case "storageutilbyarray":
            $title  = "Storage Utilization By Array";
            $export = true;
            include "get_report_storageutilbyarray.php";
            break;
        case "storageutilbysan":
            $title  = "Storage Utilization By SAN";
            $export = true;
            include "get_report_storageutilbysan.php";
            break;

		case "storagebsbysan":
			$title  = "Business Service By SAN";
			$export = true;
			include "get_report_storagebsbysan.php";
			break;
		case "storagebyarray":
			$title  = "Storage by Array";
			$export = true;
			include "get_report_storagebyarray.php";
			break;

		case "chassisfirmware":
			$title = "Chassis Firmware";
			$export = true;
			include "get_report_chassisfirmware.php";
			break;
        case "vms":
            $title = "VMs";
            $export = true;
            include "get_report_vms.php";
            break;
        case "vlansbychassis":
            $title = "VLANs by Chassis";
            $export = true;
            include "get_report_vlansbychassis.php";
            break;
        case "xenhypervisors":
            $title = "Xen Hypervisors";
            $export = true;
            include "get_report_xenhypervisors.php";
            break;
        case "esxhypervisors":
            $title  = "ESX Hypervisors Capacity Report";
            $export = true;
            include "get_report_esxhypervisors.php";
            break;
        default:
			$title  = "Chassis_Usage";
			$export = true;
			include "get_report_usage.php";
			break;
	}

	$dateStamp     = date('Y-m-d');
	$excelFileName = "BladeRunner {$title} {$dateStamp}.xlsx";

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
}

catch (Exception $e) {
	print "<pre>";
	printf("%-12s => %s\n", "returnCode", 1);
	printf("%-12s => %s\n", "errorCode", $e->getCode());
	printf("%-12s => %s\n", "errorText", $e->getMessage());
	printf("%-12s => %s\n", "errorFile", $e->getFile());
	printf("%-12s => %s\n", "errorLine", $e->getLine());
	printf("%-12s => \n%s\n", "errorStack", $e->getTraceAsString());
	print "</pre>";
}

function writeRow(PHPExcel_Worksheet &$sheet, $g, $row) {
	global $fields, $colNames;
	$n = 0;

	foreach ($fields as $f) {
		if ($f == "spacer" || (array_key_exists('divider', $g) && $g['divider'])) {
            $sheet->SetCellValue($colNames[$n] . $row, "");
        } else if (preg_match("/mem[TPA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                array(
                    'fill' => array(
                        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                        'startcolor' => array(
                            'argb' => '334B83A8',
                        )
                    ),
                )
            );
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                    array(
                        'fill' => array(
                            'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                            'startcolor' => array(
                                'argb' => '4C4B83A8',
                            )
                        )
                    )
                );
            }
        } else if (preg_match("/cpu[CPA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                array(
                    'fill' => array(
                        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                        'startcolor' => array(
                            'argb' => '337F531B',
                        )
                    ),
                )
            );
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                    array(
                        'fill' => array(
                            'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                            'startcolor' => array(
                                'argb' => '4C7F531B',
                            )
                        )
                    )
                );
            }
        } else if (preg_match("/vms[CTA]/", $f)) {
            $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                array(
                    'fill' => array(
                        'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                        'startcolor' => array(
                            'argb' => '33063902',
                        )
                    ),
                )
            );
            if (array_key_exists('clusterSubtotal', $g) && $g['clusterSubtotal']) {
                $sheet->getStyle("{$colNames[$n]}{$row}")->applyFromArray(
                    array(
                        'fill' => array(
                            'type'       => PHPExcel_Style_Fill::FILL_SOLID,
                            'startcolor' => array(
                                'argb' => '4C063902',
                            )
                        )
                    )
                );
            }
        } else {
			$sheet->SetCellValue($colNames[$n] . $row, $g[$f]);
		}
		$n++;
	}
}

// Deprecated function
function writeFields(PHPExcel_Worksheet &$sheet, $g, $row) {
	global $fields, $colNames;

	$i = 0;
	foreach ($fields as $f) {
		$sheet->SetCellValue($colNames[$i] . $row, $g->$f);
		$i++;
	}
}

