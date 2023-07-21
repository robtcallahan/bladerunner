<?php

use STS\DB\MySqlDB;
use STS\Util\SysLog;

class BladeRunner
{
	protected $hpsimDB;
	protected $saHost;
	protected $tmpDir;
	protected $dataDir;
	protected $hostsFile;
		
	private static $sysLog;
	private static $logLevel;

	public function __construct()
	{
        $appConfig = $GLOBALS['config'];

		// Set up SysLog
		self::$sysLog = SysLog::singleton($appConfig->appName);
		self::$logLevel = $appConfig->logLevel;
		self::$sysLog->debug();

        $config['appName']  = $appConfig->appName;
        $config['logLevel'] = $appConfig->logLevel;

        $dbIndex = $appConfig->brDB;
        $config['dbIndex'] = $dbIndex;
        $config['databases'] = array(
            $dbIndex => array(
                'server'   => $appConfig->databases->$dbIndex->server,
                'type'     => $appConfig->databases->$dbIndex->type,
                'username' => $appConfig->databases->$dbIndex->username,
                'password' => $appConfig->databases->$dbIndex->password,
                'database' => $appConfig->databases->$dbIndex->database
            )
        );
        $this->brDB = new MySqlDB($config);

        $dbIndex = $appConfig->hpsimDB;
        $config['dbIndex'] = $dbIndex;
        $config['databases'] = array(
            $dbIndex => array(
                'server'   => $appConfig->databases->$dbIndex->server,
                'type'     => $appConfig->databases->$dbIndex->type,
                'username' => $appConfig->databases->$dbIndex->username,
                'password' => $appConfig->databases->$dbIndex->password,
                'database' => $appConfig->databases->$dbIndex->database
            )
        );
		$this->hpsimDB = new MySqlDB($config);
	}	
	
	public function getAllBladeReservations()
	{
		$this->hpsimDB->connect();
		$sql = "select r.id, r.bladeId,
                       r.projectName, r.taskNumber, r.taskSysId, r.taskShortDescr,
                       r.dateReserved, r.userReserved,
                       r.dateUpdated, r.userUpdated,
	                   b.id as bladeId, c.id as chassisId,
	                   c.deviceName as chassisName,
	                   b.deviceName as bladeName, b.slotNumber
	            from   blade_reservation r,
		               chassis c,
		               blade b
			    where  r.dateCancelled is null
			      and  r.dateCompleted is null
				  and  b.id = r.bladeId
				  and  c.id = b.chassisId;";
        $rows = $this->hpsimDB->getAllObjects($sql);
        $this->hpsimDB->close();
		return $rows;
    }

	public function getNodeIdByChassisId($chassisId) {
		$this->hpsimDB->connect();
		$sql = "select concat(c.distSwitchName, '/', c.id) as nodeId
		        from chassis c
		        where c.id = {$chassisId}";
		$row = $this->hpsimDB->getObject($sql);
		$this->hpsimDB->close();
		return $row;
	}

	public function getNodeIdByMPId($mpId) {
		$this->hpsimDB->connect();
		$sql = "select concat(c.distSwitchName, '/', c.id, '/mgmtProcs/', mp.id) as nodeId
		        from mgmt_processor mp,
		             chassis c
		        where mp.id = {$mpId}
		          and c.id = mp.chassisId";
		$row = $this->hpsimDB->getObject($sql);
		$this->hpsimDB->close();
		return $row;
	}

	public function getNodeIdByBladeId($bladeId) {
		$this->hpsimDB->connect();
		$sql = "select concat(c.distSwitchName, '/', c.id, '/blades/', b.id) as nodeId
		        from blade b,
		             chassis c
		        where b.id = {$bladeId}
		          and c.id = b.chassisId";
		$row = $this->hpsimDB->getObject($sql);
		$this->hpsimDB->close();
		return $row;
	}

	public function getNodeIdByVmId($vmId) {
		$this->hpsimDB->connect();
		$sql = "select concat(c.distSwitchName, '/', c.id, '/blades/', b.id, '/vms/', vm.id) as nodeId
		        from vm,
		             blade b,
		             chassis c
		        where vm.id = {$vmId}
		          and b.id = vm.bladeId
		          and c.id = b.chassisId";
		$row = $this->hpsimDB->getObject($sql);
		$this->hpsimDB->close();
		return $row;
	}

	public function getHostNamesAndNodeIds($query = null, $view = "")
	{
		if ($view === "chassis") 
		{
			$swCase = "'all'";
			$swName = "'all'";
		} 
		else 
		{
			$swCase = "case when c.distSwitchName is null then 'Unassigned' else c.distSwitchName end";
			$swName = "c.distSwitchName";
		}
		
		$this->hpsimDB->connect();
		$results = array();
        if ($query)
        {
        	function sortByName($a, $b)
        	{
        		return strcmp($a['name'], $b['name']);
        	}
        	
        	// chassis
        	$sql = "select c.deviceName as name, 
        	               concat(
        	                   {$swCase},
        	                   '/', c.id
        	               ) as node
                    from   chassis c
                    where  c.deviceName like '{$query}%'
                    order  by name;";
		    $rows = $this->hpsimDB->getAllObjects($sql);
		    for ($i=0; $i<count($rows); $i++)
		    {
		    	$results[] = array(
		    		"name" => $rows[$i]->name,
		    		"node" => $rows[$i]->node
		    		);
		    }

		    // blades
        	$sql = "select b.deviceName as name, 
        	               concat(
        	                   {$swCase},
        	                   '/', c.id, '/blades/', b.id
        	               ) as node
                    from   blade b,
                           chassis c
                    where  c.id = b.chassisId
                      and  b.deviceName like '{$query}%'
                    order  by name;";
		    $rows = $this->hpsimDB->getAllObjects($sql);
		    for ($i=0; $i<count($rows); $i++)
		    {
		    	$results[] = array(
		    		"name" => $rows[$i]->name,
		    		"node" => $rows[$i]->node
		    		);
		    }

		    // mgmt modules
        	$sql = "select m.deviceName as name, 
        	               concat(
        	                   {$swCase},
        	                   '/', c.id, '/mgmtProcs/', m.id
        	               ) as node
                    from   mgmt_processor m,
                           chassis c
                    where  c.id = m.chassisId
                      and  m.deviceName like '{$query}%'
                    order  by name;";
		    $rows = $this->hpsimDB->getAllObjects($sql);
		    for ($i=0; $i<count($rows); $i++)
		    {
		    	$results[] = array(
		    		"name" => $rows[$i]->name,
		    		"node" => $rows[$i]->node
		    		);
		    }

		    // vms
        	$sql = "select v.deviceName as name, 
        	               concat(
        	                   {$swCase},
        	                   '/', c.id, '/blades/', b.id, '/vms/', v.id
        	               ) as node
                    from   vm v,
                           blade b,
                           chassis c
                    where  b.id = v.bladeId
                      and  c.id = b.chassisId
                      and  v.deviceName like '{$query}%'
                    order  by name;";
		    $rows = $this->hpsimDB->getAllObjects($sql);
		    for ($i=0; $i<count($rows); $i++)
		    {
		    	$results[] = array(
		    		"name" => $rows[$i]->name,
		    		"node" => $rows[$i]->node
		    		);
		    }
		    usort($results, 'sortByName');
        }
        
        else
        {
        	$sql = "select c.deviceName as name, concat({$swName}, '/', c.id) as node
                    from   chassis c
                    union
                    select b.deviceName as name, concat({$swName}, '/', c.id, '/blades/', b.id) as node
                    from   blade b,
                           chassis c
                    where  c.id = b.chassisId
                    union
                    select m.deviceName as name, concat({$swName}, '/', c.id, '/mgmtProcs/', m.id) as node
                    from   mgmt_processor m,
                           chassis c
                    where  c.id = m.chassisId
                    union
                    select v.deviceName as name, concat({$swName}, '/', c.id, '/blades/', b.id, '/', v.id) as node
                    from   vm v,
                           blade b,
                           chassis c
                    where  b.id = v.bladeId
                      and  c.id = b.chassisId
                      order  by name;";
		    $results = $this->hpsimDB->getAllObjects($sql);
        }
        $this->hpsimDB->close();
		return $results;
	}

	public function getWwnsAndNodeIds($query = null, $view = "")
	{
		if ($view === "chassis") 
		{
			$swCase = "'all'";
		} 
		else 
		{
			$swCase = "case when c.distSwitchName is null then 'Unassigned' else c.distSwitchName end";
		}

		$this->hpsimDB->connect();
        if ($query)
        {
        	function sortByName($a, $b)
        	{
        		return strcmp($a['name'], $b['name']);
        	}
        	
        	$results = array();
        	$sql = "select w.wwn as name, concat({$swCase}, '/', c.id, '/blades/', b.id, '/wwns/', w.id) as node
                    from   blade_wwn w,
                           blade b,
                           chassis c
                    where  b.id = w.bladeId
                      and  c.id = b.chassisId
                      and  w.wwn like '{$query}%'
                    order  by name;";
		    $rows = $this->hpsimDB->getAllObjects($sql);
		    for ($i=0; $i<count($rows); $i++)
		    {
		    	$results[] = array(
		    		"name" => $rows[$i]->name,
		    		"node" => $rows[$i]->node
		    		);
		    }
		    usort($results, 'sortByName');
        }
        
        else
        {
        	$sql = "select w.wwn as name, concat({$swCase}, '/', c.id, '/blades/', b.id, '/wwns/', w.id) as node
                    from   blade_wwn w,
                           blade b,
                           chassis c
                    where  b.id = w.bladeId
                      and  c.id = b.chassisId
                    order  by name;";
		    $results = $this->hpsimDB->getAllObjects($sql);
        }
        $this->hpsimDB->close();
		return $results;
	}

	public function getLogins()
	{
		self::$sysLog->debug();
		$sql = "select l.*, u.*
		        from
		            login l,
		            user u
		        where u.id = l.userId
		        order by l.lastLogin desc";
		$this->brDB->connect();
        $results = $this->brDB->getAllObjects($sql);
        $this->brDB->close();
		return $results;
	}

	public function execCommand($command)
	{

		$out = null;
		$retVar = null;
		exec($command, $out, $retVar);
		if ($retVar != 0)

		{
			throw new ErrorException("Could not execute remote command: {$command}");
		}
		return $out;
	}
}

