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
    function __construct()
    {
        $dsn = 'mysql:host=localhost;dbname=confdroid_test';
        $username = 'root';
        $password = '';
        $options = array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        );
        $dbh = new PDO($dsn, $username, $password, $options);

        //$stmt = $dbh->prepare("SELECT * FROM users");
        //$stmt->execute();
    }

    public function login($username, $password){

    }

    public function get($request){
        if($request[1] == "user"){

            if(isset($_GET["imei"])){
                $Device["imei"] = $_GET["imei"];
                $Device["Applications"]["Snapchat"]["apkName"] = "Snapchat-2.8.9.apk";
                $User["Name"] = "Mattias";
                $User["Email"] = "matkag@kth.se";
                $User["Devices"][$_GET["imei"]] = $Device;
                $User["Groups"] = array("Tidaa", "Tutus");

                return $User;
            }else{
                die("Missing variables");
            }
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