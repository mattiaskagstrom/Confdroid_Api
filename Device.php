<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:19
 */
class Device
{

    private $id, $name, $imei, $applications;

    function __construct($id, $name, $imei){
        $this->id = $id;
        $this->name = $name;
        $this->imei = $imei;
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
        $device["applications"] = $this->applications;
        return $device;
    }

    function addApplication($application){
        array_push($this->applications, $application);
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