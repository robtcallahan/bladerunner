<?php

use STS\DB\MySqlDB;
use STS\Util\SysLog;

class NeuMatic
{
	protected $neumaticDB;

	public function __construct()
	{
        $appConfig = $GLOBALS['config'];

        $dbIndex = "neumatic";
        $config['appName'] = "bladerunner";
        $config['logLevel'] = SysLog::WARNING;
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
        $this->neumaticDB = new MySqlDB($config);
	}
	
	public function getVlanByVlanIdAndDistSwitch($vlanId, $distSwitch)
	{
		$this->neumaticDB->connect();
		$sql = "select v.*
                from dist_switch s,
                     vlan v
                where v.distSwitchId = s.id
                  and s.name = '{$distSwitch}'
                  and v.vlanId = {$vlanId};";
        $row = $this->neumaticDB->getObject($sql);
        $this->neumaticDB->close();
		return $row;
    }
}

