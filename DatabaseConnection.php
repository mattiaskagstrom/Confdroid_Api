<?php

/**
 * Connection between database and API.
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
        $this->applicationFunctions = new ApplicationFunctions($this->dbc);
        $this->adminFunctions = new AdminFunctions($this->dbc);
    }

    /**
     * Split the get request into the data fetching functions for each unit
     * @param $request to handle
     * @return mixed|string, returns md array with the requested object, or a string if nothing is found
     */
    public function get($request)
    {

        switch ($request[1]) {
            case "user":
                if (!isset($request[2])) {
                    if ($this->adminFunctions->authorizeAdmin($_GET["authToken"], $_GET["id"])) {
                        $searchValue=null;
                        if(isset($_GET[searchValue]))$searchValue=$_GET["searchValue"];
                        $users = $this->adminFunctions->searchUsers($searchValue, $searchValue);
                        return $users;
                    }else {
                        http_response_code(403);
                        return "Not Authorized";
                    }


                } else {

                    if (isset($_GET["imei"])) {
                        $userValues = $this->applicationFunctions->authorizeUser($request[2]);    //Gets User
                        if ($userValues == "not authorized") {
                            http_response_code(403);
                            die();
                        }
                        $user = new User($userValues["id"], $userValues["name"], $userValues["mail"]);  //Create User from authorized user
                        $device = $this->applicationFunctions->getDevice($user->getId(), $_GET["imei"]);//Gets Device
                        if ($device == null) {
                            http_response_code(404);
                            return "No device on this imei, contact administration for support";
                        }
                        $device = $this->applicationFunctions->getApplications($device);                 //Gets the device applications

                        if (isset($device) && isset($user)) {
                            $user->addDevice($device);                                                      //Add device to the user
                            return $user->getObject();
                        } else {
                            http_response_code(404);
                            return "No device found";
                        }
                    }else{
                        http_response_code(400);
                    }
                }
        }
    }

    public function post($request)
    {
        switch ($request[1]) {
            case "admin":
                switch ($request[2]) {
                    case "login":
                        return $this->adminFunctions->login($_POST["username"], $_POST["password"]);
                        break;
                    default:
                        http_response_code(404);
                        return "No such unit to get";
                }
                break;
            case "user":
                if ($this->adminFunctions->authorizeAdmin($_POST["authToken"], $_POST["id"])) {
                    if ($this->adminFunctions->addUser($_POST["name"], $_POST["email"])) {
                        http_response_code(201);

                    }
                } else {
                    http_response_code(403);
                    return "Not Authorized";
                }
                break;
            default:
                http_response_code(404);
                return "No such unit to get";
        }
    }

    public function put($request)
    {

    }

    public function delete($request)
    {
        if ($this - $this->adminFunctions->authorizeAdmin($_POST["authToken"], $_POST["id"])) {
            switch ($request[1]) {
                case "user":
                    if (isset($request[2])) {
                        if(!$this->$this->adminFunctions->removeUser(null, $request[2]))http_response_code(400);
                    }else{
                        http_response_code(400);
                    }

                    break;
            }
        } else {
            http_response_code(403);
            return "Not Authorized";
        }
    }

    private function sqlQuery()
    {
        //SELECT user.name, device.name FROM device, user, user_device WHERE user_device.user_id = user.id AND user_device.device_id = device.id
    }
}