<?php

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenSwitchTable;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenVmTable;

include __DIR__ . "/../config/global.php";

try {
    // config
    $config = $GLOBALS['config'];

    // get the user and update the page view
    $userName = $_SERVER["PHP_AUTH_USER"];

    // set default time zone
    date_default_timezone_set("Greenwich");

    // instantiate
    $ss          = new SanScreen();
    $ssVmTable   = new SanScreenVmTable();
    $swTable     = new SanScreenSwitchTable();
    $ssHostTable = new SanScreenHostTable();

    // get the passed node
    $node = array_key_exists('node', $_POST) ? $_POST['node'] : 'root';

    $nodes = array();

    // Switches
    if ($node === "root") {
        $switches = $swTable->getAll("name", "asc");

        // header row
        $nodeObj = array(
            "id"      => "HEADER",
            "type"    => "header",
            "col1"    => "Switch Name",
            "col2"    => "Model",
            "col3"    => "Vendor",
            "col4"    => "Fabric ID",
            "col5"    => "Firware Version",
            "col6"    => "Status",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        for ($i = 0; $i < count($switches); $i++) {
            $sw = $switches[$i];

            // filter out any HP chassis switches
            if (preg_match("/DS-HP-FC/", $sw->getModel())) continue;

            $nodeObj = array(
                "id"      => $sw->getId(),
                "dbId"    => $sw->getId(),
                "type"    => "switch",
                "col1"    => $sw->getName(),
                "col2"    => $sw->getModel(),
                "col3"    => $sw->getVendor(),
                "col4"    => $sw->getFabricId(),
                "col5"    => $sw->getFirmwareVersion(),
                "col6"    => $sw->getStatus(),
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'array',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Switch subfolders
    else if (preg_match("/^(\d+)$/", $node, $m)) {
        $switchId = $m[1];

        $nodes = array(
            array(
                "id"      => "{$switchId}/arrays",
                "type"    => "arrays-folder",
                "col1"    => "Arrays",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$switchId}/hosts",
                "type"    => "hosts-folder",
                "col1"    => "Hosts",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$switchId}/blades",
                "type"    => "swblades-folder",
                "col1"    => "Switch Blades",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            )
        );
    } // Switch Hosts
    else if (preg_match("/^(\d+)\/hosts$/", $node, $m)) {
        $switchId = $m[1];

        $hosts = $ss->getHostsBySwitchId($switchId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/hosts/HEADER",
            "type"    => "header",
            "col1"    => "Host Name",
            "col2"    => "Business Service",
            "col3"    => "Subsystem",
            "col4"    => "Port",
            "col5"    => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($hosts); $i++) {
            $h = $hosts[$i];

            // check if host is in CMDB. If so, check for VMs
            $vms = array();
            if ($h->sysId != "") {
                $vms = $ssVmTable->getByHostId($h->id);
            }

            $nodeObj = array(
                "id"      => "{$switchId}/hosts/{$h->id}",
                "dbId"    => $h->id,
                "type"    => "host",
                "col1"    => $h->name,
                "col2"    => $h->businessService,
                "col3"    => $h->subsystem,
                "col4"    => $h->port,
                "col5"    => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => count($vms) > 0 ? false : true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Host VMs
    else if (preg_match("/^(\d+)\/hosts\/(\d+)$/", $node, $m)) {
        $switchId = $m[1];
        $hostId   = $m[2];

        $host = $ssHostTable->getById($hostId);

        $ssVmTable = new SanScreenVmTable();
        $vms       = $ssVmTable->getByHostId($host->getId());

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/hosts/{$hostId}/HEADER",
            "type"    => "header",
            "col1"    => "VM Name",
            "col2"    => "Business Service",
            "col3"    => "Subsystem",
            "col4"    => "Ops Supp Mgr",
            "col5"    => "Ops Supp Grp",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($vms); $i++) {
            $vm = $vms[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/hosts/{$hostId}/{$vm->getId()}",
                "dbId"    => $vm->getId(),
                "type"    => "host",
                "col1"    => $vm->getName(),
                "col2"    => $vm->getBusinessService(),
                "col3"    => $vm->getSubsystem(),
                "col4"    => $vm->getOpsSuppMgr(),
                "col5"    => $vm->getOpsSuppGrp(),
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Arrays
    else if (preg_match("/^(\d+)\/arrays$/", $node, $m)) {
        $switchId = $m[1];

        $arrays = $ss->getArraysBySwitchId($switchId);
        $nodes  = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/arrays/HEADER",
            "type"    => "header",
            "col1"    => "Array Name",
            "col2"    => "Serial Number",
            "col3"    => "Vendor",
            "col4"    => "Model",
            "col5"    => "Capacity (TB)",
            "col6"    => "Raw Capacity (TB)",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($arrays); $i++) {
            $a = $arrays[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/arrays/{$a->id}",
                "dbId"    => $a->id,
                "type"    => "array",
                "col1"    => $a->name,
                "col2"    => $a->serialNumber,
                "col3"    => $a->vendor,
                "col4"    => $a->model,
                "col5"    => round($a->capacityGB / 1024, 2),
                "col6"    => round($a->rawCapacityGB / 1024, 2),
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Blades
    else if (preg_match("/^(\d+)\/blades$/", $node, $m)) {
        $switchId = $m[1];

        $ss     = new SanScreen();
        $blades = $ss->getSwitchBladesBySwitchId($switchId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/blades/HEADER",
            "type"    => "header",
            "col1"    => "Blade Slot",
            "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($blades); $i++) {
            $b = $blades[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/blades/{$b->blade}",
                "dbId"    => $b->blade,
                "type"    => "swblade-folder",
                "col1"    => "Blade " . $b->blade,
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'folder',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Switch blade subfolders
    else if (preg_match("/^(\d+)\/blades\/(\d+)$/", $node, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $ss       = new SanScreen();
        $switches = $ss->getSwitchesBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $arrays   = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);
        $hosts    = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);

        $nodes = array();
        if (count($switches) > 0) {
            $nodes[] = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/switches",
                "type"    => "swblade-switches-folder",
                "col1"    => "ISL Switches",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }
        if (count($arrays) > 0) {
            $nodes[] = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/arrays",
                "type"    => "swblade-arrays-folder",
                "col1"    => "Arrays",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }
        if (count($hosts) > 0) {
            $nodes[] = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/hosts",
                "type"    => "swblade-hosts-folder",
                "col1"    => "Hosts",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }
    } // Switch Blade Switches
    else if (preg_match("/^(\d+)\/blades\/(\d+)\/switches$/", $node, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $ss       = new SanScreen();
        $switches = $ss->getSwitchesBySwitchIdAndSlotNumber($switchId, $bladeNum);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/blades/{$bladeNum}/switches/HEADER",
            "type"    => "header",
            "col1"    => "Switch Name",
            "col2"    => "Port",
            "col3"    => "Speed",
            "col4"    => "Status",
            "col5"    => "State",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($switches); $i++) {
            $h = $switches[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/switches/{$h->id}",
                "dbId"    => $h->id,
                "type"    => "swblade-switch",
                "col1"    => $h->name,
                "col2"    => $h->port,
                "col3"    => $h->speed,
                "col4"    => $h->status,
                "col5"    => $h->state,
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Blade Arrays
    else if (preg_match("/^(\d+)\/blades\/(\d+)\/arrays$/", $node, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $ss     = new SanScreen();
        $arrays = $ss->getArraysBySwitchIdAndSlotNumber($switchId, $bladeNum);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/blades/{$bladeNum}/arrays/HEADER",
            "type"    => "header",
            "col1"    => "Array Name",
            "col2"    => "Serial Number",
            "col3"    => "Vendor",
            "col4"    => "Model",
            "col5"    => "Port",
            "col6"    => "Speed",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($arrays); $i++) {
            $a = $arrays[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/arrays/{$a->id}",
                "dbId"    => $a->id,
                "type"    => "swblade-array",
                "col1"    => $a->name,
                "col2"    => $a->serialNumber,
                "col3"    => $a->vendor,
                "col4"    => $a->model,
                "col5"    => $a->port,
                "col6"    => $a->speed,
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Blade Hosts
    else if (preg_match("/^(\d+)\/blades\/(\d+)\/hosts$/", $node, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];

        $ss    = new SanScreen();
        $hosts = $ss->getHostsBySwitchIdAndSlotNumber($switchId, $bladeNum);

        $ssVmTable = new SanScreenVmTable();

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/blades/{$bladeNum}/hosts/HEADER",
            "type"    => "header",
            "col1"    => "Host Name",
            "col2"    => "Business Service",
            "col3"    => "Subsystem",
            "col4"    => "Port",
            "col5"    => "Speed",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($hosts); $i++) {
            $h = $hosts[$i];

            // check if host is in CMDB. If so, check for VMs
            $vms = array();
            if ($h->sysId != "") {
                $vms = $ssVmTable->getByHostId($h->id);
            }

            $nodeObj = array(
                "id"      => "{$switchId}/blades/{$bladeNum}/hosts/{$h->id}",
                "dbId"    => $h->id,
                "type"    => "swblade-host",
                "col1"    => $h->name,
                "col2"    => $h->businessService,
                "col3"    => $h->subsystem,
                "col4"    => $h->port,
                "col5"    => $h->speed,
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => count($vms) > 0 ? false : true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch Blade Host VMs
    else if (preg_match("/^(\d+)\/blades\/(\d+)\/hosts\/(\d+)$/", $node, $m)) {
        $switchId = $m[1];
        $bladeNum = $m[2];
        $hostId   = $m[3];

        $ssHostTable = new SanScreenHostTable();
        $host        = $ssHostTable->getById($hostId);

        $ssVmTable = new SanScreenVmTable();
        $vms       = $ssVmTable->getByHostId($host->getId());

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchId}/hosts/{$hostId}/HEADER",
            "type"    => "header",
            "col1"    => "VM Name",
            "col2"    => "Business Service",
            "col3"    => "Subsystem",
            "col4"    => "Ops Supp Mgr",
            "col5"    => "Ops Supp Grp",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($vms); $i++) {
            $vm = $vms[$i];

            $nodeObj = array(
                "id"      => "{$switchId}/hosts/{$hostId}/{$vm->getId()}",
                "dbId"    => $vm->getId(),
                "type"    => "host",
                "col1"    => $vm->getName(),
                "col2"    => $vm->getBusinessService(),
                "col3"    => $vm->getSubsystem(),
                "col4"    => $vm->getOpsSuppMgr(),
                "col5"    => $vm->getOpsSuppGrp(),
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($nodes);
    exit;
} catch (Exception $e) {
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

