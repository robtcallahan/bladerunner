<?php
/*******************************************************************************
 *
 * $Id: AssetTable.php 82443 2014-01-03 14:28:19Z rcallaha $
 * $Date: 2014-01-03 09:28:19 -0500 (Fri, 03 Jan 2014) $
 * $Author: rcallaha $
 * $Revision: 82443 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/acdc/trunk/lib/AssetTable.php $
 *
 *******************************************************************************
 */

use STS\DB\DBTable;

class ACDCAssetTable extends DBTable
{
	protected static $columnNames = array(
		"id",
        "sysId",
        "sysClassName",
        "assetClass",
        "foundBy",

        "name",
        "label",
        'deviceType',

        "cabinetId",
        "elevation",
        "numRUs",

        "serialNumber",
        "assetTag",
        "manufacturer",
		"model",
        "installStatus",

        "assetStateId",
        "powerStatus",

        "businessServiceSysId",
        "subsystemSysId",

        "lastUpdate"
	);

    protected static $joinTableColumnNames = array(
        "state",
        "businessService",
        "subsystem",
        "cabinet",
        "location",
        "locationId"
    );

    protected $queryColumns;
    protected $allJoinedColumns;
    protected $allJoinedTables;
    protected $fullJoinedQuery;

	public function __construct($idAutoIncremented=true)
	{
        $this->dbIndex = "acdc";
        $this->tableName = "asset";
        $this->idAutoIncremented = $idAutoIncremented;
		parent::__construct();

		$this->sysLog->debug();

        $colAr = explode(',', $this->getQueryColumnsStr());
        for ($i=0; $i<count($colAr); $i++) {
            $colAr[$i] = "t." . trim($colAr[$i]);
        }
        $this->queryColumns = implode(',', $colAr);

        $this->allJoinedColumns = $this->queryColumns . ",
            bs.name as businessService, ss.name as subsystem,
            c.id as cabinetId, c.name as cabinet,
            l.id as locationId, l.name as location,
            stat.name as state
            ";
        $this->allJoinedTables =
            $this->tableName . " t
            left   outer join business_service bs on bs.sysId = t.businessServiceSysId
            left   outer join subsystem ss on ss.sysId = t.subsystemSysId
            left   outer join cabinet c on c.id = t.cabinetId
            left   outer join location l on l.id = c.locationId
            left   outer join asset_state stat on stat.id = t.assetStateId
            ";
        $this->fullJoinedQuery = "
            select " . $this->allJoinedColumns . "
            from   " . $this->allJoinedTables . "
            ";
	}

	/**
	 * @param $id
	 * @return ACDCAsset
	 */
	public function getById($id)
	{
        $sql = $this->fullJoinedQuery . " where  t.id = " . $id . ";";
		$row = $this->sqlQueryRow($sql);
        return $this->_set($row);
	}

	/**
	 * @param $sysId
	 * @return ACDCAsset
	 */
	public function getBySysId($sysId)
	{
        $sql = $this->fullJoinedQuery . " where  t.sysId = '" . $sysId . "';";
		$row =  $this->sqlQueryRow($sql);
        return $this->_set($row);
	}

	/**
	 * @param $name
	 * @return ACDCAsset
	 */
	public function getByName($name)
	{
        $sql = $this->fullJoinedQuery . " where  t.name = '" . $name . "';";
		$row =  $this->sqlQueryRow($sql);
        return $this->_set($row);
	}

    /**
   	 * @param $label
   	 * @return ACDCAsset
   	 */
   	public function getByLabel($label)
   	{
        $sql = $this->fullJoinedQuery . " where  t.label = '" . $label . "';";
        $row = $this->sqlQueryRow($sql);
        return $this->_set($row);
    }

    /**
     * @param  $serialNum
     * @return ACDCAsset
     */
    public function getBySerialNumber($serialNum)
    {
        $sql = $this->fullJoinedQuery . " where  t.serialNumber = '{$serialNum}';";
        $row = $this->sqlQueryRow($sql);
        return $this->_set($row);
    }

    /**
     * @param $searchString
     * @param $orderBy
     * @param $dir
     * @return ACDCAsset[]
     */
   	public function getByLabelLike($searchString, $orderBy = "label", $dir = "asc")
   	{
        $sql = $this->fullJoinedQuery . " where  t.label like '%" . $searchString . "%' order by t." . $orderBy . " " . $dir . ";";
        $result = $this->sqlQuery($sql);
        $objects = array();
        for ($i = 0; $i < count($result); $i++) {
            $objects[] = $this->_set($result[$i]);
        }
        return $objects;
    }

	/**
	 * @param        $searchString
	 * @param string $orderBy
	 * @param string $dir
	 * @return ACDCAsset[]
	 */
	public function getByNameLike($searchString, $orderBy = "name", $dir = "asc")
	{
        $sql = $this->fullJoinedQuery . " where  t.name like '%{$searchString}%' order by t." . $orderBy . " " . $dir . ";";
		$result  = $this->sqlQuery($sql);
		$objects = array();
		for ($i = 0; $i < count($result); $i++) {
			$objects[] = $this->_set($result[$i]);
		}
		return $objects;
	}

	/**
	 * @param        $searchString
	 * @param string $orderBy
	 * @param string $dir
	 * @return ACDCAsset[]
	 */
	public function getBySerialNumberLike($searchString, $orderBy = "serialNumber", $dir = "asc")
	{
        $sql = $this->fullJoinedQuery . " where  t.serialNumber like '%{$searchString}%' order by t." . $orderBy . " " . $dir . ";";
		$result  = $this->sqlQuery($sql);
		$objects = array();
		for ($i = 0; $i < count($result); $i++) {
			$objects[] = $this->_set($result[$i]);
		}
		return $objects;
	}

    /**
     * @param string $bsId
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getByBusinessServiceId($bsId, $orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = "select {$this->queryColumns},
                    c.id as cabinetId, c.name as cabinet
                from   {$this->tableName} t
                left   outer join cabinet c on c.id = t.cabinetId
                where  businessServiceSysId = '{$bsId}'
                order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}

    /**
     * @param string $cabinetId
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getByCabinetId($cabinetId, $orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = "select {$this->queryColumns},
                    c.id as cabinetId, c.name as cabinet
                from   {$this->tableName} t
                left   outer join cabinet c on c.id = t.cabinetId
                where  cabinetId = {$cabinetId}
                order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}

    /**
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getAll($orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = $this->fullJoinedQuery . " order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}

    /**
     * @param $locationId
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getAllByLocationId($locationId, $orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = $this->fullJoinedQuery . " where  l.id = {$locationId} order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}

    /**
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getAllNoSysId($orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = $this->fullJoinedQuery . " where  t.sysId is null or t.sysId = '' order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}

    /**
     * @param $where
     * @param string $orderBy
     * @param string $dir
     * @return ACDCAsset[]
     */
    public function getWhere($where, $orderBy="name", $dir="asc")
   	{
   		$this->sysLog->debug();
        $sql = $this->fullJoinedQuery . " where  " . $where . " order by t." . $orderBy . " " . $dir . ";";
   		$result  = $this->sqlQuery($sql);
   		$objects = array();
   		for ($i = 0; $i < count($result); $i++) {
   			$objects[] = $this->_set($result[$i]);
   		}
   		return $objects;
   	}


	// *******************************************************************************
	// CRUD methods
	// *******************************************************************************

    /**
     * @param ACDCAsset $o
     * @param string $sql
     * @return ACDCAsset
     */
    public function create($o, $sql="")
	{
		$this->sysLog->debug();
        $o->clearChanges();
		$newId = parent::create($o);
		return $this->getById($newId);
	}

    /**
     * @param ACDCAsset $o
     * @param string $idColumn
     * @param string $sql
     * @return ACDCAsset
     */
    public function update($o, $idColumn = "id", $sql = "")
	{
		$this->sysLog->debug();
        $o->clearChanges();
		$o = parent::update($o);
        return $this->getById($o->getId());
	}

    /**
     * @param ACDCAsset $o
     * @param string $idColumn
     * @param string $sql
     * @return mixed
     */
    public function delete($o, $idColumn = "id", $sql = "")
	{
		$this->sysLog->debug();
        $o->clearChanges();
		return parent::delete($o);
	}

	// *****************************************************************************
	// * Getters and Setters
	// *****************************************************************************

	/**
	 * @param $columnNames
	 */
	public static function setColumnNames($columnNames)
	{
		self::$columnNames = $columnNames;
	}

	/**
	 * @return array
	 */
	public static function getColumnNames()
	{
		return self::$columnNames;
	}

    /**
     * @return array
     */
    public static function getJoinTableColumnNames()
    {
        return self::$joinTableColumnNames;
    }

	/**
	 * @param null $dbRowObj
	 * @return ACDCAsset
	 */
	private function _set($dbRowObj = null)
	{
		$this->sysLog->debug();

		$o = new ACDCAsset();
		if ($dbRowObj) {
			foreach (self::$columnNames as $prop) {
                if (property_exists($dbRowObj, $prop)) {
    				$o->set($prop, $dbRowObj->$prop);
	    		}
            }
            foreach (self::$joinTableColumnNames as $prop) {
                if (property_exists($dbRowObj, $prop)) {
                    $o->set($prop, $dbRowObj->$prop);
                }
         	}
		} else {
			foreach (self::$columnNames as $prop) {
				$o->set($prop, null);
			}
            foreach (self::$joinTableColumnNames as $prop) {
         				$o->set($prop, null);
         			}
		}
		return $o;
	}
}
