<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:19
 */
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});
class User
{

    private $id, $name, $email, $devices = array(), $groups = array(), $authToken, $dateCreated, $applications = array();

    function __construct($id, $name, $email, $authToken = null, $dateCreated = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->authToken = $authToken;
        $this->dateCreated = $dateCreated;
    }

    function __sleep()
    {
        return array("name", "email", "devices", "groups");
    }

    function __toString()
    {

        return json_encode($this->getObject());
    }

    function getObject(){
        $user["id"] = $this->id;
        $user["name"] = $this->name;
        $user["email"] = $this->email;
        $user["authToken"] = $this->authToken;
        $user["dateCreated"] = $this->dateCreated;
        $user["devices"] = $this->devices;
        $user["groups"] = $this->groups;
        $user["applications"] = $this->applications;
        return $user;
    }

    /**
     * @param $device Device
     */
    public function addDevice(Device $device){
        array_push($this->devices,$device->getObject());
    }

    /**
     * @param $devices Device[]
     */
    public function addDevices($devices){
        for ($i = 0; $i < count($devices); $i++)
            $this->addDevice($devices[$i]);
    }

    public function addGroup(Group $group){
        array_push($this->groups, $group->getObject());
    }

    /**
     * @param $devices Device[]
     */
    public function addGroups($groups){
        for ($i = 0; $i < count($groups); $i++)
            $this->addGroup($groups[$i]);
    }

    public function addApplication(Application $application){
        array_push($this->applications, $application->getObject());
    }

    /**
     * @return array
     */
    public function getApplications()
    {
        return $this->applications;
    }

    public function getName(){
        return $this->name;
    }

    public function getEmail(){
        return $this->email;
    }

    public function getDevices(){
        return $this->devices;
    }

    public function getGroups(){
        return $this->groups;
    }

    public function getId()
    {
        return $this->id;
    }
}