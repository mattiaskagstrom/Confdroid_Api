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

    public function login($username, $password){

    }

    public function get($request){
        if($request[1] == "user"){

            $stmt = $this->dbc->prepare("SELECT id, name, mail FROM user WHERE auth_token=:authToken");
            $stmt->bindParam(":authToken", $_GET["userAuth"]);
            $stmt->execute();
            $dbuser = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];

            if(isset($_GET["imei"])){
               $stmt = $this->dbc->prepare("SELECT id, name, imei FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id = :userID AND device.imei = :imei");
               $stmt->bindParam(":userID", $dbuser["id"]);
               $stmt->bindParam("imei", $_GET["imei"]);
            }else{
                $stmt = $this->dbc->prepare("SELECT id, name, imei FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id = :userID");
                $stmt->bindParam(":userID", $dbuser["id"]);
            }
            $stmt->execute();
            $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user = new User($dbuser["id"], $dbuser["name"], $dbuser["mail"]);


            foreach ($devices as $device) {
                $newDevice = new Device($device["id"], $device["name"], $device["imei"]);

                $user->addDevice($newDevice);
            }
            return $user->getObject();
        }else{
            return "bamboozle!";
        }
    }

    public function post($request){

    }

    public function put($request){

    }

    public function delete($request){

    }

    private function sqlQuery(){
        //SELECT user.name, device.name FROM device, user, user_device WHERE user_device.user_id = user.id AND user_device.device_id = device.id
    }
}