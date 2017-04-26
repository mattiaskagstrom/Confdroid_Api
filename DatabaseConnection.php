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
     * @param $request, the request to handle
     * @return mixed|string, returns md array with the requested object, or a string if nothing is found
     */
    public function get($request)
    {

        switch ($request[1]) {
            case "user":
                if (!isset($request[2])) {
                    $this->authorizeAdmin();
                    $searchValue = null;
                    if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                    $users = $this->adminFunctions->searchUsers($searchValue, $searchValue);
                    return $users;


                } else {

                    if (isset($_GET["imei"])) {
                        $userValues = $this->applicationFunctions->getUser($request[2]);    //Gets User
                        if ($userValues == null) {
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
                    } else {
                        http_response_code(400);
                    }
                }
                break;
            case "group":
                $this->authorizeAdmin();
                $searchValue = null;
                if(isset($_POST["searchValue"]))$searchValue = $_POST["searchValue"];
                return $this->adminFunctions->searchGroups($searchValue);
                break;
        }
        http_response_code(404);
        return "no such resource";
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
                $this->authorizeAdmin();
                if ($this->adminFunctions->addUser($_POST["name"], $_POST["email"])) {
                    http_response_code(201);

                }

                break;
            default:
                http_response_code(404);
                return "No such unit to get";
        }
        http_response_code(404);
        return "no such resource";
    }

    public function put($request)
    {
        $this->authorizeAdmin();
    }

    public function delete($request)
    {
        $this->authorizeAdmin();
        switch ($request[1]) {
            case "user":
                if (isset($request[2])) {
                    if (!$this->$this->adminFunctions->removeUser(null, $request[2])) http_response_code(400);
                } else {
                    http_response_code(400);
                }

                break;
        }

    }

    private function authorizeAdmin()
    {
        $authToken = null;
        $id = null;
        if (isset($_POST["authToken"]) && isset($_POST["id"])) {
            $authToken = $_POST["authToken"];
            $id = $_POST["id"];
        } else if (isset($_GET["authToken"]) && isset($_GET["id"])) {
            $authToken = $_GET["authToken"];
            $id = $_GET["id"];
        } else {
            echo "missing credentials";
            http_response_code(400);
            die();
        }
        if ($this->adminFunctions->authorizeAdmin($authToken, $id)) {
            return true;
        } else {
            echo "Invalid credentials";
            http_response_code(403);
            die();
        }
    }
}