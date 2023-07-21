<?php

use STS\HPSIM\HPSIMBladeTable;
use STS\HPSIM\HPSIMBladeExceptionTable;
use STS\HPSIM\HPSIMBladeReservationTable;

use STS\HPSIM\HPSIMBladeWWN;
use STS\HPSIM\HPSIMBladeWWNTable;

use STS\HPSIM\HPSIMChassisTable;

use STS\HPSIM\HPSIMChassisWWN;
use STS\HPSIM\HPSIMChassisWWNTable;

use STS\HPSIM\HPSIMMgmtProcessor;
use STS\HPSIM\HPSIMMgmtProcessorTable;

use STS\HPSIM\HPSIMSwitch;
use STS\HPSIM\HPSIMSwitchTable;

use STS\HPSIM\HPSIMVLAN;
use STS\HPSIM\HPSIMVLANTable;

use STS\HPSIM\HPSIMVMTable;
use STS\HPSIM\HPSIMVMExceptionTable;

use STS\Util\DistSwitchLookup;

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenHostTable;

include __DIR__ . "/../config/global.php";


try {
    // config
    $config = $GLOBALS['config'];

    // ServiceNow site
    $snSite   = $config->servicenow->site;
    $snConfig = $config->servicenow->{$snSite};
    $snHost   = $snConfig->server;

    // get the user and update the page view
    $userName = $_SERVER["PHP_AUTH_USER"];

    // set default time zone
    date_default_timezone_set("Greenwich");

    // instantiate
    $ss          = new SanScreen();
    $ssHostTable = new SanScreenHostTable();

    // get the passed node
    $node = array_key_exists('node', $_POST) ? $_POST['node'] : 'root';

    // data access objects
    $chassisTable = new HPSIMChassisTable();

    // Distribution Switches
    //if ($node === "root")
    if ($node == "root-distsw") {
        $distSw   = DistSwitchLookup::singleton();
        $networks = $chassisTable->getNetworks();

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "HEADER",
            "type"    => "header",
            "col1"    => "Distribution Switch Name",
            "col2"    => "Distribution Switch Device Name(s)",
            "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // request to show sterling first then charlotte; here's my attempt at that
        $list = "Sterling";
        $i    = -1;
        while (1) {
            $i++;
            if ($i >= count($networks)) {
                if ($list == "Charlotte") break;
                $i = 0;
                if ($list == "Sterling") {
                    $list = "Denver";
                } else {
                    $list = "Charlotte";
                }
            }
            $n      = $networks[$i];
            $swName = $n->distSwitchName;
            if (!preg_match("/^{$list}/", $swName)) continue;

            $nodeObj = array(
                "id"      => $swName,
                "dbId"    => $swName,  // normally this would be the id of the table, but we don't have a dist switch table...yet
                "type"    => "distSwitch",
                "col1"    => $swName,

                "col2"    => implode(",", $distSw->getSwitchDeviceByName($swName)),
                "col3"    => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'network',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }

        // add Unassigned
        $nodeObj = array(
            "id"      => "Unassigned",
            "dbId"    => "Unassigned",
            "type"    => "distSwitch",
            "col1"    => "Unassigned",
            "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "iconCls" => 'network',
            "leaf"    => false
        );
        $nodes[] = $nodeObj;
    } // Chassis
    else if (preg_match("/^(root-chassis)$|^([A-Za-z\s]+)$/", $node, $m)) {
        $switchName = array_key_exists(2, $m) ? $m[2] : null;

        if ($switchName) {
            $chassis = $chassisTable->getBySwitchName($switchName);
        } else {
            $switchName = "all";
            $chassis    = $chassisTable->getAll("deviceName", "asc");
        }

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/HEADER",
            "type"    => "header",
            "col1"    => "Chassis Name",
            "col2"    => "Product Name",
            "col3"    => "Slots Active",
            "col3tip" => "Indicates the number of blades installed and powered on.",
            "col4"    => "Slots Empty",
            "col4tip" => "Indicates the number of slots that are empty.",
            "col5"    => "MM Ver",
            "col6"    => "VC Ver",
            "col7"    => "HW Stat",
            "col8"    => "&nbsp;",
            "col9"    => $switchName == "all" ? "Dist Switch" : "Comments",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        $mgmtProcTable = new HPSIMMgmtProcessorTable();
        $switchTable   = new HPSIMSwitchTable();

        // data rows
        for ($i = 0; $i < count($chassis); $i++) {
            $c = $chassis[$i];

            $mms = $mgmtProcTable->getByChassisId($c->getId());
            if (count($mms) > 1) {
                $mmFirmware = $mms[0]->getVersion() ? $mms[0]->getVersion() : $mms[1]->getVersion();
            } else {
                $mmFirmware = "N/A";
            }

            $switches = $switchTable->getByChassisId($c->getId());
            if (count($switches) > 0) {
                $swFirmware = $switches[0]->getVersion() ? $switches[0]->getVersion() : $switches[1]->getVersion();
            } else {
                $swFirmware = "N/A";
            }

            $hhCount = $chassisTable->getHalfHeightBladeCount($c->getId(), $on = false);
            $fhCount = $chassisTable->getFullHeightBladeCount($c->getId(), $on = false);

            $nodeObj = array(
                "id"       => "{$switchName}/{$c->getId()}",
                "dbId"     => $c->getId(),
                "type"     => "chassis",
                "col1"     => $c->getDeviceName(),
                "col2"     => preg_replace("/ Enclosure/", "", $c->getProductName()),
                "col3"     => $hhCount + ($fhCount * 2),
                "col4"     => 16 - ($hhCount + ($fhCount * 2)),
                "col5"     => $mmFirmware,
                "col6"     => $swFirmware,
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

        $chassis = $chassisTable->getById($chassisId);

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

        if ($chassisTable->getWwnCount($chassisId) > 0) {
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

        $lobs = $chassisTable->getLobsById($chassisId);

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

        $mgmtProcTable = new HPSIMMgmtProcessorTable();
        $mms           = $mgmtProcTable->getByChassisId($chassisId);

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
            /** @var $mm HPSIMMgmtProcessor */
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

        $switchTable = new HPSIMSwitchTable();
        $switches    = $switchTable->getByChassisId($chassisId);

        $vlanTable = new HPSIMVLANTable();

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
            /** @var $sw HPSIMSwitch */
            $sw = $switches[$i];

            // check for VLAN data on this switch
            $vlans = $vlanTable->getBySwitchId($sw->getId());

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
                "leaf"    => count($vlans) > 0 ? false : true
            );
            $nodes[] = $nodeObj;
        }
    } // Switch VLANs
    else if (preg_match("/^([\w\s]+)\/(\d+)\/switches\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $switchId   = $m[3];

        $vlanTable = new HPSIMVLANTable();
        $vlans     = $vlanTable->getBySwitchId($switchId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/switches/{$switchId}/HEADER",
            "type"    => "header",
            "col1"    => "VLAN Name",
            "col2"    => "sharedUplinkSet",
            "col3"    => "Status",
            "col4"    => "VLAN ID",
            "col5"    => "Native VLAN",
            "col6"    => "Private",
            "col7"    => "Preferred Speed",
            "col8"    => "&nbsp;",
            "col9"    => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($vlans); $i++) {
            /** @var $v HPSIMVLAN */
            $v = $vlans[$i];

            $nodeObj = array(
                "id"      => "{$switchName}/{$chassisId}/switches/{$switchId}/{$v->getId()}",
                "dbId"    => $v->getId(),
                "type"    => "vlan",
                "col1"    => $v->getName(),
                "col2"    => $v->getSharedUplinkSet(),
                "col3"    => $v->getStatus(),
                "col4"    => $v->getVlanId(),
                "col5"    => $v->getNativeVlan(),
                "col6"    => $v->getPrivate(),
                "col7"    => $v->getPreferredSpeed(),
                "col8"    => "&nbsp;",
                "col9"    => "&nbsp;",

                "iconCls" => 'network',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Chassis WWNs
    else if (preg_match("/^([\w\s]+)\/(\d+)\/wwns$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];

        $wwnTable = new HPSIMChassisWWNTable();
        $wwns     = $wwnTable->getByChassisId($chassisId);

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
            /** @var $wwn HPSIMChassisWWN */
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

        $bladeTable = new HPSIMBladeTable();
        $blades     = $bladeTable->getByChassisId($chassisId, "slotNumber", "asc");

        $resTable    = new HPSIMBladeReservationTable();
        $bladeResObj = (object)array();

        $bladeExceptionTable = new HPSIMBladeExceptionTable();

        $response = curlExec($config->hypersWsUrl);
        try {
            $json = json_decode($response);
        } catch (\Exception $e) {
            throw new \ErrorException("Could not JSON decode the response. Error: {$e->getMessage()} response: " . print_r($response, true));
        }

        $coreHypers = is_object($json) && property_exists($json, 'hypervisors') ? $json->hypervisors : null;
        $nodes      = array();

        // header row
        $nodeObj = array(
            "id"      => "{$switchName}/{$chassisId}/blades/HEADER",
            "type"    => "header",
            "col1"    => "Device Name",
            "col2"    => "Product Name",
            "col3"    => "Slot Num",
            "col4"    => "Device Address",
            "col5"    => "Phys x Cores",
            "col6"    => "Mem(GB)",
            "col7"    => "HW Stat",
            "col8"    => "Pwr'd On",
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
            $b = $blades[$i];

            $slotNumber = $b->getSlotNumber();
            $targetSlot++;

            // check for a reservation
            $bladeRes = $resTable->getOpenByBladeId($b->getId());

            // check for exception
            $bladeException = $bladeExceptionTable->getByBladeId($b->getId());
            $hyperException = $bladeExceptionTable->getQueryErrorByBladeId($b->getId());

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
                        "id"       => "{$switchName}/{$chassisId}/blades/EMPTYSLOT-{$targetSlot}",
                        "dbId"     => null,
                        "type"     => "emptySlot",
                        "col1"     => "[ --- ]",
                        "col2"     => "&nbsp;",
                        "col3"     => $targetSlot,
                        "col4"     => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                        "bladeRes" => $bladeResObj,

                        "iconCls"  => 'blade-empty',
                        "leaf"     => true
                    );
                    $nodes[] = $nodeObj;
                }
                $targetSlot++;
            }

            // actual blades
            $totalVmMemory = $bladeTable->getTotalVmMemory($b->getId());

            #$memory = $totalVmMemory ? $totalVmMemory . "/" . round($b->getMemorySizeGB()/1024/1024, 2) : round($b->getMemorySizeGB()/1024/1024, 2);
            $memory = $totalVmMemory ? $totalVmMemory . "/" . $b->getMemorySizeGB() : $b->getMemorySizeGB();

            // check for USE* as deviceName
            $deviceName        = $b->getDeviceName();
            $isCorpVMwareBlade = false;
            //if (preg_match("/[Uu][Ss][Ee].*/", $b->getDeviceName())) {
            if ($b->getSerialNumber() == $b->getDeviceName()) {
                if ($b->getCmdbName() != null) {
                    $url = "https://{$snHost}/nav_to.do?uri=cmdb_ci_server.do?sys_id={$b->getSysId()}";
                    if (preg_match("/^(.*?)\./", $b->getCmdbName(), $m)) {
                        $link       = "<span title='Click to go to CMDB entry' style='text-decoration:underline;padding:0;' " .
                            "onclick='window.open(\"{$url}\", \"_blank\");'>" . $b->getDeviceName() . "</span>";
                        $deviceName = "{$link} <span style='font-size:8pt;padding:0;'>(" . strtolower($m[1]) . ")</span>";
                        if (preg_match("/vmlan/", strtolower($m[1]))) {
                            $isCorpVMwareBlade = true;
                        }
                    } else {
                        $link       = "<span title='Click to go to CMDB entry' style='text-decoration:underline;padding:0;' " .
                            "onclick='window.open(\"{$url}\", \"_blank\");'>" . $b->getDeviceName() . "</span>";
                        $deviceName = "{$link} <span style='font-size:8pt;padding:0;'>(" . strtolower($b->getCmdbName()) . ")</span>";
                        if (preg_match("/vmlan/", strtolower($b->getCmdbName()))) {
                            $isCorpVMwareBlade = true;
                        }
                    }
                } else {
                    $deviceName = "{$b->getDeviceName()} (<span style='font-size:8pt;'>N/A</span>)";
                }
            } else {
                if ($b->getCmdbName() != null) {
                    $url        = "https://{$snHost}/nav_to.do?uri=cmdb_ci_server.do?sys_id={$b->getSysId()}";
                    $link       = "<span title='Click to go to CMDB entry' style='text-decoration:underline;padding:0;' " .
                        "onclick='window.open(\"{$url}\", \"_blank\");'>{$b->getDeviceName()}</span>";
                    $deviceName = $link;
                } else {
                    $deviceName = $b->getDeviceName();
                }
            }

            $exceptionDescr = "";
            $iconCls        = "blade-active";

            // exception
            if ($bladeException->getId() != "") {
                $iconCls        = "blade-active-excep";
                $exceptionDescr = $bladeException->getExceptionTypeDescr();
            } else {
                $iconCls = "blade-active";
            }

            // inventory
            if ($b->getIsInventory()) {
                if ($bladeException->getId() != "") {
                    $iconCls        = "blade-inventory-excep";
                    $exceptionDescr = $bladeException->getExceptionTypeDescr();
                } else {
                    $iconCls = "blade-inventory";
                }
            }
            // reserved
            if ($bladeRes && $bladeRes->getId() != "") {
                if ($bladeException->getId() != "") {
                    $iconCls        = "blade-reserved-excep";
                    $exceptionDescr = $bladeException->getExceptionTypeDescr();
                } else {
                    $iconCls = "blade-reserved";
                }
                if ($bladeRes->getTaskSysId()) {
                    $url        = "https://{$snHost}/nav_to.do?uri=sc_task.do?sys_id={$bladeRes->getTaskSysId()}";
                    $ticketNum  = preg_replace("/TASK0+/", "", $bladeRes->getTaskNumber());
                    $deviceName = "{$b->getDeviceName()} <span style='font-size:8pt;'>[{$bladeRes->getProjectName()} <span title='Click to go to ServiceNow ticket' style='text-decoration: underline;' onclick='window.open(\"{$url}\", \"_blank\");'>{$ticketNum}</span>]</span>";
                } else {
                    $deviceName = "{$b->getDeviceName()} <span style='font-size:8pt;'>[{$bladeRes->getProjectName()}]</span>";
                }
            }
            // spare
            if ($b->getIsSpare()) {
                $iconCls = "blade-spare";
            }

            /*
            // hyper not queried
            if (!$b->getQueried()) {
                $deviceName = "{$b->getDeviceName()} <span class='not-queried'>Hyper not queried</span>";
            }
            */

            $nodeObj     = array(
                "id"             => "{$switchName}/{$chassisId}/blades/{$b->getId()}",
                "dbId"           => $b->getId(),
                "type"           => "blade",
                "col1"           => $deviceName,
                "col2"           => $b->getProductName(),
                "col3"           => $slotNumberStr,
                "col4"           => $b->getDeviceAddress(),
                "col5"           => $b->getNumCpus() ? "{$b->getNumCpus()} x {$b->getNumCoresPerCpu()}" : "&nbsp;",
                "col6"           => $memory,
                "col7"           => $b->getHwStatus(),
                "col8"           => $b->getPowerStatus() == "On" ? "Normal" : "Critical",
                "col8type"       => "power-status",
                "col9"           => $b->getComments(),

                "isHyper"        => preg_match("/kvm|xm/", $b->getDeviceName()) ? true : false,
                "isCoreHyper"    => $coreHypers != null && property_exists($coreHypers, $b->getFullDnsName()) ? true : false,
                "hyperQueried"   => $b->getQueried() ? true : false,
                "queryError"     => $hyperException->getId() ? true : false,
                "bladeRes"       => $bladeRes->toObject(),
                "bladeException" => $exceptionDescr,

                "editable"       => true,

                "href"           => "",  // currently not using this. keeping to remind us of the feature :-)

                "iconCls"        => $iconCls,
                "leaf"           => $bladeTable->getVmCount($b->getId()) == 0 && $bladeTable->getWwnCount($b->getId()) == 0 ? true : false
            );
            $nodes[]     = $nodeObj;
            $currentSlot = $b->getSlotNumber();
        }

        // any remaining empty slots
        while ($currentSlot < 16) {
            $currentSlot++;

            if ($currentSlot <= 8 || ($currentSlot > 8 && !array_key_exists($currentSlot, $fullHeightSlots))) {
                $nodeObj = array(
                    "id"       => "{$switchName}/{$chassisId}/blades/EMPTYSLOT-{$currentSlot}",
                    "dbId"     => null,
                    "type"     => "emptySlot",
                    "col1"     => "[ --- ]",
                    "col2"     => "&nbsp;",
                    "col3"     => $currentSlot,
                    "col4"     => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                    "bladeRes" => $bladeResObj,

                    "iconCls"  => 'blade-empty',
                    "leaf"     => true
                );
                $nodes[] = $nodeObj;
            }
        }
    } // Folders for WWNs, VMs, arrays (volumes)
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $bladeTable = new HPSIMBladeTable();
        $blade      = $bladeTable->getById($bladeId);

        $vmTable = new HPSIMVMTable();
        $vms     = $vmTable->getByBladeId($bladeId);

        $nodes = array();

        if ($bladeTable->getWwnCount($bladeId) > 0) {
            $nodes[] = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/wwns",
                "type"    => "folder",
                "col1"    => "WWNs",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }

        //if ($bladeTable->getVmCount($bladeId) > 0)
        //if (preg_match("/xm|kvm|esx/", $blade->getDeviceName()) || preg_match("/vmlan/i", $b->getCmdbName())) {
        if (count($vms) > 0) {
            $nodes[] = array(
                "id"      => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms",
                "type"    => "folder",
                "col1"    => "Virtual Hosts",
                #"col2"    => !$blade->getQueried() ? 'Xen Master not queried' : "",
                "col2"    => "",
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

        $wwnTable = new HPSIMBladeWWNTable();
        $wwns     = $wwnTable->getByBladeId($bladeId);

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
            /** @var $wwn HPSIMBladeWWN */
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

        $bladeTable = new HPSIMBladeTable();
        $b          = $bladeTable->getById($bladeId);

        $vmTable = new HPSIMVMTable();
        $vms     = $vmTable->getByBladeId($bladeId);

        $vmExceptionTable = new HPSIMVMExceptionTable();

        $nodes = array();

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
            "col7"    => "Status",
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

            //if ($vmware) {
            if (false) {
                $nodeObj = array(
                    "id"          => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms/{$vm->getId()}",
                    "dbId"        => $vm->getId(),
                    "type"        => "vm",
                    "col1"        => $vm->name,
                    "col2"        => "&nbsp;",
                    "col3"        => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;",
                    "col6"        => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                    "vmException" => "",

                    "iconCls"     => $iconCls,
                    "leaf"        => true
                );
                $nodes[] = $nodeObj;
            } else {
                // check for exceptions
                $vmException = $vmExceptionTable->getByVmId(($vm->getId()));

                $exceptionDescr = "";
                $iconCls        = "vm";
                if ($vmException->getId() != "") {
                    $iconCls        = "vm-excep";
                    $exceptionDescr = $vmException->getExceptionTypeDescr();
                }

                $nodeObj = array(
                    "id"          => "{$switchName}/{$chassisId}/blades/{$bladeId}/vms/{$vm->getId()}",
                    "dbId"        => $vm->getId(),
                    "type"        => "vm",
                    "col1"        => $vm->getDeviceName() ? $vm->getDeviceName() : $vm->getFullDnsName(),
                    "col2"        => $vm->getSubsystem(),
                    "col3"        => $vm->getOsVersion(),
                    "col4"        => $vm->getOsPatchLevel(),
                    "col5"        => $vm->getNumberOfCpus(),
                    "col6"        => $vm->getMemorySize(),
                    "col7"        => $vm->getStatus(),
                    "col8"        => "&nbsp;",
                    "col9"        => $vm->getComments(),

                    "vmException" => $exceptionDescr,

                    "iconCls"     => $iconCls,
                    "leaf"        => true
                );
                $nodes[] = $nodeObj;
            }
        }
    } // Blade Arrays
    else if (preg_match("/^([\w\s]+)\/(\d+)\/blades\/(\d+)\/arrays$/", $node, $m)) {
        $switchName = $m[1];
        $chassisId  = $m[2];
        $bladeId    = $m[3];

        $bladeTable = new HPSIMBladeTable();
        $b          = $bladeTable->getById($bladeId);

        $ssHostTable = new SanScreenHostTable();
        $ssHost      = $ssHostTable->getByName($b->getFullDnsName());

        $arrays = $ss->getArraysByHostId($ssHost->getId());

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

        $bladeTable = new HPSIMBladeTable();
        $b          = $bladeTable->getById($bladeId);

        $ssHostTable = new SanScreenHostTable();
        $ssHost      = $ssHostTable->getByName($b->getFullDnsName());

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

function curlExec($url) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function curlGetUrl($url, $post = null) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, "{$_SERVER['PHP_AUTH_USER']}:{$_SERVER['PHP_AUTH_PW']}");
    //curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    if (is_array($post)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}
