<?php

/**
 * Class Application
 * Stores data about the applications
 */
class Application
{
    private $id, $forceInstall, $dataDir, $apkName,$apkURL,$name,$SQL_settings = array(),$XML_settings = array(), $packageName, $users, $groups, $devices;

    function __construct($id, $dataDir,$apkName,$apkURL,$name,$forceInstall, $packageName)
    {
        $this->id = $id;
        $this->apkName = $apkName;
        $this->apkURL = $apkURL;
        $this->dataDir = $dataDir;
        $this->forceInstall = $forceInstall;
        $this->name = $name;
        $this->packageName = $packageName;
    }

    public function getObject(){
        $application['id'] = $this->id;
        $application['name'] = $this->name;
        $application['apkName'] = $this->apkName;
        $application['forceInstall'] = $this->forceInstall;
        $application['packageName'] = $this->packageName;
        $application['dataDir'] = $this->dataDir;
        $application['apkURL'] = $this->apkURL;
        $application['SQL_settings'] = $this->SQL_settings;
        $application['XML_settings'] = $this->XML_settings;
        $application["user"]=$this->users;
        $application["groups"]=$this->groups;
        $application["devices"]=$this->devices;
        return $application;
    }

    public function addSQL_setting($SQL_setting){
        array_push($this->SQL_settings,$SQL_setting->getObject());

    }

    public function addXML_setting($XML_setting){
        array_push($this->XML_settings,$XML_setting->getObject());

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getSQLSettings()
    {
        return $this->SQL_settings;
    }

    /**
     * @return mixed
     */
    public function getXMLSettings()
    {
        return $this->XML_settings;
    }

    /**
     *
     */
    public function getApkName()
    {
        return $this->apkName;
    }

    /**
     *
     */
    public function getApkURL()
    {
        return $this->apkURL;
    }

    /**
     *
     */
    public function getDataDir()
    {
        return $this->dataDir;
    }

    /**
     *
     */
    public function getForceInstall()
    {
        return $this->forceInstall;
    }
    /**
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * @return mixed
     */
    public function getDevices()
    {
        return $this->devices;
    }

    /**
     * @return mixed
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param mixed $user
     */
    public function addUser($user)
    {
        array_push($this->users, $user);
    }

    public function addGroup($group){
        array_push($this->groups, $group);
    }

    public function addDevice($device){
        array_push($this->devices, $device);
    }

    /**
     * @param mixed $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @param mixed $devices
     */
    public function setDevices($devices)
    {
        $this->devices = $devices;
    }

    /**
     * @param mixed $groups
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }
}