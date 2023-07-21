<?php

include __DIR__ . "/../config/global.php";

try {
	// config
	$config = $GLOBALS['config'];

	// get the user 
	$userName = $_SERVER["PHP_AUTH_USER"];

	// get the requested report type
	$reportType = array_key_exists('reportType', $_POST) ? $_POST['reportType'] : $_GET['reportType'];

	switch ($reportType) {
		case "usage":
			include "get_report_usage.php";
			break;
        case "capacity":
            include "get_report_capacity.php";
            break;
		case "capmgmtsw":
			include "get_report_capmgmtsw.php";
			break;
		case "bladeInventoryRollup":
			include "get_report_bladeInventoryRollup.php";
			break;
		case "capmgmtchassistype":
			include "get_report_capmgmtchassistype.php";
			break;

        case "storageutilbyarray":
            include "get_report_storageutilbyarray.php";
            break;
        case "storageutilbysan":
            include "get_report_storageutilbysan.php";
            break;

        case "bladecountweekoverweek":
            include "get_report_bladecountweekoverweek.php";
            break;
        case "vmcountweekoverweek":
            include "get_report_vmcountweekoverweek.php";
            break;


		case "storagebyhost":
			$groupBy = "host";
			include "get_report_storagebyhost.php";
			break;
        case "storagebsbysan":
            include "get_report_storagebsbysan.php";
            break;
		case "storagebyhost":
			$groupBy = "host";
			include "get_report_storagebyhost.php";
			break;
		case "storagebyarray":
			$groupBy = "array";
			include "get_report_storagebyarray.php";
			break;

		case "chassisfirmware":
			$groupBy = "";
			include "get_report_chassisfirmware.php";
			break;
        case "xenhypervisors":
            $groupBy = "";
            include "get_report_xenhypervisors.php";
            break;
        case "esxhypervisors":
            $groupBy = "";
            include "get_report_esxhypervisors.php";
            break;
        case "vmwarevms":
            $groupBy = "";
            include "get_report_vmwarevms.php";
            break;
        case "vlansbychassis":
            $groupBy = "";
            include "get_report_vlansbychassis.php";
            break;
		default:
			include "get_report_usage.php";
			break;
	}
}

catch (Exception $e) {
    header('Content-Type: application/json');
	print json_encode(
		array(
			"returnCode" => 1,
			"errorCode"  => $e->getCode(),
			"errorText"  => $e->getMessage(),
			"errorFile"  => $e->getFile(),
			"errorLine"  => $e->getLine(),
			"errorStack" => $e->getTraceAsString()
		)
	);
	exit;
}

