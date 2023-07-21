<?php

use STS\SANScreen\SANScreen;
use STS\SANScreen\SANScreenArrayTable;
use STS\SANScreen\SANScreenHostTable;
use STS\SANScreen\SANScreenVolumeTable;
use STS\SANScreen\SANScreenSwitchTable;
use STS\SANScreen\SANScreenVmTable;

use STS\CMDB\CMDBStorageDeviceTable;

include __DIR__ . "/../config/global.php";

// TODO: SAN names need to be split by Sterling and Charlotte

try {
    // config
    $config = $GLOBALS['config'];

    // get the user and update the page view
    $userName = $_SERVER["PHP_AUTH_USER"];

    // set default time zone
    date_default_timezone_set("Greenwich");

    // instantiate
    $ss            = new SANScreen();
    $arrayTable    = new SANScreenArrayTable();
    $ssHostTable   = new SANScreenHostTable();
    $ssVolumeTable = new SANScreenVolumeTable();
    $ssSwitchTable = new SANScreenSwitchTable();
    $ssVmTable     = new SANScreenVmTable();

    // get the passed node
    $node = array_key_exists('node', $_POST) ? $_POST['node'] : 'root';

    $nodes = array();

    // SANs
    if ($node === "root") {
        $sans = $arrayTable->getSans(SANScreenArrayTable::STERLING_FIRST);

        // header row
        $nodeObj = array(
            "id"      => "HEADER",
            "type"    => "header",
            "col1"    => "SAN Name",
            "col2"    => "&nbsp;",
            "col3"    => "&nbsp;",
            "col4"    => "&nbsp;",
            "col5"    => "&nbsp;",
            "col6"    => "&nbsp;",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        foreach ($sans as $san) {
            $nodeObj = array(
                "id"      => $san,
                "dbId"    => $san,
                "type"    => "san",
                "col1"    => $san,
                "col2"    => "&nbsp;",
                "col3"    => "&nbsp;",
                "col4"    => "&nbsp;",
                "col5"    => "&nbsp;",
                "col6"    => "&nbsp;",
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'array',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Tiers
    else if (preg_match("/^([\w\d\s]+)$/", $node, $m)) {
        $sanName = $m[1];

        // get the list of arrays for this SAN
        $tiers = $arrayTable->getTiersBySanName($sanName);

        // header row
        /* excluding header for Tiers
		$nodeObj = array(
			"id"   => "{$sanName}/HEADER",
			"type" => "header",
			"col1" => "Tier Name",
			"col2" => "&nbsp;",	"col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;",
			"col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
			
			"header" => true,
			
			"iconCls"      => 'x-tree-node-inline-icon',
			"leaf"         => true
			);
		$nodes[] = $nodeObj;
        */

        foreach ($tiers as $tier) {
            $nodeObj = array(
                "id"      => "{$sanName}/{$tier}",
                "dbId"    => 0,
                "type"    => "",
                "col1"    => $tier,
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;",
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'array',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Arrays
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];

        $arrays = $arrayTable->getBySanAndTier($sanName, $tierName);

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/HEADER",
            "type"    => "header",
            "col1"    => "Storage Array Name",
            "col2"    => "Serial Number",
            "col3"    => "Vendor",
            "col4"    => "Model",
            "col5"    => "Useable (TB)",
            "col6"    => "Provisioned (TB)",
            "col7"    => "Available (TB)",
            "col8"    => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        foreach ($arrays as $a) {
            $arrayStorage = $ss->getArrayStorageByArrayId($a->getId());
            $nodeObj      = array(
                "id"      => "{$sanName}/{$tierName}/{$a->getId()}",
                "dbId"    => $a->getId(),
                "type"    => "array",
                "col1"    => $a->getName(),
                "col2"    => $a->getSerialNumber(),
                "col3"    => $a->getVendor(),
                "col4"    => $a->getModel(),
                "col5"    => round($arrayStorage->totalUseableTb, 2),
                "col6"    => round($arrayStorage->totalProvisionedTb, 2),
                "col7"    => round($arrayStorage->totalAvailableTb, 2),
                "col8"    => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'array',
                "leaf"    => false
            );
            $nodes[]      = $nodeObj;
        }
    } // Array subfolders
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];

        $array = $arrayTable->getById($arrayId);

        $nodes = array(
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/bs",
                "type"    => "folder",
                "col1"    => "Hosts Business Services",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/switches",
                "type"    => "folder",
                "col1"    => "Switches",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/volumes",
                "type"    => "folder",
                "col1"    => "Volumes",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts",
                "type"    => "folder",
                "col1"    => "Hosts",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
        );
    } // Business Services
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/bs$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];

        $lobs = $ss->getLobsByArrayId($arrayId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/bs/HEADER",
            "type"    => "header",
            "col1"    => "Hosts Business Services",
            "col2"    => "Hosts Subsystems",
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
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/bs/{$l->subsystem}",
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
    } // Array Hosts
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/hosts$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];

        $hosts = $ssHostTable->getByArrayId($arrayId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/HEADER",
            "type"    => "header",
            "col1"    => "Host Name",
            "col2"    => "Subsystem",
            "col3"    => "Ops Supp Mgr",
            "col4"    => "Ops Supp Grp",
            "col5"    => "Environment",
            "col6"    => "Total Provisioned(GB)",
            "col7"    => "&nbsp;",
            "col8"    => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($hosts); $i++) {
            $h            = $hosts[$i];
            $totalStorage = $ss->getHostArrayStorageByArrayIdAndHostId($arrayId, $h->getId());

            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$h->getId()}",
                "dbId"    => $h->getId(),
                "type"    => "host",
                "col1"    => $h->getName(),
                "col2"    => $h->getSubsystem(),
                "col3"    => $h->getOpsSuppMgr(),
                "col4"    => $h->getOpsSuppGrp(),
                "col5"    => $h->getEnvironment(),
                "col6"    => $totalStorage,
                "col7"    => "&nbsp;",
                "col8"    => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => false
            );
            $nodes[] = $nodeObj;
        }
    } // Array Volumes
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/volumes$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];

        $volumes = $ssVolumeTable->getByArrayId($arrayId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/volumes/HEADER",
            "type"    => "header",
            "col1"    => "Name",
            "col2"    => "Type",
            "col3"    => "Disk Type",
            "col4"    => "Redundancy",
            "col5"    => "Capacity (GB)",
            "col6"    => "Raw Capacity (GB)",
            "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($volumes); $i++) {
            $v = $volumes[$i];

            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/volumes/{$v->getId()}",
                "dbId"    => $v->getId(),
                "type"    => "volume",
                "col1"    => $v->getName(),
                "col2"    => $v->getType(),
                "col3"    => $v->getDiskType(),
                "col4"    => $v->getRedundancy(),
                "col5"    => $v->getCapacityGB(),
                "col6"    => $v->getRawCapacityGB(),
                "col7"    => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'blade',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Array Switches
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/switches$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];

        $switches = $ssSwitchTable->getByArrayId($arrayId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/switches/HEADER",
            "type"    => "header",
            "col1"    => "Name",
            "col2"    => "IP Address",
            "col3"    => "Dead",
            "col4"    => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($switches); $i++) {
            $s = $switches[$i];

            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/switches/{$s->getId()}",
                "dbId"    => $s->getId(),
                "type"    => "switch",
                "col1"    => $s->getName(),
                "col2"    => $s->getIp(),
                "col3"    => $s->getDead(),
                "col4"    => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Array Switch Ports
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/switches\/(\d+)$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];
        $switchId = $m[4];

        $ports = $ss->getArrayPortsByArrayId($switchId, $arrayId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/switches/{$switchId}/HEADER",
            "type"    => "header",
            "col1"    => "Array WWN",
            "col2"    => "SW Port",
            "col3"    => "SW State",
            "col4"    => "SW Status",
            "col5"    => "SW WWN",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($ports); $i++) {
            $p = $ports[$i];

            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/switches/{$switchId}/{$p->apId}",
                "dbId"    => $p->apId,
                "type"    => "port",
                "col1"    => $p->apWwn,
                "col2"    => $p->spName,
                "col3"    => $p->spState,
                "col4"    => $p->spStatus,
                "col5"    => $p->spWwn,
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Hosts subfolders
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/hosts\/(\d+)$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];
        $hostId   = $m[4];

        $h = $ssHostTable->getById($hostId);

        $nodes = array(
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/ports",
                "type"    => "folder",
                "col1"    => "Ports",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            ),
            array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/volumes",
                "type"    => "folder",
                "col1"    => "Volumes",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            )
        );

        $vms = $ssVmTable->getByHostId($h->getId());
        if (count($vms) > 0) {
            $nodes[] = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/vms",
                "type"    => "folder",
                "col1"    => "VMs",
                "col2"    => "&nbsp;", "col3" => "&nbsp;", "col4" => "&nbsp;", "col5" => "&nbsp;", "col6" => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",
                "iconCls" => 'folder'
            );
        }
    } // Hosts Ports
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/hosts\/(\d+)\/ports$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];
        $hostId   = $m[4];

        $ports = $ss->getHostPortsByHostId($hostId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/ports/HEADER",
            "type"    => "header",
            "col1"    => "Host WWN",
            "col2"    => "SW Port",
            "col3"    => "SW State",
            "col4"    => "SW Status",
            "col5"    => "SW WWN",
            "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

            "header"  => true,

            "iconCls" => 'x-tree-node-inline-icon',
            "leaf"    => true
        );
        $nodes[] = $nodeObj;

        // data rows
        for ($i = 0; $i < count($ports); $i++) {
            $p = $ports[$i];

            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/ports/{$p->hpId}",
                "dbId"    => $p->hpId,
                "type"    => "port",
                "col1"    => $p->hpWwn,
                "col2"    => $p->spName,
                "col3"    => $p->spState,
                "col4"    => $p->spStatus,
                "col5"    => $p->spWwn,
                "col6"    => "&nbsp;", "col7" => "&nbsp;", "col8" => "&nbsp;", "col9" => "&nbsp;",

                "iconCls" => 'card',
                "leaf"    => true
            );
            $nodes[] = $nodeObj;
        }
    } // Hosts Volumes
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/hosts\/(\d+)\/volumes$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];
        $hostId   = $m[4];

        $vols = $ss->getHostArrayVolumesByArrayIdAndHostId($arrayId, $hostId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/volumes/HEADER",
            "type"    => "header",
            "col1"    => "Volume Name",
            "col2"    => "Type",
            "col3"    => "Disk Type",
            "col4"    => "Redundancy",
            "col5"    => "Capacity (GB)",
            "col6"    => "Raw Capacity (GB)",
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
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/volumes/{$v->id}",
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
    } // Hosts VMs
    else if (preg_match("/^([\w\d\s]+)\/([\w\d\s]+)\/(\d+)\/hosts\/(\d+)\/vms$/", $node, $m)) {
        $sanName  = $m[1];
        $tierName = $m[2];
        $arrayId  = $m[3];
        $hostId   = $m[4];

        $ssHostTable = new SANScreenHostTable();
        $h           = $ssHostTable->getById($hostId);

        $ssVmTable = new SANScreenVmTable();
        $vms       = $ssVmTable->getByHostId($hostId);

        $nodes = array();

        // header row
        $nodeObj = array(
            "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/vms/HEADER",
            "type"    => "header",
            "col1"    => "Host Name",
            "col2"    => "Subsystem",
            "col3"    => "Environment",
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
            $vm      = $vms[$i];
            $nodeObj = array(
                "id"      => "{$sanName}/{$tierName}/{$arrayId}/hosts/{$hostId}/vms/{$vm->getId()}",
                "dbId"    => $vm->getId(),
                "type"    => "vm",
                "col1"    => $vm->getName(),
                "col2"    => $vm->getSubsystem(),
                "col3"    => $vm->getEnvironment(),
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

/**
 * @param $a CMDBStorageDevice
 * @param $b CMDBStorageDevice
 */
function sortByBS(STS\CMDB\CMDBStorageDevice $a, STS\CMDB\CMDBStorageDevice $b) {
    return strcmp($a->getBusinessService(), $b->getBusinessService());
}

