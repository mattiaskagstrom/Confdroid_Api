<?php

/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-04-04
 * Time: 11:18
 */
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});


class DatabaseConnection
{
    private $dbc;
    private $applicationFunctions;
    private $adminFunctions;

    /**
     * DatabaseConnection constructor.
     */
    function __construct()
    {
        $dsn = 'mysql:host=localhost;dbname=confdroid_test';
        $username = 'confdroid_test';
        $password = 'tutus';
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );
        $this->dbc = new PDO($dsn, $username, $password, $options);
        $this->applicationFunctions = new ApplicationFunctoins($this->dbc);
        $this->adminFunctions = new AdminFunctions($this->dbc);
    }

    /**
     * Split the get request into the data fetching functions for each unit
     * @param $request to handle
     * @return mixed|string, returns md array with the requested object, or a string if nothing is found
     */
    public function get($request){
        if($request[1] == "user" && !isset($request[2])){
            if(!isset($_GET["userAuth"])){
                die("userAuth token missing.");
            }
            if(isset($_GET["imei"])){
                $userValues = $this->applicationFunctions->authorizeUser($_GET["userAuth"]);    //Gets User
                $user = new User($userValues["id"], $userValues["name"], $userValues["mail"]);  //Create User from authorized user
                $device = $this->applicationFunctions->getDevice($user->getId(), $_GET["imei"]);//Gets Device
                if($device == null)
                    return "No device on this imei, contact administration for support";
                $device = $this->applicationFunctions->getAplications($device);                 //Gets the device applications
                $user->addDevice($device);                                                      //Add device to the user
                return $user->getObject();
            }
        }
        else{
            return "No such unit to get";
        }
    }

    public function post($request){
        if($request[1] == "admin"){
            if($request[2] == "login")
                return $this->adminFunctions->login($_POST["username"], $_POST["password"]);
            else if($request[2] == "authorize")
                return $this->adminFunctions->authorizeAdmin($_POST["authToken"], $_POST["id"]);
            else if($request[2] == "search")
                return 1;

        }
        else{
            return "No such unit to get";
        }
    }

    public function put($request){

    }

    public function delete($request){

    }

    private function sqlQuery(){
        //SELECT user.name, device.name FROM device, user, user_device WHERE user_device.user_id = user.id AND user_device.device_id = device.id
    }





}