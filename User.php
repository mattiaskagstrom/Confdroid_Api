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

    private $id, $name, $email, $devices = array(), $groups = array();

    function __construct($id, $name, $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
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
        $user["name"] = $this->name;
        $user["email"] = $this->email;
        $user["devices"] = $this->devices;
        $user["groups"] = $this->groups;
        return $user;
    }

    public function addDevice($device){
        array_push($this->devices,$device->getObject());
    }

    public function addGroup($group){
        array_push($this->groups, $group);
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