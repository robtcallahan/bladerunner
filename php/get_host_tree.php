<?php

include __DIR__ . "/../config/global.php";

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeWWNTable;
use STS\HPSIM\HPSIMChassisTable;
use STS\HPSIM\HPSIMChassisWWNTable;
use STS\HPSIM\HPSIMMgmtProcessorTable;
use STS\HPSIM\HPSIMSwitchTable;
use STS\HPSIM\HPSIMVLANTable;
use STS\HPSIM\HPSIMVMTable;

use STS\CMDB\CMDBServerTable;

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenHostTable;

try {
    // config
    $config = $GLOBALS['config'];

    // get the user and update the page view
    $userName = $_SERVER["PHP_AUTH_USER"];

    // set default time zone
    date_default_timezone_set("Greenwich");

    // instantiate
    $sanscreen   = new SANScreen();
    $ssHostTable = new SANScreenHostTable();

    $simChassisTable  = new HPSIMChassisTable();
    $simBladeTable    = new HPSIMBladeTable();
    $simVmTable       = new HPSIMVmTable();
    $simSwitchTable   = new HPSIMSwitchTable();
    $simMgmtProcTable = new HPSIMMgmtProcessorTable();
    $chassisWwnTable  = new HPSIMChassisWWNTable();

    // get the passed node
    $node = array_key_exists('node', $_POST) ? $_POST['node'] : 'root';

    // All Hosts
    if ($node === "root") {
        $cmServerTable = new CMDBServerTable();
        $hosts         = $cmServerTable->getByBusinessServicesArray($config->coreBusServices);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "HEADER",
            "type"    => "header",
            "col1"    => "Host Name",
            "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($hosts); $i++) {
            $h = $hosts[$i];

            $nodeObj = array(
                "id"      => $h->getId(),
                "dbId"    => $h->getId(),
                "type"    => "host",
                "col2"    => $h->get('name'),
                "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'host',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Chassis
    else if (preg_match("/^(root-chassis)$|^([A-Za-z\s]+)$/", $node, $m)) {
        $switchName = array_key_exists(2, $m) ? $m[2] : null;

        if ($switchName) {
            $chassis = $simChassisTable->getBySwitchName($switchName);
        } else {
            $switchName = "all";
            $chassis    = $simChassisTable->getAll("deviceName", "asc");
        }

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/HEADER",
            "type"    => "header",
            "col1"    => "Chassis Name",
            "col2"    => "Product Name",
            "col3"    => "Slots Active",
            "col4"    => "Slots Available",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "HW Stat",
            "col8"    => "&nbsp;",
            "col9"    => $switchName == "all" ? "Dist Switch" : "Comments",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($chassis); $i++) {
            $c       = $chassis[$i];
            $hhCount = $simChassisTable->getHalfHeightBladeCount($c->getId());
            $fhCount = $simChassisTable->getFullHeightBladeCount($c->getId());

            $nodeObj = array(
                "id"       => "{$switchName}/{$c->getId()}",
                "dbId"     => $c->getId(),
                "type"     => "chassis",
                "col1"     => $c->getDeviceName(),
                "col2"     => $c->getProductName(),
                "col3"     => $hhCount + $fhCount,
                "col4"     => 16 - ($hhCount + ($fhCount * 2)),
                "col5"     => "&nbsp;",
                "col6"     => "&nbsp;",
                "col7"     => $c->getHwStatus(),
                "col8"     => "&nbsp;",
                "col9"     => $switchName == "all" ? $c->getDistSwitchName() : $c->getComments(),

                "editable" => true,

                "iconCls"  => 'chassis',
                "leaf"     => false
            );
            $nodes[] = $nodeObj;
        }
    } // Mgmt Processors, Switches and Blades
    else if (preg_match("/^([\w\s]+)\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $chassis = $simChassisTable->getById($chassisId);

        $nodes = array(
            array(
                "id"      => "{$switchName}/{$chassisId}/bs",
                "type"    => "folder",
                "col1"    => "Business Services/Subsystems",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$switchName}/{$chassisId}/mgmtProcs",
                "type"    => "folder",
                "col1"    => "Management Processors",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$switchName}/{$chassisId}/switches",
                "type"    => "folder",
                "col1"    => "Switches",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            )
        );

        if ($simChassisTable->getWwnCount($chassis->getId()) > 0) {
            $nodes [] = array(
                "id"      => "{$switchName}/{$chassisId}/wwns",
                "type"    => "folder",
                "col1"    => "WWNs",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }

        $nodes[] = array(
            "id"      => "{$switchName}/{$chassisId}/blades",
            "type"    => "folder",
            "col1"    => "Blades",
            "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
            "iconCls" => 'folder'
        );
    } // Business Services
    else if (preg_match("/^([\w\s]+)\/(\d+)\/bs$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $lobs = $simChassisTable->getLobsById($chassisId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/bs/HEADER",
            "type"    => "header",
            "col1"    => "Business Service",
            "col2"    => "Subsystem",
            "col3"    => "Ops Supp Mgr",
            "col4"    => "Ops Supp Grp",
            "col5"    => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($lobs); $i++) {
            $l = $lobs[$i];
            if ($l->subsystem == null) continue;

            $nodeObj = array(
                "id"      => "{$chassisId}/{$l->subsystem}",
                "dbId"    => $l->subsystem,
                "type"    => "subsystem",
                "col1"    => $l->businessService,
                "col2"    => $l->subsystem,
                "col3"    => $l->opsSuppMgr,
                "col4"    => $l->opsSuppGrp,
                "col5"    => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'lob',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Management Processors
    else if (preg_match("/^([\w\s]+)\/(\d+)\/mgmtProcs$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $mms = $simMgmtProcTable->getByChassisId($chassisId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/mgmtProcs/HEADER",
            "type"    => "header",
            "col1"    => "Device Name",
            "col2"    => "Product Name",
            "col3"    => "Version",
            "col4"    => "Device Address",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "HW Stat",
            "col8"    => "MP Stat",
            "col9"    => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($mms); $i++) {
            $mm = $mms[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/mgmtProcs/{$mm->getId()}",
                "dbId"    => $mm->getId(),
                "type"    => "mm",
                "col1"    => $mm->getDeviceName(),
                "col2"    => $mm->getProductName(),
                "col3"    => $mm->getVersion(),
                "col4"    => $mm->getDeviceAddress(),
                "col5"    => "&nbsp;",
                "col6"    => "&nbsp;",
                "col7"    => $mm->getHwStatus(),
                "col8"    => $mm->getMpStatus(),
                "col9"    => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Switches
    else if (preg_match("/^([\w\s]+)\/(\d+)\/switches$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $switches = $simSwitchTable->getByChassisId($chassisId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/switches/HEADER",
            "type"    => "header",
            "col1"    => "Device Name",
            "col2"    => "Product Name",
            "col3"    => "Version",
            "col4"    => "Device Address",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "HW Stat",
            "col8"    => "MP Stat",
            "col9"    => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($switches); $i++) {
            $sw = $switches[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/switches/{$sw->getId()}",
                "dbId"    => $sw->getId(),
                "type"    => "vc",
                "col1"    => $sw->getDeviceName(),
                "col2"    => $sw->getProductName(),
                "col3"    => $sw->getVersion(),
                "col4"    => $sw->getDeviceAddress(),
                "col5"    => "&nbsp;",
                "col6"    => "&nbsp;",
                "col7"    => $sw->getHwStatus(),
                "col8"    => $sw->getMpStatus(),
                "col9"    => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Chassis WWNs
    else if (preg_match("/^([\w\s]+)\/(\d+)\/wwns$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $wwns = $chassisWwnTable->getByChassisId($chassisId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/wwns/HEADER",
            "type"    => "header",
            "col1"    => "WWN",
            "col2"    => "Status",
            "col3"    => "Used By",
            "col4"    => "Speed",
            "col5"    => "Type",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($wwns); $i++) {
            $wwn = $wwns[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/wwns/{$wwn->getId()}",
                "dbId"    => $wwn->getId(),
                "type"    => "wwn",
                "col1"    => $wwn->getWwn(),
                "col2"    => $wwn->getStatus(),
                "col3"    => $wwn->getUsedBy(),
                "col4"    => $wwn->getSpeed(),
                "col5"    => $wwn->getType(),
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Blades
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $fullHeightSlots = array(); // keep track of full slots populated by full-height blades

        $blades = $simBladeTable->getByChassisId($chassisId, "slotNumber", "asc");

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/HEADER",
            "type"    => "header",
            "col1"    => "Device Name",
            "col2"    => "Product Name",
            "col3"    => "Slot Num",
            "col4"    => "Device Address",
            "col5"    => "# CPUs",
            "col6"    => "Mem(GB)",
            "col7"    => "HW Stat",
            "col8"    => "SW Stat",
            "col9"    => "Comments",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        $targetSlot  = 0;
        $currentSlot = null;
        for ($i = 0; $i < count($blades); $i++) {
            $b          = $blades[$i];
            $slotNumber = $b->getSlotNumber();
            $targetSlot++;

            // if full height blade, show the 2 occupied slots
            $slotNumberStr = $slotNumber;
            if (preg_match("/BL68.*G5/", $b->getProductName()))  // full height blade
            {
                $fullHeightSlots[$slotNumber + 8] = true;
                $slotNumberStr .= "," . ($slotNumber + 8);
            }

            // denote empty slots
            while ($slotNumber > $targetSlot) {
                if ($targetSlot <= 8 || ($targetSlot > 8 && !array_key_exists($targetSlot, $fullHeightSlots))) {
                    $nodeObj = array(
                        "id"      => "{$switchName}/{$chassisId}/blades/EMPTYSLOT-{$targetSlot}",
                        "dbId"    => null,
                        "type"    => "emptySlot",
                        "col1"    => "[ --- ]",
                        "col2"    => "&nbsp;",
                        "col3"    => $targetSlot,
                        "col4"    => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                        "iconCls" => 'blade',
                        "leaf"    => true
                    );
                    $nodes[] = $nodeObj;
                }
                $targetSlot++;
            }

            // actual blades
            $totalVmMemory = $simBladeTable->getTotalVmMemory($b->getId());
            $memory        = $totalVmMemory ? $totalVmMemory . "/" . round($b->getMemorySizeGB() / 1024 / 1024, 2) : round($b->getMemorySizeGB() / 1024 / 1024, 2);
            $nodeObj       = array(
                "id"       => "{$switchName}/{$chassisId}/blades/{$b->getId()}",
                "dbId"     => $b->getId(),
                "type"     => "blade",
                "col1"     => $b->getDeviceName(),
                "col2"     => $b->getProductName(),
                "col3"     => $slotNumberStr,
                "col4"     => $b->getDeviceAddress(),
                "col5"     => $b->getNumCpus() ? $b->getNumCpus() : "&nbsp;",
                "col6"     => $memory,
                "col7"     => $b->getHwStatus(),
                "col8"     => $b->getSwStatus(),
                "col9"     => $b->getComments(),

                "editable" => true,

                "iconCls"  => 'blade',
                "leaf"     => $simBladeTable->getVmCount($b->getId()) == 0 && $simBladeTable->getWwnCount($b->getId()) == 0 ? true : false
            );
            $nodes[]       = $nodeObj;
            $currentSlot   = $b->getSlotNumber();
        }

        // any remaining empty slots
        while ($currentSlot < 16) {
            $currentSlot++;

            if ($currentSlot <= 8 || ($currentSlot > 8 && !array_key_exists($currentSlot, $fullHeightSlots))) {
                $nodeObj = array(
                    "id"      => "{$switchName}/{$chassisId}/blades/EMPTYSLOT-{$currentSlot}",
                    "dbId"    => null,
                    "type"    => "emptySlot",
                    "col1"    => "[ --- ]",
                    "col2"    => "&nbsp;",
                    "col3"    => $currentSlot,
                    "col4"    => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                    "iconCls" => 'blade',
                    "leaf"    => true
                );
                $nodes[] = $nodeObj;
            }
        }
    } // Folders for WWNs, VMs, arrays (volumes)
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $blade = $simBladeTable->getById($bladeId);

        $nodes = array();

        if ($simBladeTable->getWwnCount($blade->getId()) > 0) {
            $nodes[] = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/wwns",
                "type"    => "folder",
                "col1"    => "WWNs",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }

        if ($simBladeTable->getVmCount($blade->getId()) > 0) {
            $nodes[] = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms",
                "type"    => "folder",
                "col1"    => "Virtual Hosts",
                "col2"    => $blade->getQueried(),
                "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }

        $ssHost = $ssHostTable->getByName($blade->getFullDnsName());
        if ($ssHost && $ssHost->getId()) {
            $nodes[] = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/arrays",
                "type"    => "folder",
                "col1"    => "Arrays",
                "col2"    => "&nbsp;",
                "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }
    } // Blade WWNs
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)\/wwns$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $simBladeWwnTable = new HPSIMBladeWWNTable();
        $wwns             = $simBladeWwnTable->getByBladeId($bladeId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/wwns/HEADER",
            "type"    => "header",
            "col1"    => "WWN",
            "col2"    => "Status",
            "col3"    => "Fabric Name",
            "col4"    => "Speed",
            "col5"    => "Port",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($wwns); $i++) {
            $wwn = $wwns[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/wwns/{$wwn->getId()}",
                "dbId"    => $wwn->getId(),
                "type"    => "wwn",
                "col1"    => $wwn->getWwn(),
                "col2"    => $wwn->getStatus(),
                "col3"    => $wwn->getFabricName(),
                "col4"    => $wwn->getSpeed(),
                "col5"    => $wwn->getPort(),
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Blade VMs
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)\/vms$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $b = $simBladeTable->getById($bladeId);

        $vms = $simVmTable->getByBladeId($bladeId);

        $nodes = array();

        if (count($vms) === 0) {
            echo json_encode($nodes);
            exit;
        }

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms/HEADER",
            "type"    => "header",
            "col1"    => "VM Name",
            "col2"    => "Subsystem",
            "col3"    => "OS Version",
            "col4"    => "Patch Level",
            "col5"    => "# CPUs",
            "col6"    => "Mem (GBs)",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;",
            "col9"    => "Comments",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($vms); $i++) {
            $vm = $vms[$i];

            if ($vm->getActive() == 1) {
                $iconCls = "active";
            } else {
                if ($b->getQueried() == 1) {
                    $iconCls = "inactive";
                } else {
                    $iconCls = "unknown";
                }
            }

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms/{$vm->getId()}",
                "dbId"    => $vm->getId(),
                "type"    => "vm",
                "col1"    => $vm->getFullDnsName(),
                "col2"    => $vm->getSubsystem(),
                "col3"    => $vm->getOsVersion(),
                "col4"    => $vm->getOsPatchLevel(),
                "col5"    => $vm->getNumberOfCpus(),
                "col6"    => $vm->getMemorySize(),
                "col7"    => "&nbsp;",
                "col8"    => "&nbsp;",
                "col9"    => $vm->getComments(),

                "active"  => $vm->getActive(),
                "queried" => $b->getQueried(),

                "iconCls" => $iconCls,
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Blade Arrays
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)\/arrays$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $b = $simBladeTable->getById($bladeId);

        $ssHost = $ssHostTable->getByName($b->getFullDnsName());

        $arrays = $sanscreen->getArraysByHostId($ssHost->getId());

        $nodes = array();

        if (count($arrays) === 0) {
            echo json_encode($nodes);
            exit;
        }

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/arrays/HEADER",
            "type"    => "header",
            "col1"    => "Array Name",
            "col2"    => "Model",
            "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($arrays); $i++) {
            $a = $arrays[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/arrays/{$a->getId()}",
                "dbId"    => $a->getId(),
                "type"    => "array",
                "col1"    => $a->getSerialNumber(),
                "col2"    => $a->getModel(),
                "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => "array",
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Blade Array Volumes
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)\/arrays\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];
        $arrayId    = $m[4];

        $b = $simBladeTable->getById($bladeId);

        $ssHost = $ssHostTable->getByName($b->getFullDnsName());

        $ss   = new SanScreen();
        $vols = $ss->getHostArrayVolumesByArrayIdAndHostId($arrayId, $ssHost->getId());

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/arrays/{$arrayId}/HEADER",
            "type"    => "header",
            "col1"    => "Volume Name",
            "col2"    => "Type",
            "col3"    => "Disk Type",
            "col4"    => "Redundancy",
            "col5"    => "Cap (GB)",
            "col6"    => "Raw (GB)",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($vols); $i++) {
            $v = $vols[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/arrays/{$arrayId}/{$v->id}",
                "dbId"    => $v->id,
                "type"    => "volume",
                "col1"    => $v->name,
                "col2"    => $v->type,
                "col3"    => $v->diskType,
                "col4"    => $v->redundancy,
                "col5"    => $v->capacityGB,
                "col6"    => $v->rawCapacityGB,
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'volume',
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
