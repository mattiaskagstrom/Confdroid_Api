<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:20
 */
class Group
{
    private $id, $prio, $name, $users = array(), $applications = array();

    function __construct($id, $prio, $name)
    {
        $this->id = $id;
        $this->prio = $prio;
        $this->name = $name;
    }

    function __toString()
    {
        return json_encode($this->getObject());
    }

    function getObject()
    {
        $group["id"] = $this->id;
        $group["prio"] = $this->prio;
        $group["name"] = $this->name;
        $group["users"] = $this->users;
        $group["applications"] = $this->applications;
        return $group;
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
    public function getPrio()
    {
        return $this->prio;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $user User, the user to ad to the group
     */
    public function addUser($user){
        array_push($this->users, $user->getObject());
    }

    /**
     * @param $application Application, The application to add to the grop
     */
    public function addApplication($application){
        array_push($this->applications, $application->getObject());
    }
}