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
                        if ($this->authorizeAdmin()) {
                            if (isset($request[3])) {
                                if ($request[3] == "variable") {
                                    if (isset($request[4])) {
                                        if (isset($request[3])) return $this->databaseFunctions->getVariableForUser($request[2], $request[4]); else http_response_code(400);
                                    } else {
                                        if (isset($request[3])) return $this->databaseFunctions->getVariableForUser($request[2]); else http_response_code(400);
                                    }
                                }
                            }

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
                    $device = $this->databaseFunctions->getDevice($request[2], null, false);//with id
                    if ($device == null) {
                        http_response_code(404);
                        return "no device with that id";
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
            case "sqlsetting":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $sqlSetting = $this->databaseFunctions->searchSqlSettings(null, $request[2]);
                    if ($sqlSetting == null) {
                        http_response_code(404);
                        return "no SQL setting with that id";
                    } else {
                        return $sqlSetting[0];
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->searchSqlSettings($searchValue);

                break;
            case "xmlsetting":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    $XmlSetting = $this->databaseFunctions->searchXmlSettings(null, $request[2]);
                    if ($XmlSetting == null) {
                        http_response_code(404);
                        return "no XML setting with that id";
                    } else {
                        return $XmlSetting[0];
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->searchXmlSettings($searchValue);
                break;
            case "variable":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    if ($request[2] == "user") {
                        if (isset($request[3])) return $this->databaseFunctions->getVariableForUser($request[3]); else http_response_code(400);
                    }
                    if (isset($request[3])) {
                        if ($request[3] == "user") {
                            if (isset($request[4])) return $this->databaseFunctions->getVariableForUser($request[4], $request[2]); else http_response_code(400);
                        }
                    } else {
                        $variable = $this->databaseFunctions->getVariable(null, $request[2]);
                        if ($variable == null) {
                            http_response_code(404);
                            return "no variable with that id";
                        } else {
                            return $variable[0];
                        }
                    }
                }
                $searchValue = null;
                if (isset($_GET["searchValue"])) $searchValue = $_GET["searchValue"];
                return $this->databaseFunctions->getVariable($searchValue);
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
                            case "variable":
                                $this->databaseFunctions->setVariable($request[4], $request[2], json_decode($postjson, true)["value"]);
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
                                break;
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->addGroupToUser($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    }

                } else {
                    $this->databaseFunctions->createGroup($postjson);
                }
                break;
            case "application":
                $this->authorizeAdmin();
                if (isset($request[2])) {//applicationID
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "group":
                                if (isset($request[4])) {//applicationID
                                    $this->databaseFunctions->addApplicationToGroup($request[4], $request[2]);
                                }
                                break;
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToUser($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                            case "device":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToDevice($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                            case "sqlsetting":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToSqlSetting($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                            case "xmlsetting":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToXmlSetting($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    }
                } else {
                    if ($this->databaseFunctions->createApplication($postjson)) {
                        http_response_code(201);
                    } else {
                        http_response_code(400);
                    }
                }
                break;
            case "device":
                if (isset($request[2])) {//deviceID
                    $this->authorizeAdmin();
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->addDeviceToUser($request[4], $request[2])) http_response_code(201); else http_response_code(409);
                                break;
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToDevice($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    }
                } else {
                    $this->databaseFunctions->createDevice($postjson);
                }
                break;
            case "sqlsetting":
                $this->authorizeAdmin();
                if (isset($request[2])) {//deviceID
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToSqlSetting($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    }
                } else {
                    $this->databaseFunctions->createSqlSetting($postjson);
                }
                break;
            case "xmlsetting":
                $this->authorizeAdmin();
                if (isset($request[2])) {//deviceID
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->addApplicationToXmlSetting($request[2], $request[4])) http_response_code(201); else http_response_code(409);
                                break;
                        }
                    }
                } else {
                    $this->databaseFunctions->createXmlSetting($postjson);
                }
                break;
            case "variable":
                $this->authorizeAdmin();
                if (isset($request[2])) {
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "user":
                                $this->databaseFunctions->setVariable($request[2], $request[4], json_decode($postjson, true)["value"]);
                                break;
                        }
                    }
                } else {
                    $this->databaseFunctions->createVariable($postjson);
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
                    if (isset($request[3])) {
                        if ($request[3] == "variable") {
                            if (isset($request[4])) {
                                $this->databaseFunctions->setVariable($request[4], $request[2], json_decode($putjson, true)["value"]);
                            } else {
                                http_response_code(400);
                            }
                        }
                    } else {
                        if ($this->databaseFunctions->updateUser($request[2], $putjson)) {

                        } else {
                            http_response_code(400);
                        }
                    }

                } else {
                    http_response_code(400);
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
            case "group":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->updateGroup($request[2], $putjson)) {

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
            case "application":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->updateApplication($request[2], $putjson)) {

                    } else {
                        http_response_code(400);
                    }
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "variable":
                if (isset($request[3]) && isset($request[4])) {
                    if ($request[3] == "user") {
                        $this->databaseFunctions->setVariable($request[2], $request[4], json_decode($putjson, true)["value"]);
                    }
                }

                break;
        }
    }

    public function delete($request)
    {
        $this->authorizeAdmin();
        switch ($request[1]) {
            case "user": //DELETE /user/{userID}/groups/{groupID}.json
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
                            case "varable":
                                if (isset($request[4])) if ($this->databaseFunctions->unsetVariable($request[4], $request[2])) http_response_code(204);
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
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->removeGroupFromUser($request[4], $request[2])) http_response_code(204);
                                break;
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromGroup($request[2], $request[4])) http_response_code(204);
                                break;
                        }
                    } else {
                        if (!$this->databaseFunctions->deleteGroup($request[2])) http_response_code(400);
                    }
                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "device":
                if (isset($request[2])) {
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->removeDeviceFromUser($request[4], $request[2])) http_response_code(204);
                                break;
                            case "application":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromDevice($request[2], $request[4])) http_response_code(204);
                                break;
                        }
                    } else {
                        if (!$this->databaseFunctions->deleteDevice($request[2])) http_response_code(400);
                    }

                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "application":
                if (isset($request[2])) {
                    if (isset($request[3])) {
                        switch ($request[3]) {
                            case "user":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromUser($request[4], $request[2])) http_response_code(204);
                                break;
                            case "device":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromDevice($request[4], $request[2])) http_response_code(204);
                                break;
                            case "group":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromGroup($request[4], $request[2])) http_response_code(204);
                                break;
                            case "sqlsetting":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromSqlSetting($request[4], $request[2])) http_response_code(204);
                                break;
                            case "xmlsetting":
                                if (isset($request[4])) if ($this->databaseFunctions->removeApplicationFromXmlSetting($request[4], $request[2])) http_response_code(204);
                                break;
                        }
                    } else {
                        if (!$this->databaseFunctions->deleteApplication($request[2])) http_response_code(400);
                    }

                    return;
                } else {
                    http_response_code(400);
                }
                break;
            case "xmlsetting":
                if (isset($request[2])) {
                    if ($this->databaseFunctions->deleteXmlSetting($request[2])) {

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
                    if ($this->databaseFunctions->deleteSqlSetting($request[2])) {

                    } else {
                        http_response_code(400);
                    }
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
            case "variable":
                if (isset($request[2])) {
                    $this->databaseFunctions->deleteVariable($request[2]);
                    if (isset($request[3])) {
                        if(isset($request[4])){
                            $this->databaseFunctions->unsetVariable($request[2], $request[4]);
                        }else{
                            http_response_code(400);
                        }
                    } else {

                    }
                }else{
                    http_response_code(400);
                }
                break;
        }

    }
}