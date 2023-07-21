<?php
/*******************************************************************************
 *
 * $Id: Asset.php 81526 2013-11-28 19:27:43Z rcallaha $
 * $Date: 2013-11-28 14:27:43 -0500 (Thu, 28 Nov 2013) $
 * $Author: rcallaha $
 * $Revision: 81526 $
 * $HeadURL: https://svn.ultradns.net/svn/sts_tools/acdc/trunk/lib/Asset.php $
 *
 *******************************************************************************
 */

class ACDCAsset
{
    protected $id;
    protected $sysId;
    protected $sysClassName;
    protected $assetClass;
    protected $foundBy;

    protected $name;
    protected $label;
    protected $deviceType;

    protected $cabinetId;
    protected $elevation;
    protected $numRUs;

    protected $serialNumber;
    protected $assetTag;
    protected $manufacturer;
    protected $model;

    protected $assetStateId;
    protected $powerStatus;

    protected $businessServiceSysId;
    protected $subsystemSysId;

    protected $lastUpdate;

    protected $state;
    protected $installStatus;
    protected $businessService;
    protected $subsystem;
    protected $location;
    protected $locationId;
    protected $cabinet;

    protected $changes = array();

    public function __toString()
    {
        $return = "";
        foreach (ACDCAssetTable::getColumnNames() as $prop) {
            $return .= sprintf("%-25s => %s\n", $prop, $this->$prop);
        }
        foreach (ACDCAssetTable::getJoinTableColumnNames() as $prop) {
            $return .= sprintf("%-25s => %s\n", $prop, $this->$prop);
        }
        return $return;
    }

    /**
     * @return object
     */
    public function toObject()
    {
        $obj = (object)array();
        foreach (ACDCAssetTable::getColumnNames() as $prop) {
            $obj->$prop = $this->$prop;
        }
        foreach (ACDCAssetTable::getJoinTableColumnNames() as $prop) {
            $obj->$prop = $this->$prop;
        }
        return $obj;
    }

    // *******************************************************************************
    // Getters and Setters
    // *******************************************************************************

    /**
     * @param $prop
     * @return mixed
     */
    public function get($prop)
    {
        return $this->$prop;
    }

    /**
     * @param $prop
     * @param $value
     * @return $this
     */
    public function set($prop, $value)
    {
        $this->$prop = $value;
        return $this;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function clearChanges()
    {
        $this->changes = array();
    }

    /**
     * @param $value
     */
    private function updateChanges($value)
    {
        $trace = debug_backtrace();

        // get the calling method name, eg., setSysId
        $callerMethod = $trace[1]["function"];

        // perform a replace to remove "set" from the method name and change first letter to lowercase
        // so, setSysId becomes sysId. This will be the property name that needs to be added to the changes array
        $prop = preg_replace_callback(
            "/^set(\w)/",
            function ($matches) {
                return strtolower($matches[1]);
            },
            $callerMethod
        );

        // check to be sure that there was a change to the value before updating the changes array
        if ($value != $this->$prop) {
            // update the changes array to keep track of this properties orig and new values
            if (!array_key_exists($prop, $this->changes)) {
                $this->changes[$prop] = (object)array(
                    'originalValue' => $this->$prop,
                    'modifiedValue' => $value
                );
            } else {
                $this->changes[$prop]->modifiedValue = $value;
            }
        }
    }

    /**
     * @param $assetTag
     * @return $this
     */
    public function setAssetTag($assetTag)
    {
        $this->updateChanges(func_get_arg(0));
        $this->assetTag = $assetTag;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssetTag()
    {
        return $this->assetTag;
    }

    /**
     * @param $cabinetId
     * @return $this
     */
    public function setCabinetId($cabinetId)
    {
        $this->updateChanges(func_get_arg(0));
        $this->cabinetId = $cabinetId;
        return $this;
    }

    /**
     * @return mixed
     *
     */
    public function getCabinetId()
    {
        return $this->cabinetId;
    }

    /**
     * @param $elevation
     * @return $this
     */
    public function setElevation($elevation)
    {
        $this->updateChanges(func_get_arg(0));
        $this->elevation = $elevation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getElevation()
    {
        return $this->elevation;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->updateChanges(func_get_arg(0));
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     * @return $this
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $manufacturer
     * @return $this
     */
    public function setManufacturer($manufacturer)
    {
        $this->updateChanges(func_get_arg(0));
        $this->manufacturer = $manufacturer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getManufacturer()
    {
        return $this->manufacturer;
    }

    /**
     * @param $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->updateChanges(func_get_arg(0));
        $this->model = $model;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->updateChanges(func_get_arg(0));
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $numRUs
     * @return $this
     */
    public function setNumRUs($numRUs)
    {
        $this->updateChanges(func_get_arg(0));
        $this->numRUs = $numRUs;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumRUs()
    {
        return $this->numRUs;
    }

    /**
     * @param $serialNumber
     * @return $this
     */
    public function setSerialNumber($serialNumber)
    {
        $this->updateChanges(func_get_arg(0));
        $this->serialNumber = $serialNumber;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * @param $sysId
     * @return $this
     */
    public function setSysId($sysId)
    {
        $this->updateChanges(func_get_arg(0));
        $this->sysId = $sysId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSysId()
    {
        return $this->sysId;
    }

    /**
     * @param mixed $sysClassName
     * @return $this
     */
    public function setSysClassName($sysClassName)
    {
        $this->updateChanges(func_get_arg(0));
        $this->sysClassName = $sysClassName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSysClassName()
    {
        return $this->sysClassName;
    }

    /**
     * @param mixed $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->updateChanges(func_get_arg(0));
        $this->label = $label;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param mixed $foundBy
     * @return $this
     */
    public function setFoundBy($foundBy)
    {
        $this->updateChanges(func_get_arg(0));
        $this->foundBy = $foundBy;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFoundBy()
    {
        return $this->foundBy;
    }

    /**
     * @param mixed $businessServiceSysId
     * @return $this
     */
    public function setBusinessServiceSysId($businessServiceSysId)
    {
        $this->updateChanges(func_get_arg(0));
        $this->businessServiceSysId = $businessServiceSysId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBusinessServiceSysId()
    {
        return $this->businessServiceSysId;
    }

    /**
     * @param mixed $subsystemSysId
     * @return $this
     */
    public function setSubsystemSysId($subsystemSysId)
    {
        $this->updateChanges(func_get_arg(0));
        $this->subsystemSysId = $subsystemSysId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubsystemSysId()
    {
        return $this->subsystemSysId;
    }

    /**
     * @return mixed
     */
    public function getBusinessService()
    {
        return $this->businessService;
    }

    /**
     * @return mixed
     */
    public function getSubsystem()
    {
        return $this->subsystem;
    }

    /**
     * @return mixed
     */
    public function getCabinet()
    {
        return $this->cabinet;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param mixed $installStatus
     * @return $this
     */
    public function setInstallStatus($installStatus)
    {
        $this->updateChanges(func_get_arg(0));
        $this->installStatus = $installStatus;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInstallStatus()
    {
        return $this->installStatus;
    }

    /**
     * @param mixed $deviceType
     * @return $this
     */
    public function setDeviceType($deviceType)
    {
        $this->updateChanges(func_get_arg(0));
        $this->deviceType = $deviceType;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * @param mixed $assetClass
     * @return $this
     */
    public function setAssetClass($assetClass)
    {
        $this->updateChanges(func_get_arg(0));
        $this->assetClass = $assetClass;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssetClass()
    {
        return $this->assetClass;
    }

    /**
     * @param mixed $powerStatus
     * @return $this
     */
    public function setPowerStatus($powerStatus)
    {
        $this->updateChanges(func_get_arg(0));
        $this->powerStatus = $powerStatus;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPowerStatus()
    {
        return $this->powerStatus;
    }

    /**
     * @param mixed $assetStateId
     * @return $this
     */
    public function setAssetStateId($assetStateId)
    {
        $this->updateChanges(func_get_arg(0));
        $this->assetStateId = $assetStateId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAssetStateId()
    {
        return $this->assetStateId;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $lastUpdate
     * @return $this
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->updateChanges(func_get_arg(0));
        $this->lastUpdate = $lastUpdate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

}
