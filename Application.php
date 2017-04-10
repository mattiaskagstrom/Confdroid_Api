<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:20
 */
class Application
{


    private $id, $forceInstall, $dataDir, $apkName,$apkURL,$name,$SQL_settings = array(),$XML_settings = array();

    function __construct($id, $dataDir,$apkName,$apkURL,$name,$forceInstall)
    {
        $this->id = $id;
        $this->apkName = $apkName;
        $this->apkURL = $apkURL;
        $this->dataDir = $dataDir;
        $this->forceInstall = $forceInstall;
        $this->name = $name;
    }


    function getObject(){
        $application['name'] = $this->name;
        $application['apkName'] = $this->apkName;
        $application['forceInstall'] = $this->forceInstall;
        $application['dataDir'] = $this->dataDir;
        $application['apkURL'] = $this->apkURL;
        $application['SQL_settings'] = $this->SQL_settings;
        $application['XML_settings'] = $this->XML_settings;
        return $application;
    }

    function addSQL_setting($SQL_setting){
        array_push($this->SQL_settings,$SQL_setting);

    }

    function addXML_setting($XML_setting){
        array_push($this->XML_settings,$XML_setting);

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








}