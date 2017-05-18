<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:44
 */
spl_autoload_register(function ($class_name) {
    /** @noinspection PhpIncludeInspection */
    include $class_name . '.php';
});

class DatabaseFunctions
{
    /**
     * @var PDO
     */
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

    /**
     * Login function for admin from the webbInterface
     * @param $username
     * @param $password
     * @return mixed
     */
    public function login($username, $password)
    {
        $stmt = $this->dbc->prepare("SELECT id FROM admin WHERE username=:username AND password=:password");
        $salt = $this->dbc->prepare("SELECT salt FROM admin WHERE username=:username");
        $salt->bindParam(":username", $username);
        $salt->execute();
        $salt = $salt->fetch()["salt"];
        $passwordHash = hash("sha512", $password . $salt);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $passwordHash);
        $stmt->execute();
        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $adminSession["id"] = null;
        $adminSession["Token"] = null;

        if (!isset($user[0]["id"])) {

            return null;
        } else {
            $adminSession["id"] = $user[0]["id"];
            $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
            $insertAuth = $this->dbc->prepare("UPDATE admin SET authToken=:authToken, logintime=NOW(), ipaddr=:ipaddr WHERE username=:username AND password=:password ");
            $insertAuth->bindParam(":authToken", $token);
            $insertAuth->bindParam(":username", $username);
            $insertAuth->bindParam(":password", $passwordHash);
            $ip = $this->getRealIpAddr();
            $insertAuth->bindParam(":ipaddr", $ip);
            $insertAuth->execute();
            $adminSession["Token"] = $token;
            return $adminSession;
        }
    }

    function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Authorize the admin with the session variables created on login.
     * @param $authToken
     * @param $adminId
     * @return bool
     */
    public function authorizeAdmin($authToken, $adminId)
    {
        $stmt = $this->dbc->prepare("SELECT * FROM admin WHERE id=:adminID AND authToken=:authToken AND TIME_TO_SEC(TIMEDIFF(NOW(),logintime)) < 600;");
        $stmt->bindParam(":adminID", $adminId);
        $stmt->bindParam(":authToken", $authToken);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($result) == 1) {
            $stmt = $this->dbc->prepare("UPDATE admin SET logintime=NOW() WHERE id=:adminID AND authToken=:authToken;");
            $stmt->bindParam(":adminID", $adminId);
            $stmt->bindParam(":authToken", $authToken);
            $stmt->execute();
            return true;
        }
        return false;
    }

    public function logout($authToken, $adminId)
    {
        $stmt = $this->dbc->prepare("UPDATE admin SET authToken=NULL WHERE id=:adminID AND authToken=:authToken;");
        $stmt->bindParam(":adminID", $adminId);
        $stmt->bindParam(":authToken", $authToken);
        $stmt->execute();
    }

    /**
     * Search on user
     * @param string $name
     * @return Mixed
     */
    public function searchUsers($name = "")
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE name LIKE :name"); //OR mail LIKE :mail
        $name = "%" . $name . "%";
        $stmt->bindParam(":name", $name);
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        $users = array(User::class);
        if (isset($queriedUsers[0])) {
            foreach ($queriedUsers as $user) {
                $users[$i] = new User($user["id"], $user["name"], $user["mail"], $user["auth_token"], $user["date_created"]);
                $users[$i]->addDevices($this->getDevices($user["id"]));
                $users[$i]->addGroups($this->getGroupsByUserId($user["id"]));
                $users[$i] = $users[$i]->getObject();
                $i++;
            }
            return $users;
        }

        return null;
    }

    /**
     * Returns array with devices
     * @param $userId
     * @return Device[]
     */
    private function getDevices($userId)
    {
        $stmt = $this->dbc->prepare("SELECT id, name, imei, date_created FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id=:userId");
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $devices = null;
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $devices[$i] = new Device($stmtAnswer[$i]["id"], $stmtAnswer[$i]["name"], $stmtAnswer[$i]["imei"], $stmtAnswer[$i]["date_created"]);
                $devices[$i] = $this->getApplications($devices[$i]);
            }
            return $devices;
        }
        return null;
    }

    /**
     * Returns all the applications on a device.
     * @param $device
     * @param $user
     * @return Device
     */
    private function getApplications(Device $device = null, User $user = null)
    {
        if ($user == null && $device != null) {
            $stmt = $this->dbc->prepare("SELECT application.* FROM application, application_device WHERE application.id = application_device.application_id AND application_device.device_id =:deviceID");
            $deviceId = $device->getId();
            $stmt->bindParam(":deviceID", $deviceId);
            $addTo = $device;
        } else if ($user != null && $device != null) {
            $stmt = $this->dbc->prepare("SELECT DISTINCT application.* FROM `application`, device, application_device, user, user_device WHERE `application`.id=application_device.application_id AND application_device.device_id=device.id AND device.id=:deviceID AND user.id = user_device.user_id AND application_device.device_id = user_device.device_id AND user.id=:userID UNION DISTINCT SELECT DISTINCT application.* FROM `application`, `user`, application_user WHERE `application`.id=application_user.application_id AND application_user.user_id=`user`.id AND user.id=:userID UNION DISTINCT SELECT DISTINCT application.* FROM `application`, `group`, application_group, user, user_group WHERE `application`.id=application_group.application_id AND application_group.group_id=`group`.id AND user.id = user_group.user_id AND application_group.group_id = user_group.group_id AND user.id=:userID");
            $userID = $user->getId();
            $stmt->bindParam(":userID", $userID);
            $deviceId = $device->getId();
            $stmt->bindParam(":deviceID", $deviceId);
            $addTo = $device;
        } else if ($device == null && $user != null) {
            $stmt = $this->dbc->prepare("SELECT application.* FROM application, application_user WHERE application.id = application_user.application_id AND application_user.user_id =:userID");
            $userID = $user->getId();
            $stmt->bindParam(":userID", $userID);
            $addTo = $user;
        } else {
            return $device;
        }

        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sqlSettingStmt = $this->dbc->prepare("SELECT sql_setting.* FROM application, sql_setting, application_sql_setting WHERE application.id = application_sql_setting.application_id AND sql_setting.id = application_sql_setting.sql_setting_id AND application.id=:appID");
        $xmlSettingStmt = $this->dbc->prepare("SELECT xml_setting.* FROM application, xml_setting, application_xml_setting WHERE application.id = application_xml_setting.application_id AND xml_setting.id = application_xml_setting.xml_setting_id AND application.id=:appID");

        foreach ($applications as $application) {

            $sqlSettingStmt->bindParam(":appID", $application["id"]);
            $sqlSettingStmt->execute();
            $sqlSettings = $sqlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $xmlSettingStmt->bindParam(":appID", $application["id"]);
            $xmlSettingStmt->execute();
            $xmlSettings = $xmlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $app = new Application($application["id"], $application["data_dir"], $application["apk_name"], $application["apk_url"], $application["friendly_name"], $application["force_install"], $application["package_name"]);
            foreach ($sqlSettings as $sqlSetting) {
                $sqlSetting = new SqlSetting($sqlSetting["id"], $sqlSetting["sql_location"], $sqlSetting["sql_setting"], $sqlSetting["friendly_name"]);
                if ($user != null) {
                    $this->evaluateSqlSettingVariables($sqlSetting, $user);
                }
                $app->addSQL_setting($sqlSetting);
            }
            foreach ($xmlSettings as $xmlSetting) {
                $xmlSetting = new XmlSetting($xmlSetting["id"], $xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"], $xmlSetting["friendly_name"]);
                if ($user != null) {
                    $this->evaluateXmlSettingVariables($xmlSetting, $user);
                }
                $app->addXML_setting($xmlSetting);
            }

            $addTo->addApplication($app);
        }
        return $addTo;
    }

    /**
     * @param $userId
     * @return Group[]
     */
    private function getGroupsByUserId($userId)
    {
        $stmt = $this->dbc->prepare("SELECT id, prio, name FROM `group`, user_group WHERE `group`.`id` = user_group.group_id AND user_group.user_id=:userId");
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $groups = array(Group::class);
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $groups[$i] = new Group($stmtAnswer[$i]["id"], $stmtAnswer[$i]["prio"], $stmtAnswer[$i]["name"]);
            }
            return $groups;
        }
        return null;
    }

    public function getUserWithAuthtoken($authToken, $imei)
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE BINARY auth_token=:authToken");
        $stmt->bindParam(":authToken", $authToken);
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($queriedUsers[0])) {
            $user = new User($queriedUsers[0]["id"], $queriedUsers[0]["name"], $queriedUsers[0]["mail"], $queriedUsers[0]["auth_token"], $queriedUsers[0]["date_created"]);
            $device = $this->getDevice($imei, $user, true);
            if ($device) $user->addDevice($device); else http_response_code(400);
            $user->addGroups($this->getGroupsByUserId($user->getId()));
            $user = $this->getApplications(null, $user);
            return $user;
        }

        return null;
    }

    public function getDevice($identifier, User $user = null, $isImei = false)
    {
        if ($isImei) {
            $stmt = $this->dbc->prepare("SELECT id FROM device WHERE imei=:imei");
            $stmt->bindParam(":imei", $identifier);
            $stmt->execute();
            $id = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]["id"];
        } else {
            $id = $identifier;
        }
        if ($user == null) {
            $stmt = $this->dbc->prepare("SELECT `device`.* FROM device WHERE device.id=:id");
            $stmt->bindParam(":id", $id);
            $userStmt = $this->dbc->prepare("SELECT `user`.* FROM device, user, user_device WHERE user_device.device_id=:id AND user_device.device_id=device.id AND user_device.user_id=user.id");
            $userStmt->bindParam(":id", $id);
            $userStmt->execute();
            $userStmtResult = $userStmt->fetchAll(PDO::FETCH_ASSOC);
            if (isset($userStmtResult[0])) $deviceOwner = new User($userStmtResult[0]["id"], $userStmtResult[0]["name"], $userStmtResult[0]["mail"], $userStmtResult[0]["auth_token"], $userStmtResult[0]["date_created"]);

        } else {
            $stmt = $this->dbc->prepare("SELECT device.* FROM device, user_device WHERE device.id=:id AND user_device.device_id=device.id AND user_device.user_id=:userID");
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":userID", $user->getID());
        }

        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $device = new Device($stmtAnswer[0]["id"], $stmtAnswer[0]["name"], $stmtAnswer[0]["imei"], $stmtAnswer[0]["date_created"]);
            $device = $this->getApplications($device, $user);
            if (isset($deviceOwner)) $device->setUser($deviceOwner->getObject());

            return $device;
        }
        return null;
    }

    public function getUserWithID($id)
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE id=:id");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($queriedUsers[0])) {
            $user = new User($queriedUsers[0]["id"], $queriedUsers[0]["name"], $queriedUsers[0]["mail"], $queriedUsers[0]["auth_token"], $queriedUsers[0]["date_created"]);
            $user->addDevices($this->getDevices($user->getId()));
            $user->addGroups($this->getGroupsByUserId($user->getId()));
            $user->addVariables($this->getVariableForUser($user->getId()));
            $user = $this->getApplications(null, $user);

            return $user;
        }

        return null;
    }


    public function createDevice($json)
    {
        $device = json_decode($json, true);

        if (!isset($device["name"]) || !isset($device["imei"])) {
            http_response_code(400);
            return false;
        }
        if ($device["name"] == "" || $device["imei"] == "") {
            http_response_code(400);
            return false;
        }

        $stmt = $this->dbc->prepare("INSERT INTO device(name, imei) VALUES(:name, :imei)");
        $stmt->bindParam(":name", $device["name"]);
        $stmt->bindParam(":imei", $device["imei"]);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) {
            return true;
        }
        return false;
    }

    public function updateDevice($deviceID, $json)
    {
        $device = json_decode($json, true);
        $query = "";
        if (isset($device["imei"])) {
            $query .= "imei=:imei ";
        }
        if (isset($device["imei"]) && isset($device["name"])) {
            $query .= ", ";
        }
        if (isset($device["name"])) {
            $query .= "name=:name ";
        }
        $check = $this->dbc->prepare("SELECT * FROM device WHERE id=:deviceID");
        $check->bindParam(":deviceID", $deviceID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No device with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE device SET $query WHERE id=:deviceID");
        $stmt->bindParam(":deviceID", $deviceID);
        if (isset($device["imei"])) {
            $stmt->bindParam(":imei", $device["imei"]);
        }
        if (isset($device["name"])) {
            $stmt->bindParam(":name", $device["name"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function addDeviceToUser($userID, $deviceID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO user_device(user_id, device_id) VALUES(:userID, :deviceID)");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":deviceID", $deviceID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function removeDeviceFromUser($userID, $deviceID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM user_device WHERE device_id=:deviceID AND user_id=:userID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":deviceID", $deviceID);
        $stmt->execute();
        return true;
    }

    public function searchDevices($searchValue)
    {
        $stmt = $this->dbc->prepare("SELECT id, name, imei, date_created FROM device WHERE device.name LIKE :searchValue OR device.imei LIKE :searchValue");
        $searchValue = "%" . $searchValue . "%";
        $stmt->bindParam(":searchValue", $searchValue);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $devices = array(Device::class);
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $devices[$i] = new Device($stmtAnswer[$i]["id"], $stmtAnswer[$i]["name"], $stmtAnswer[$i]["imei"], $stmtAnswer[$i]["date_created"]);
                $devices[$i] = $this->getApplications($devices[$i]);
                $devices[$i] = $devices[$i]->getObject();
            }
            return $devices;
        }
        return null;
    }

    public function addApplicationToUser($userID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_user(user_id, application_id) VALUES(:userID, :applicationID)");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function addApplicationToGroup($groupID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_group(group_id, application_id) VALUES(:groupID, :applicationID)");
        $stmt->bindParam(":groupID", $groupID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function addApplicationToDevice($deviceID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_device(device_id, application_id) VALUES(:deviceID, :applicationID)");
        $stmt->bindParam(":deviceID", $deviceID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function addApplicationToSqlSetting($sqlSettingID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_sql_setting(sql_setting_id, application_id) VALUES(:sqlSettingID, :applicationID)");
        $stmt->bindParam(":sqlSettingID", $sqlSettingID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function addApplicationToXmlSetting($xmlSettingID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_xml_setting(xml_setting_id, application_id) VALUES(:xmlSettingID, :applicationID)");
        $stmt->bindParam(":xmlSettingID", $xmlSettingID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }


    public function removeApplicationFromUser($userID, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_user WHERE application_id=:applicationID AND user_id=:userID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        return true;
    }


    public function removeApplicationFromGroup($groupID, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_group WHERE group_id=:groupID AND application_id=:applicationID");
        $stmt->bindParam(":groupID", $groupID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function removeApplicationFromDevice($deviceID, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_device WHERE device_id=:deviceID AND application_id=:applicationID");
        $stmt->bindParam(":deviceID", $deviceID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function removeApplicationFromSqlSetting($sqlSettingID, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_sql_setting WHERE sql_setting_id=:sqlSettingID AND application_id=:applicationID");
        $stmt->bindParam(":sqlSettingID", $sqlSettingID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function removeApplicationFromXmlSetting($xmlSettingID, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_xml_setting WHERE xml_setting_id=:xmlSettingID AND application_id=:applicationID");
        $stmt->bindParam(":xmlSettingID", $xmlSettingID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function createApplication($json)
    {
        $application = json_decode($json, true);

        if (!isset($application["name"]) || !isset($application["packageName"])) {
            http_response_code(400);
            return false;
        }
        if ($application["name"] == "" || $application["packageName"] == "") {
            http_response_code(400);
            return false;
        }
        if (!isset($application["apkName"])) $application["apkName"] = "";
        if (!isset($application["forceInstall"])) $application["forceInstall"] = "";
        if (!isset($application["dataDir"])) $application["dataDir"] = "";
        if (!isset($application["apkURL"])) $application["apkURL"] = "";

        $stmt = $this->dbc->prepare("INSERT INTO application(apk_name, apk_url, force_install, package_name, data_dir, friendly_name) VALUES(:apk_name, :apk_url, :force_install, :package_name, :data_dir, :friendly_name)");
        $stmt->bindParam(":apk_name", $application["apkName"]);
        $stmt->bindParam(":apk_url", $application["apkURL"]);
        $stmt->bindParam(":force_install", $application["forceInstall"]);
        $stmt->bindParam(":package_name", $application["packageName"]);
        $stmt->bindParam(":data_dir", $application["dataDir"]);
        $stmt->bindParam(":friendly_name", $application["name"]);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) {
            return true;
        } else {
            return false;
        }

    }

    public function searchApplications($searchValue = null, $appID = null)
    {
        if (!$appID) {
            $stmt = $this->dbc->prepare("SELECT * FROM application WHERE friendly_name LIKE :searchValue OR package_name LIKE :searchValue OR apk_name LIKE :searchValue");
            $searchValue = "%" . $searchValue . "%";
            $stmt->bindParam(":searchValue", $searchValue);
        } else {
            $stmt = $this->dbc->prepare("SELECT * FROM application WHERE id=:appID");
            $stmt->bindParam(":appID", $appID);
        }
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sqlSettingStmt = $this->dbc->prepare("SELECT sql_setting.* FROM application, sql_setting, application_sql_setting WHERE application.id = application_sql_setting.application_id AND sql_setting.id = application_sql_setting.sql_setting_id AND application.id=:appID");
        $xmlSettingStmt = $this->dbc->prepare("SELECT xml_setting.* FROM application, xml_setting, application_xml_setting WHERE application.id = application_xml_setting.application_id AND xml_setting.id = application_xml_setting.xml_setting_id AND application.id=:appID");
        $usersStmt = $this->dbc->prepare("SELECT user.* FROM user, application_user WHERE application_user.user_id=user.id AND application_user.application_id=:appID");
        $groupsStmt = $this->dbc->prepare("SELECT `group`.* FROM `group`, application_group WHERE application_group.group_id=`group`.id AND application_group.application_id=:appID");
        $devicesStmt = $this->dbc->prepare("SELECT device.* FROM device, application_device WHERE application_device.device_id=device.id AND application_device.application_id=:appID");
        $appArray = array();
        foreach ($applications as $application) {
            $usersStmt->bindParam(":appID", $application["id"]);
            $usersStmt->execute();
            $groupsStmt->bindParam(":appID", $application["id"]);
            $groupsStmt->execute();
            $devicesStmt->bindParam(":appID", $application["id"]);
            $devicesStmt->execute();
            $sqlSettingStmt->bindParam(":appID", $application["id"]);
            $sqlSettingStmt->execute();
            $sqlSettings = $sqlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $xmlSettingStmt->bindParam(":appID", $application["id"]);
            $xmlSettingStmt->execute();
            $xmlSettings = $xmlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $app = new Application($application["id"], $application["data_dir"], $application["apk_name"], $application["apk_url"], $application["friendly_name"], $application["force_install"], $application["package_name"]);
            foreach ($sqlSettings as $sqlSetting) {
                $app->addSQL_setting(new SqlSetting($sqlSetting["id"], $sqlSetting["sql_location"], $sqlSetting["sql_setting"], $sqlSetting["friendly_name"]));
            }
            foreach ($xmlSettings as $xmlSetting) {
                $app->addXML_setting(new XmlSetting($xmlSetting["id"], $xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"], $xmlSetting["friendly_name"]));
            }
            $app->setUsers($usersStmt->fetchAll(PDO::FETCH_ASSOC));
            $app->setGroups($groupsStmt->fetchAll(PDO::FETCH_ASSOC));
            $app->setDevices($devicesStmt->fetchAll(PDO::FETCH_ASSOC));
            array_push($appArray, $app->getObject());
        }

        return $appArray;
    }

    public function searchSqlSettings($searchValue = null, $sqlSettingID = null)
    {
        if (!$sqlSettingID) {
            $stmt = $this->dbc->prepare("SELECT * FROM sql_setting WHERE friendly_name LIKE :searchValue");
            $searchValue = "%" . $searchValue . "%";
            $stmt->bindParam(":searchValue", $searchValue);
        } else {
            $stmt = $this->dbc->prepare("SELECT * FROM sql_setting WHERE id=:sqlSettingID");
            $stmt->bindParam(":sqlSettingID", $sqlSettingID);
        }
        $stmt->execute();
        $sqlSettingArray = array();
        $sqlSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sqlSettings as $sqlSetting) {
            array_push($sqlSettingArray, (new SqlSetting($sqlSetting["id"], $sqlSetting["sql_location"], $sqlSetting["sql_setting"], $sqlSetting["friendly_name"]))->getObject());
        }
        return $sqlSettingArray;
    }

    public function searchXmlSettings($searchValue = null, $XmlSettingID = null)
    {
        if (!$XmlSettingID) {
            $stmt = $this->dbc->prepare("SELECT * FROM xml_setting WHERE friendly_name LIKE :searchValue");
            $searchValue = "%" . $searchValue . "%";
            $stmt->bindParam(":searchValue", $searchValue);
        } else {
            $stmt = $this->dbc->prepare("SELECT * FROM xml_setting WHERE id=:XmlSettingID");
            $stmt->bindParam(":XmlSettingID", $XmlSettingID);
        }
        $stmt->execute();
        $xmlSettingArray = array();
        $XmlSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($XmlSettings as $xmlSetting) {
            array_push($xmlSettingArray, (new XmlSetting($xmlSetting["id"], $xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"], $xmlSetting["friendly_name"]))->getObject());
        }
        return $xmlSettingArray;
    }


    public function removeGroupFromUser($userID, $groupID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM user_group WHERE group_id=:groupID AND user_id=:userID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        return true;
    }

    public function addGroupToUser($userID, $groupID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO user_group(user_id, group_id) VALUES(:userID, :groupID)");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function createGroup($json)
    {
        $group = json_decode($json, true);
        if (!isset($group["prio"])) $group["prio"] = 50;
        if (!isset($group["name"])) {
            http_response_code(400);
            return false;
        }
        if ($group["name"] == "") {
            http_response_code(400);
            return false;
        }
        $stmt = $this->dbc->prepare("INSERT INTO `group`(prio, name) VALUES(:prio, :name)");
        $stmt->bindParam(":prio", $group["prio"]);
        $stmt->bindParam(":name", $group["name"]);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }


    public function addUser($json)
    {

        $user = json_decode($json, true);
        if (isset($user["email"]) && isset($user["name"])) {
            if ($user["email"] != "" && $user["name"] != "") {
                $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
                $stmt = $this->dbc->prepare("INSERT INTO user(name, mail, auth_token) VALUES(:name, :mail, :authToken)");
                $stmt->bindParam(":name", $user["name"]);
                $stmt->bindParam(":mail", $user["email"]);
                $stmt->bindParam(":authToken", $token);
                $stmt->execute();
                if ($stmt->errorCode() == 00000) return true;
                return false;
            }
        }
        http_response_code(400);
        return false;
    }

    public function deleteUser($id)
    {
        $stmt = $this->dbc->prepare("DELETE FROM user WHERE id=:id;");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function deleteDevice($deviceID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `device` WHERE id=:deviceID");
        $stmt->bindParam(":deviceID", $deviceID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }


    public function deleteApplication($applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `application` WHERE id=:applicationID");
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function deleteSqlSetting($sqlSettingID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `sql_setting` WHERE id=:sqlSettingID");
        $stmt->bindParam(":sqlSettingID", $sqlSettingID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function deleteXmlSetting($xmlSettingID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `xml_setting` WHERE id=:xmlSettingID");
        $stmt->bindParam(":xmlSettingID", $xmlSettingID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function createSqlSetting($json)
    {
        $sqlSetting = json_decode($json, true);
        if (!isset($sqlSetting["query"]) || !isset($sqlSetting["dblocation"]) || !isset($sqlSetting["name"])) {
            http_response_code(400);
            return false;
        }
        if ($sqlSetting["name"] == "" || $sqlSetting["dblocation"] == "" || $sqlSetting["name"] == null) {
            http_response_code(400);
            return false;
        }
        $stmt = $this->dbc->prepare("INSERT INTO `sql_setting`(sql_setting, sql_location, friendly_name) VALUES(:sqlquery, :dblocation, :name)");
        $stmt->bindParam(":sqlquery", $sqlSetting["query"]);
        $stmt->bindParam(":dblocation", $sqlSetting["dblocation"]);
        $stmt->bindParam(":name", $sqlSetting["name"]);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function createXmlSetting($json)
    {
        $xmlSetting = json_decode($json, true);
        if (!isset($xmlSetting["fileLocation"]) || !isset($xmlSetting["regexp"]) || !isset($xmlSetting["replaceWith"]) || !isset($xmlSetting["name"])) {
            http_response_code(400);
            return false;
        }
        if ($xmlSetting["name"] == "" || $xmlSetting["regexp"] == "" || $xmlSetting["replaceWith"] == "" || $xmlSetting["name"] == null) {
            http_response_code(400);
            return false;
        }
        $stmt = $this->dbc->prepare("INSERT INTO `xml_setting`(regularexp,replacewith, file_location, friendly_name) VALUES(:regularexp, :replaceWith, :fileLocation, :name)");
        $stmt->bindParam(":regularexp", $xmlSetting["regexp"]);
        $stmt->bindParam(":replaceWith", $xmlSetting["replaceWith"]);
        $stmt->bindParam(":fileLocation", $xmlSetting["fileLocation"]);
        $stmt->bindParam(":name", $xmlSetting["name"]);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function updateApplication($applicationID, $putJSON)
    {
        $application = json_decode($putJSON, true);
        $query = "";
        if (isset($application["name"])) {//friendly_name
            $query .= "friendly_name=:name ";
        }
        if (isset($application["apkName"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "apk_name=:apkName ";
        }
        if (isset($application["forceInstall"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "force_install=:forceInstall ";
        }
        if (isset($application["packageName"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "package_name=:packageName ";
        }
        if (isset($application["dataDir"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "data_dir=:dataDir ";
        }
        if (isset($application["apkURL"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "apk_url=:apkURL ";
        }

        $check = $this->dbc->prepare("SELECT * FROM application WHERE id=:id");
        $check->bindParam(":id", $applicationID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No application with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE application SET $query WHERE id=:id");
        $stmt->bindParam(":id", $applicationID);
        if (isset($application["name"])) {
            $stmt->bindParam(":name", $application["name"]);
        }
        if (isset($application["apkName"])) {
            $stmt->bindParam(":apkName", $application["apkName"]);
        }
        if (isset($application["forceInstall"])) {
            $stmt->bindParam(":forceInstall", $application["forceInstall"]);
        }
        if (isset($application["packageName"])) {
            $stmt->bindParam(":packageName", $application["packageName"]);
        }
        if (isset($application["dataDir"])) {
            $stmt->bindParam(":dataDir", $application["dataDir"]);
        }
        if (isset($application["apkURL"])) {
            $stmt->bindParam(":apkURL", $application["apkURL"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            echo $stmt->queryString;
            print_r($stmt->errorInfo());
            return false;
        }
    }

    public function updateUser($userID, $putJSON)
    {
        $user = json_decode($putJSON, true);
        $query = "";
        if (isset($user["name"])) {
            $query .= "name=:name ";
        }
        if (isset($user["email"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "mail=:email ";
        }
        if (isset($user["authToken"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "auth_token=:authToken ";
        }
        $check = $this->dbc->prepare("SELECT * FROM user WHERE id=:id");
        $check->bindParam(":id", $userID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No user with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE user SET $query WHERE id=:id");
        $stmt->bindParam(":id", $userID);
        if (isset($user["name"])) {
            $stmt->bindParam(":name", $user["name"]);
        }
        if (isset($user["email"])) {
            $stmt->bindParam(":email", $user["email"]);
        }
        if (isset($user["authToken"])) {
            $stmt->bindParam(":authToken", $user["authToken"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function searchGroups($groupName)
    {
        $stmt = $this->dbc->prepare("SELECT id, prio, name FROM `group` WHERE name LIKE :groupName");
        $groupName = "%" . $groupName . "%";
        $stmt->bindParam(":groupName", $groupName);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $groups = array(Group::class);
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $groups[$i] = new Group($stmtAnswer[$i]["id"], $stmtAnswer[$i]["prio"], $stmtAnswer[$i]["name"]);
                $groups[$i] = $groups[$i]->getObject();
            }
            return $groups;
        }

        return null;
    }

    public function getGroup($groupID)
    {
        $groupstmt = $this->dbc->prepare("SELECT id, prio, name FROM `group` WHERE id=:groupID");
        $usersstmt = $this->dbc->prepare("SELECT * FROM user, user_group WHERE user.id = user_group.user_id AND user_group.group_id=:groupID");
        $applicationsstmt = $this->dbc->prepare("SELECT * FROM application, application_group WHERE application.id=application_group.application_id AND application_group.group_id=:groupID");
        $groupstmt->bindParam(":groupID", $groupID);
        $groupstmt->execute();
        $groupstmtAnswer = $groupstmt->fetchAll(PDO::FETCH_ASSOC);
        $usersstmt->bindParam(":groupID", $groupID);
        $usersstmt->execute();
        $userstmtAnswer = $usersstmt->fetchAll(PDO::FETCH_ASSOC);
        $applicationsstmt->bindParam(":groupID", $groupID);
        $applicationsstmt->execute();
        $applicationsstmtAnswer = $applicationsstmt->fetchAll(PDO::FETCH_ASSOC);
        $group = new Group($groupstmtAnswer[0]["id"], $groupstmtAnswer[0]["prio"], $groupstmtAnswer[0]["name"]);
        foreach ($userstmtAnswer as $uservalues) {
            $group->addUser(new User($uservalues["id"], $uservalues["name"], $uservalues["mail"], $uservalues["auth_token"], $uservalues["date_created"]));
        }
        foreach ($applicationsstmtAnswer as $applicationValues) {

            $group->addApplication(new Application($applicationValues["id"], $applicationValues["data_dir"], $applicationValues["apk_name"], $applicationValues["apk_url"], $applicationValues["friendly_name"], $applicationValues["force_install"], $applicationValues["package_name"]));
        }

        return $group->getObject();


    }

    public function updateGroup($groupID, $putJSON)
    {
        $group = json_decode($putJSON, true);
        $query = "";
        if (isset($group["prio"])) {
            $query .= "prio=:prio ";
        }

        if (isset($group["name"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "name=:name ";
        }

        $check = $this->dbc->prepare("SELECT * FROM `group` WHERE id=:id");
        $check->bindParam(":id", $groupID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No group with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE `group` SET $query WHERE id=:id");
        $stmt->bindParam(":id", $groupID);
        if (isset($group["prio"])) {
            $stmt->bindParam(":prio", $group["prio"]);
        }
        if (isset($group["name"])) {
            $stmt->bindParam(":name", $group["name"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function deleteGroup($groupID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `group` WHERE id=:groupID");
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function updateSqlSetting($sqlSettingID, $json)
    {
        $sqlSetting = json_decode($json, true);
        $query = "";
        if (isset($sqlSetting["dblocation"])) {
            $query .= "sql_location=:dblocation ";
        }

        if (isset($sqlSetting["query"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "sql_setting=:query ";
        }
        if (isset($sqlSetting["name"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "friendly_name=:name ";
        }
        $check = $this->dbc->prepare("SELECT * FROM sql_setting WHERE id=:id");
        $check->bindParam(":id", $sqlSettingID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No sql setting with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE sql_setting SET $query WHERE id=:id");
        $stmt->bindParam(":id", $sqlSettingID);
        if (isset($sqlSetting["dblocation"])) {
            $stmt->bindParam(":dblocation", $sqlSetting["dblocation"]);
        }
        if (isset($sqlSetting["query"])) {
            $stmt->bindParam(":query", $sqlSetting["query"]);
        }
        if (isset($sqlSetting["name"])) {
            $stmt->bindParam(":name", $sqlSetting["name"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }


    public function updateXmlSetting($xmlSettingID, $json)
    {
        $xmlSetting = json_decode($json, true);
        $query = "";
        if (isset($xmlSetting["fileLocation"])) {
            $query .= "file_location=:fileLocation ";
        }

        if (isset($xmlSetting["regexp"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "regularexp=:regexp ";
        }

        if (isset($xmlSetting["replaceWith"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "replacewith=:replaceWith ";
        }
        if (isset($xmlSetting["name"])) {
            if (substr($query, -1) != ',') $query .= ",";
            $query .= "friendly_name=:name ";
        }
        $check = $this->dbc->prepare("SELECT * FROM xml_setting WHERE id=:id");
        $check->bindParam(":id", $xmlSettingID);
        $check->execute();
        if ($check->rowCount() < 1) {
            echo "No xml setting with that id!";
            http_response_code(404);
            return true;
        }
        $stmt = $this->dbc->prepare("UPDATE xml_setting SET $query WHERE id=:id");
        $stmt->bindParam(":id", $xmlSettingID);
        if (isset($xmlSetting["fileLocation"])) {
            $stmt->bindParam(":fileLocation", $xmlSetting["fileLocation"]);
        }
        if (isset($xmlSetting["regexp"])) {
            $stmt->bindParam(":regexp", $xmlSetting["regexp"]);
        }
        if (isset($xmlSetting["replaceWith"])) {
            $stmt->bindParam(":replaceWith", $xmlSetting["replaceWith"]);
        }
        if (isset($xmlSetting["name"])) {
            $stmt->bindParam(":name", $xmlSetting["name"]);
        }
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    private function evaluateSqlSettingVariables(SqlSetting $sqlSetting, User $user)
    {
        $stmt = $this->dbc->prepare("SELECT value FROM user_variable WHERE user_id=:userID AND variables_id=(SELECT id FROM variables WHERE name=:variableName)");
        $userID = $user->getId();
        $stmt->bindParam(":userID", $userID);
        $query = $sqlSetting->getQuery();
        $dbLocation = $sqlSetting->getDblocation();
        preg_match_all("/\\{%(.*?)%\\}/", $query, $queryMatches);
        preg_match_all("/\\{%(.*?)%\\}/", $dbLocation, $dbLocationMatches);
        $matches = $queryMatches[1];
        array_merge($matches, $dbLocationMatches[1]);
        foreach ($matches as $match) {
            $stmt->bindParam(":variableName", $match);
            $stmt->execute();
            $sqlSetting->replaceVariable($match, $stmt->fetch()["value"], "query");
        }
    }

    private function evaluateXmlSettingVariables(XmlSetting $xmlSetting, User $user)
    {
        $stmt = $this->dbc->prepare("SELECT value FROM user_variable WHERE user_id=:userID AND variables_id=(SELECT id FROM variables WHERE name=:variableName)");
        $userID = $user->getId();
        $stmt->bindParam(":userID", $userID);
        $fileLocation = $xmlSetting->getFileLocation();
        $replaceWith = $xmlSetting->getReplaceWith();
        preg_match_all("/\\{%(.*?)%\\}/", $fileLocation, $fileLocationMatches);
        preg_match_all("/\\{%(.*?)%\\}/", $replaceWith, $replaceWithMatches);
        $matches = $fileLocationMatches[1];
        array_merge($matches, $replaceWithMatches[1]);
        foreach ($matches as $match) {
            $stmt->bindParam(":variableName", $match);
            $stmt->execute();
            $xmlSetting->replaceVariable($match, $stmt->fetch()["value"], "query");
        }
    }

    public function getVariable($searchValue, $id = null)
    {
        if ($id == null) {
            $stmt = $this->dbc->prepare("SELECT * FROM variables WHERE name LIKE :searchValue");
            $searchValue = "%" . $searchValue . "%";
            $stmt->bindParam(":searchValue", $searchValue);
        } else {
            $stmt = $this->dbc->prepare("SELECT * FROM variables WHERE id=:id");
            $stmt->bindParam(":id", $id);
        }
        $stmt->execute();
        $variables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $this->dbc->prepare("SELECT * FROM user_variable WHERE variables_id=:id");
        foreach ($variables as  $key =>$variable) {
            $id = $variable["id"];
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $userValue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($userValue as $value) {
                $variables[$key]["userValues"][$value["user_id"]] = $value["value"];
            }
        }
        return $variables;
    }

    public function createVariable($json){
        $variableName = json_decode($json, true)["name"];
        $stmt = $this->dbc->prepare("INSERT INTO variables(name) VALUES (:name)");
        $stmt->bindParam(":name", $variableName);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }

    public function setVariable($variableID, $userID, $value){
        $stmt = $this->dbc->prepare("INSERT INTO user_variable(user_id, variables_id, value) VALUES (:userID, :variableID, :value) ON DUPLICATE KEY UPDATE value=:value");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":variableID", $variableID);
        $stmt->bindParam(":value", $value);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }
    public function getVariableForUser($userID, $variableID = null){
        if($variableID != null){
            $stmt = $this->dbc->prepare("SELECT * FROM user_variable, variables WHERE variables_id=:variableID AND user_id=:userID AND variables_id = variables.id");
            $stmt->bindParam(":variableID", $variableID);
        }else{
            $stmt = $this->dbc->prepare("SELECT * FROM user_variable, variables WHERE user_id=:userID AND variables_id = variables.id");
        }
        $stmt->bindParam(":userID", $userID);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $returnValue = array();
        foreach ($result as $item) {
            $toPush["id"] = $item["id"];
            $toPush["name"] = $item["name"];
            $toPush["value"] = $item["value"];
            array_push($returnValue, $toPush);
        }
        if ($stmt->errorCode() == "00000") {
            return $returnValue;
        } else {
            return false;
        }

    }
    public function deleteVariable($variableID){
        $stmt = $this->dbc->prepare("DELETE FROM variables WHERE id=:variableID");
        $stmt->bindParam(":variableID", $variableID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }
    public function unsetVariable($variableID, $userID){
        $stmt = $this->dbc->prepare("DELETE FROM user_variable WHERE variables_id=:variableID AND user_id=:userID");
        $stmt->bindParam(":userID", $userID);
        $stmt->bindParam(":variableID", $variableID);
        $stmt->execute();
        if ($stmt->errorCode() == "00000") {
            return true;
        } else {
            return false;
        }
    }
}