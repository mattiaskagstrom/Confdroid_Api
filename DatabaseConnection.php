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
    //private $applicationFunctions;
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
        //$this->applicationFunctions = new ApplicationFunctions($this->dbc);
        $this->adminFunctions = new AdminFunctions($this->dbc);
    }

    /**
     * Split the get request into the data fetching functions for each unit
     * @param $request , the request to handle
     * @return mixed|string, returns md array with the requested object, or a string if nothing is found
     */
    public function get($request)
    {

        switch ($request[1]) {
            case "user":
                if (!isset($request[2])) {//Admin wants to search for a user
                    $this->authorizeAdmin();
                    $searchValue = null;
                    if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                    $users = $this->adminFunctions->searchUsers($searchValue, $searchValue);
                    return $users;
                } else {
                    if (isset($_GET["imei"])) {//User is requesting himself with a specific device
                        $user = $this->adminFunctions->getUser($request[2], $_GET["imei"]);

                        if ($user == null) {
                            http_response_code(403);
                            die();
                        }
                        $devices =$user->getDevices();
                        $device = null;
                        if(isset($devices[0]))$device = $devices[0];
                        if ($device == null) {
                            http_response_code(404);
                            return "No device on this imei, contact administration for support";
                        };                 //Gets the device applications

                        if (isset($device) && isset($user)) {

                            if (isset($_GET["hash"])) {
                                if (md5(json_encode($user->getObject(), JSON_UNESCAPED_UNICODE)) == $_GET["hash"]) {
                                    http_response_code(304);
                                    return "no changes";
                                }
                            }
                            return $user->getObject();
                        } else {
                            http_response_code(404);
                            return "No device found";
                        }
                    } else {
                        if ($this->authorizeAdmin()) {//admin is requesting a specific user
                            $user = $this->adminFunctions->getUser($request[2]);
                            if ($user == null) {
                                http_response_code(404);
                                die();
                            }
                            return $user->getObject();
                        } else {
                            http_response_code(400);
                        }
                    }
                }
                break;
            case "group":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    return $this->adminFunctions->getGroup($request[2]);
                    break;
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->adminFunctions->searchGroups($searchValue);
                break;
            case "device":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $device = $this->adminFunctions->getDevice($request[2])->getObject();
                    if ($device == null) {
                        http_response_code(404);
                        return "no device with that imei";
                    } else {
                        return $device;
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->adminFunctions->searchDevices($searchValue);
                break;
            case "application":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $application = $this->adminFunctions->searchApplications(null, $request[2]);
                    if ($application == null) {
                        http_response_code(404);
                        return "no application with that id";
                    } else {
                        return $application;
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->adminFunctions->searchApplications($searchValue);
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
                if (isset($request[2])) {//authToken
                    if (isset($request[3])) {//group/device/application
                        switch ($request[3]){
                            case "group":
                                if(isset($request[4]))if($this->adminFunctions->addGroupToUser($request[2], $request[4]))http_response_code(201);else http_response_code(409);
                                break;
                            case "device":
                                if(isset($request[4]))if($this->adminFunctions->addDeviceToUser($request[2], $request[4]))http_response_code(201);else http_response_code(409);
                                break;
                            case "application":
                                if(isset($request[4]))if($this->adminFunctions->addApplicationToUser($request[2], $request[4]))http_response_code(201);else http_response_code(409);
                                break;
                        }
                    } else {

                    }
                } else {
                    if ($this->adminFunctions->addUser($_POST["name"], $_POST["email"])) {
                        http_response_code(201);
                    }else{
                        http_response_code(400);
                    }
                }
                break;
            case "group":
                    if(isset($request[2])){//groupID
                        if(isset($request[3])){
                            switch ($request[3]){
                                case "application":
                                    if(isset($request[4])){//applicationID
                                        $this->adminFunctions->addApplicationToGroup($request[2], $request[4]);
                                    }
                            }
                        }

                    }else{

                    }
                break;
            default:
                http_response_code(404);
                return "No such unit to get";
        }
    }

    public function put($request)
    {
        $this->authorizeAdmin();
        $putvars = json_decode(file_get_contents("php://input"));

        switch ($request[1]) {
            case "user":
                if (isset($request[2])) {
                    $this->adminFunctions->updateUser($request[2], $putvars["name"], $putvars["mail"]);
                }
                break;
        }
    }

    public function delete($request)
    {
        $this->authorizeAdmin();
        switch ($request[1]) {
            case "user": //DELETE /user/{authToken}/groups/{groupID}.json
                if (isset($request[2])) {
                    if (isset($request[3])) {
                        switch ($request[3]){
                            case "group":
                                if(isset($request[4]))if($this->adminFunctions->removeGroupFromUser($request[2], $request[4]))http_response_code(204);
                                break;
                            case "device":
                                if(isset($request[4]))if($this->adminFunctions->removeDeviceFromUser($request[2], $request[4]))http_response_code(204);
                                break;
                            case "application":
                                if(isset($request[4]))if($this->adminFunctions->removeApplicationFromUser($request[2], $request[4]))http_response_code(204);
                                break;
                        }
                    } else {
                        if (!$this->adminFunctions->removeUser(null, $request[2])) http_response_code(400);
                    }
                } else {
                    http_response_code(400);
                }
                break;
            case "group":
                if (isset($request[2])) {
                    $this->adminFunctions->deleteGroup($request[2]);
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "device":
                if (isset($request[2])) {
                    $this->adminFunctions->deleteDevice($request[2]);
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "application":
                if (isset($request[2])) {
                    $this->adminFunctions->deleteApplication($request[2]);
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "admin":
                switch ($request[2]) {
                    case "login":
                        $this->authorizeAdmin(true);
                        break;
                }
                break;
        }

    }

    private function authorizeAdmin($logout = false)
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
        if ($logout == true) {
            $this->adminFunctions->logout($authToken, $id);
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