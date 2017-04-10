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

    }

    private function login($username, $password)
    {
        $stmt = $this->dbc->prepare("SELECT id FROM admin WHERE username=:username AND password=:password");
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);
        $stmt->execute();


        $checking = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $loggedInId = null;
        if(isset($checking[0]))
            $loggedInId = $checking[0];
        if($loggedInId == null)
            return "Failed login";
        else
        {
            $token = bin2hex(openssl_random_pseudo_bytes(16));
            $_SESSION["adminAuthToken"] = $token;
            $insertAuth = $this->dbc->prepare("UPDATE admin SET authToken=:authToken WHERE username=:username AND password=:password ");
            $insertAuth->bindParam(":authToken", $token);
            $insertAuth->bindParam(":username", $username);
            $insertAuth->bindParam(":password", $password);
            $insertAuth->execute();
            return $token;
        }

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
                return $this->getUser($_GET["userAuth"], $_GET["imei"]);
            }
            else{
                return $this->getUser($_GET["userAuth"]);
            }
        }
        else{
            return "No such unit to get";
        }
    }

    public function post($request){
        if($request[1] == "admin" && $request[2] == "login"){
            return $this->login($_POST["username"], $_POST["password"]);
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

    /**
     * @param $userAuth, the authentication token for the user to get
     * @param $imei is optional, fetches all if not specified.
     * @return md array with the requested user and its device(es).
     */
    private function getUser($userAuth, $imei = null){
        $stmt = $this->dbc->prepare("SELECT id, name, mail FROM user WHERE auth_token=:authToken");
        $stmt->bindParam(":authToken", $userAuth);
        $stmt->execute();
        $dbuser = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

        if($imei != null){
            $stmt = $this->dbc->prepare("SELECT id, name, imei FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id = :userID AND device.imei = :imei");
            $stmt->bindParam(":userID", $dbuser["id"]);
            $stmt->bindParam("imei", $imei);
        }else{
            $stmt = $this->dbc->prepare("SELECT id, name, imei FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id = :userID");
            $stmt->bindParam(":userID", $dbuser["id"]);
        }
        $stmt->execute();
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $user = new User($dbuser["id"], $dbuser["name"], $dbuser["mail"]);

        $stmt = $this->dbc->prepare("SELECT id, apk_name, apk_url, force_install, data_dir, friendly_name FROM application, application_device WHERE application.id = application_device.application_id AND application_device.device_id =:deviceID");
        foreach ($devices as $device) {
            $newDevice = new Device($device["id"], $device["name"], $device["imei"]);
            $deviceID = $newDevice->getId();
            $stmt->bindParam(":deviceID", $deviceID);
            $stmt->execute();

            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($applications as $application) {
                $newDevice->addApplication(new Application($application["id"], $application["data_dir"], $application["apk_name"],$application["apk_url"],$application["friendly_name"],$application["force_install"]));
            }
            $user->addDevice($newDevice);

        }

        return $user->getObject();
    }
}