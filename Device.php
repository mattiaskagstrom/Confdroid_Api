<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:19
 */
class Device
{

    private $id, $name, $imei, $applications = array(), $dateCreated;

    function __construct($id, $name, $imei, $dateCreated = null){
        $this->id = $id;
        $this->name = $name;
        $this->imei = $imei;
        $this->dateCreated = $dateCreated;
    }

    function __sleep()
    {
        return array("name", "imei", "applications");
    }

    function __toString()
    {

        return json_encode($this->getObject());
    }

    function getObject(){

        $device["name"] = $this->name;
        $device["imei"] = $this->imei;
        $device["dateCreated"] = $this->dateCreated;
        $device["applications"] = $this->applications;
        return $device;
    }

    function addApplication($application){
        array_push($this->applications, $application->getObject());
    }

    public function getApplications()
    {
        return $this->applications;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getImei()
    {
        return $this->imei;
    }
}