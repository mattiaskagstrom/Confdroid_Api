<?php

/**
 * Connection between database and API.
 */
spl_autoload_register(function ($class_name) {
    /** @noinspection PhpIncludeInspection */
    include $class_name . '.php';
});


class RequestParser
{

    private $databaseFunctions;

    /**
     * RequestParser constructor.
     */
    function __construct()
    {
        $this->databaseFunctions = new DatabaseFunctions();
    }

    /**
     * Split the get request into the data fetching functions for each unit
     * @param $request , the request to handle
     * @return mixed|string, returns md array with the requested object, or a string if nothing is found
     */
    public function get($request)
    {

        switch ($request[1]) {
            case "user": //user is requested
                if (!isset($request[2])) {//Admin wants to fetch users
                    $this->authorizeAdmin();//Make sure the admin is authorized
                    $searchValue = null;
                    if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];//iff the admin have defined a search, search for that, if not, return all users
                    $users = $this->databaseFunctions->searchUsers($searchValue);
                    return $users;
                } else {
                    if (isset($_GET["imei"])) {//User is requesting himself with a specific device using authToken
                        $user = $this->databaseFunctions->getUserWithAuthtoken($request[2], $_GET["imei"]);

                        if ($user == null) {
                            http_response_code(403);
                            die();
                        }
                        $devices = $user->getDevices();
                        $device = null;
                        if (isset($devices[0])) $device = $devices[0];
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
                        if ($this->authorizeAdmin()) {//admin is requesting a specific user using id
                            $user = $this->databaseFunctions->getUserWithID($request[2]);
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
                    return $this->databaseFunctions->getGroup($request[2]);
                    break;
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->searchGroups($searchValue);
                break;
            case "device":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $device = $this->databaseFunctions->getDevice($request[2]);//with id
                    if ($device == null) {
                        http_response_code(404);
                        return "no device with that imei";
                    } else {
                        return $device->getObject();
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->searchDevices($searchValue);
                break;
            case "application":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $application = $this->databaseFunctions->searchApplications(null, $request[2]);
                    if ($application == null) {
                        http_response_code(404);
                        return "no application with that id";
                    } else {
                        return $application[0];
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->searchApplications($searchValue);
                break;

        }
        http_response_code(404);
        return "no such resource";
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
            $this->databaseFunctions->logout($authToken, $id);
            die();
        }
        if ($this->databaseFunctions->authorizeAdmin($authToken, $id)) {
            return true;
        } else {
            echo "Invalid credentials";
            http_response_code(403);
            die();
        }
    }

    public function post($request)
    {
        $postjson = file_get_contents("php://input");
        switch ($request[1]) {
            case "admin":
                switch ($request[2]) {
                    case "login":
                        return $this->databaseFunctions->login($_POST["username"], $_POST["password"]);
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
                        switch ($request[3]) {
                            case "group":
                                if (isset($request[4])) if ($this->databaseFunctions->addGroupToUser($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                            case "device":
                                if (isset($request[4])) if ($this->databaseFunctions->addDeviceToUser($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToUser($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    } else {

                    }
                } else {
                    if ($this->databaseFunctions->addUser($postjson)) {
                        http_response_code(201);
                    } else {
                        http_response_code(400);
                    }
                }
                break;
            case "group":
                $this->authorizeAdmin();
                if (isset($request[2])) {//groupID
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "application":
                                if (isset($request[4])) {//applicationID
                                    $this->databaseFunctions->addApplicationToGroup($request[2], $request[4]);
                                }
                        }
                    }

                } else {
                    $this->databaseFunctions->createGroup($postjson);
                }
                break;
            default:
                http_response_code(404);
                return "No such unit to get";
        }
        return null;
    }

    public function put($request)
    {
        $this->authorizeAdmin();
        $putjson = file_get_contents("php://input");

        switch ($request[1]) {
            case "user":
                if (isset($request[2])) {
                    $this->databaseFunctions->updateUser($request[2], $putjson);
                }
                break;
            case "device":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->updateDevice($request[2], $putjson)) {

                    } else {
                        http_response_code(400);
                    }
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "xmlsetting":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->updateXmlSetting($request[2], $putjson)) {

                    } else {
                        http_response_code(400);
                    }
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "sqlsetting":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->updateSqlSetting($request[2], $putjson)) {

                    } else {
                        http_response_code(400);
                    }
                    return;
                } else {
                    http_response_code(400);
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
                        switch ($request[3]) {
                            case "group":
                                if (isset($request[4])) if ($this->databaseFunctions->removeGroupFromUser($request[2], $request[4])) http_response_code(204);
                                break;
                            case "device":
                                if (isset($request[4])) if ($this->databaseFunctions->removeDeviceFromUser($request[2], $request[4])) http_response_code(204);
                                break;
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromUser($request[2], $request[4])) http_response_code(204);
                                break;
                        }
                    } else {
                        if (!$this->databaseFunctions->deleteUser($request[2])) http_response_code(400);
                    }
                } else {
                    http_response_code(400);
                }
                break;
            case "group":
                if (isset($request[2])) {
                    $this->databaseFunctions->deleteGroup($request[2]);
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "device":
                if (isset($request[2])) {
                    $this->databaseFunctions->deleteDevice($request[2]);
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "application":
                if (isset($request[2])) {
                    $this->databaseFunctions->deleteApplication($request[2]);
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
}