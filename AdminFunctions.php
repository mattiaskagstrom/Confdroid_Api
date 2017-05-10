<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:44
 */
class AdminFunctions
{
    /**
     * @var PDO
     */
    private $dbc;

    function __construct($dbc)
    {
        $this->dbc = $dbc;
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
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password", $password);
        $stmt->execute();

        $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $adminSession["id"] = null;
        $adminSession["Token"] = null;

        if (!isset($user[0]["id"]))                 //If does not exist a matching username and password return failed
        {
            $adminSession["Token"] = "Failed";
            return $adminSession;
        } else {
            $adminSession["id"] = $user[0]["id"];
            $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
            $insertAuth = $this->dbc->prepare("UPDATE admin SET authToken=:authToken, logintime=NOW(), ipaddr=:ipaddr WHERE username=:username AND password=:password ");
            $insertAuth->bindParam(":authToken", $token);
            $insertAuth->bindParam(":username", $username);
            $insertAuth->bindParam(":password", $password);
            $insertAuth->bindParam(":ipaddr", $this->getRealIpAddr());
            $insertAuth->execute();
            $adminSession["Token"] = $token;
            return $adminSession;
        }
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
     * @param string $mail
     * @return Mixed
     */
    public function searchUsers($name = "", $mail = "")
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE name LIKE :name"); //OR mail LIKE :mail
        $name = "%" . $name . "%";
        //$mail = "%" . $mail . "%";
        $stmt->bindParam(":name", $name);
        //$stmt->bindParam(":mail", $mail); //removed due to search should only be on name
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;

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
        $users[0] = "Failed";
        return $users;
    }

    public function getUser($authToken, $imei = null)
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE auth_token=:authToken");
        $stmt->bindParam(":authToken", $authToken);
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        if (isset($queriedUsers[0])) {
            $user = new User($queriedUsers[0]["id"], $queriedUsers[0]["name"], $queriedUsers[0]["mail"], $queriedUsers[0]["auth_token"], $queriedUsers[0]["date_created"]);
            if ($imei == null) {
                $user->addDevices($this->getDevices($user->getId()));
            } else {
                $device = $this->getDevice($imei, $user);
                if($device)$user->addDevice($device);else http_response_code(400);

            }
            $user->addGroups($this->getGroupsByUserId($user->getId()));
            $user = $this->getApplications(null, $user);
            return $user;
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

    public function getDevice($imei, User $user = null)
    {
        if($user == null){
            $stmt = $this->dbc->prepare("SELECT device.* FROM device WHERE device.imei=:imei");
            $stmt->bindParam(":imei", $imei);
        }else{
            $stmt = $this->dbc->prepare("SELECT device.* FROM device, user_device WHERE device.imei=:imei AND user_device.user_id=:userID");
            $stmt->bindParam(":imei", $imei);
            $stmt->bindParam(":userID", $user->getID());
        }

        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $device = new Device($stmtAnswer[0]["id"], $stmtAnswer[0]["name"], $stmtAnswer[0]["imei"], $stmtAnswer[0]["date_created"]);
            $device = $this->getApplications($device, $user);
            return $device;
        }
        return null;
    }

    public function addDeviceToUser($userToken, $imei)
    {
        $stmt = $this->dbc->prepare("INSERT INTO user_device(user_id, device_id) VALUES((SELECT id FROM user WHERE auth_token=:authToken), (SELECT id FROM device WHERE imei=:imei))");
        $stmt->bindParam(":authToken", $userToken);
        $stmt->bindParam(":imei", $imei);
        $stmt->execute();
        if($stmt->errorCode()=="00000"){
            return true;
        }else{
            return false;
        }
    }

    public function removeDeviceFromUser($userToken, $imei)
    {
        $stmt = $this->dbc->prepare("DELETE FROM user_device WHERE device_id=(SELECT id FROM device WHERE imei=:imei) AND user_id=(SELECT id FROM user WHERE auth_token=:authToken)");
        $stmt->bindParam(":authToken", $userToken);
        $stmt->bindParam(":imei", $imei);
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
            $devices = null;
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $devices[$i] = new Device($stmtAnswer[$i]["id"], $stmtAnswer[$i]["name"], $stmtAnswer[$i]["imei"], $stmtAnswer[$i]["date_created"]);
                $devices[$i] = $this->getApplications($devices[$i]);
                $devices[$i] = $devices[$i]->getObject();
            }
            return $devices;
        }
        return null;
    }

    public function addApplicationToUser($userToken, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_user(user_id, application_id) VALUES((SELECT id FROM user WHERE auth_token=:authToken), :applicationID)");
        $stmt->bindParam(":authToken", $userToken);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if($stmt->errorCode()=="00000"){
            return true;
        }else{
            return false;
        }
    }

    public function addApplicationToGroup($groupID, $applicationID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO application_group(group_id, application_id) VALUES(:groupID, :applicationID)");
        $stmt->bindParam(":groupID", $groupID);
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        if($stmt->errorCode()=="00000"){
            return true;
        }else{
            return false;
        }
    }

    public function removeApplicationFromUser($userToken, $applicationID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM application_user WHERE application_id=:applicationID AND user_id=(SELECT id FROM user WHERE auth_token=:authToken)");
        $stmt->bindParam(":authToken", $userToken);
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
        if($stmt->errorCode()=="00000"){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Returns all the applications on a device.
     * @param $device
     * @return mixed
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
            $stmt->bindParam(":userID", $user->getId());
            $deviceId = $device->getId();
            $stmt->bindParam(":deviceID", $deviceId);
            $addTo = $device;
        } else if ($device == null && $user != null) {
            $stmt = $this->dbc->prepare("SELECT application.* FROM application, application_user WHERE application.id = application_user.application_id AND application_user.user_id =:userID");
            $stmt->bindParam(":userID", $user->getId());
            $addTo = $user;
        } else {
            return "error!";
        }

        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sqlSettingStmt = $this->dbc->prepare("SELECT sql_setting.sql_setting, sql_setting.sql_location FROM application, sql_setting, application_sql_setting WHERE application.id = application_sql_setting.application_id AND sql_setting.id = application_sql_setting.sql_setting_id AND application.id=:appID");
        $xmlSettingStmt = $this->dbc->prepare("SELECT xml_setting.file_location, xml_setting.regularexp, xml_setting.replacewith FROM application, xml_setting, application_xml_setting WHERE application.id = application_xml_setting.application_id AND xml_setting.id = application_xml_setting.xml_setting_id AND application.id=:appID");

        foreach ($applications as $application) {

            $sqlSettingStmt->bindParam(":appID", $application["id"]);
            $sqlSettingStmt->execute();
            $sqlSettings = $sqlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $xmlSettingStmt->bindParam(":appID", $application["id"]);
            $xmlSettingStmt->execute();
            $xmlSettings = $xmlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
            $app = new Application($application["id"], $application["data_dir"], $application["apk_name"], $application["apk_url"], $application["friendly_name"], $application["force_install"], $application["package_name"]);
            foreach ($sqlSettings as $sqlSetting) {
                $app->addSQL_setting(new SqlSetting($sqlSetting["sql_location"], $sqlSetting["sql_setting"]));
            }
            foreach ($xmlSettings as $xmlSetting) {
                $app->addXML_setting(new XmlSetting($xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"]));
            }

            $addTo->addApplication($app);
        }
        return $addTo;
    }

    public function searchApplications($searchValue = null, $appID = null){
        if(!$appID){
            $stmt = $this->dbc->prepare("SELECT * FROM application WHERE friendly_name LIKE :searchValue OR package_name LIKE :searchValue OR apk_name LIKE :searchValue");
            $searchValue = "%".$searchValue."%";
            $stmt->bindParam(":searchValue", $searchValue);
        }else{
            $stmt = $this->dbc->prepare("SELECT * FROM application WHERE id=:appID");
            $stmt->bindParam(":appID", $appID);
        }
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sqlSettingStmt = $this->dbc->prepare("SELECT sql_setting.sql_setting, sql_setting.sql_location FROM application, sql_setting, application_sql_setting WHERE application.id = application_sql_setting.application_id AND sql_setting.id = application_sql_setting.sql_setting_id AND application.id=:appID");
        $xmlSettingStmt = $this->dbc->prepare("SELECT xml_setting.file_location, xml_setting.regularexp, xml_setting.replacewith FROM application, xml_setting, application_xml_setting WHERE application.id = application_xml_setting.application_id AND xml_setting.id = application_xml_setting.xml_setting_id AND application.id=:appID");
        $usersStmt = $this->dbc->prepare("SELECT user.* FROM user, application_user WHERE application_user.user_id=user.id AND application_user.application_id=:appID");
        $groupsStmt=$this->dbc->prepare("SELECT group.* FROM group, application_group WHERE application_group.group_id=group.id AND application_group.application_id=:appID");
        $devicesStmt=$this->dbc->prepare("SELECT device.* FROM device, application_device WHERE application_device.device_id=device.id AND application_device.application_id=:appID");
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
                $app->addSQL_setting(new SqlSetting($sqlSetting["sql_location"], $sqlSetting["sql_setting"]));
            }
            foreach ($xmlSettings as $xmlSetting) {
                $app->addXML_setting(new XmlSetting($xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"]));
            }
            $app->setUsers($usersStmt->fetchAll(PDO::FETCH_ASSOC));
            $app->setGroups($groupsStmt->fetchAll(PDO::FETCH_ASSOC));
            $app->setDevices($devicesStmt->fetchAll(PDO::FETCH_ASSOC));
            array_push($appArray, $app->getObject());
        }

        return $appArray;
    }

    /*public function getApplication($applicationID)
    {
        $stmt = $this->dbc->prepare("SELECT id, apk_name, apk_url, force_install, data_dir, friendly_name, package_name FROM application WHERE application.id=:applicationID");
        $stmt->bindParam(":applicationID", $applicationID);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sqlSettingStmt = $this->dbc->prepare("SELECT sql_setting.sql_setting, sql_setting.sql_location FROM application, sql_setting, application_sql_setting WHERE application.id = application_sql_setting.application_id AND sql_setting.id = application_sql_setting.sql_setting_id AND application.id=:appID");
        $xmlSettingStmt = $this->dbc->prepare("SELECT xml_setting.file_location, xml_setting.regularexp, xml_setting.replacewith FROM application, xml_setting, application_xml_setting WHERE application.id = application_xml_setting.application_id AND xml_setting.id = application_xml_setting.xml_setting_id AND application.id=:appID");
        $sqlSettingStmt->bindParam(":appID", $applications[0]["id"]);
        $sqlSettingStmt->execute();
        $sqlSettings = $sqlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
        $xmlSettingStmt->bindParam(":appID", $applications[0]["id"]);
        $xmlSettingStmt->execute();
        $xmlSettings = $xmlSettingStmt->fetchAll(PDO::FETCH_ASSOC);
        $app = new Application($applications[0]["id"], $applications[0]["data_dir"], $applications[0]["apk_name"], $applications[0]["apk_url"], $applications[0]["friendly_name"], $applications[0]["force_install"], $applications[0]["package_name"]);
        foreach ($sqlSettings as $sqlSetting) {
            $app->addSQL_setting(new SqlSetting($sqlSetting["sql_location"], $sqlSetting["sql_setting"]));
        }
        foreach ($xmlSettings as $xmlSetting) {
            $app->addXML_setting(new XmlSetting($xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"]));
        }
        return $app->getObject();
    }*/

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
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $groups[$i] = new Group($stmtAnswer[$i]["id"], $stmtAnswer[$i]["prio"], $stmtAnswer[$i]["name"]);
            }
            return $groups;
        }
        return null;
    }

    public function removeGroupFromUser($userToken, $groupID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM user_group WHERE group_id=:groupID AND user_id=(SELECT id FROM user WHERE auth_token=:authToken)");
        $stmt->bindParam(":authToken", $userToken);
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        return true;
    }

    public function addGroupToUser($userToken, $groupID)
    {
        $stmt = $this->dbc->prepare("INSERT INTO user_group(user_id, group_id) VALUES((SELECT id FROM user WHERE auth_token=:authToken), :groupID)");
        $stmt->bindParam(":authToken", $userToken);
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        if($stmt->errorCode()=="00000"){
            return true;
        }else{
            return false;
        }
    }

    public function addUser($name, $email)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
        $stmt = $this->dbc->prepare("INSERT INTO user(name, mail, auth_token) VALUES(:name, :mail, :authToken)");
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":mail", $email);
        $stmt->bindParam(":authToken", $token);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }

    public function removeUser($id = null, $token = null)
    {
        if (isset($id) && isset($token)) {
            $stmt = $this->dbc->prepare("DELETE FROM user WHERE id=:id AND auth_token=:token;");
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":token", $token);
        } else if (isset($id)) {
            $stmt = $this->dbc->prepare("DELETE FROM user WHERE id=:id;");
            $stmt->bindParam(":id", $id);

        } else if (isset($token)) {
            $stmt = $this->dbc->prepare("DELETE FROM user WHERE auth_token=:token;");
            $stmt->bindParam(":token", $token);

        } else {
            return false;
        }
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
    }


    public function updateUser($userToken, $name = null, $mail = null)
    {
        $sqlstatement = "UPDATE user SET";
        if ($name != null) $sqlstatement .= " name=:name";
        if ($name != null && $mail != null) $sqlstatement .= ", ";
        if ($mail != null) $sqlstatement .= " mail=:mail ";
        $sqlstatement .= "WHERE auth_token:token";
        var_dump($sqlstatement);
    }

    public function searchGroups($groupName)
    {
        $stmt = $this->dbc->prepare("SELECT id, prio, name FROM `group` WHERE name LIKE :groupName");
        $groupName = "%" . $groupName . "%";
        $stmt->bindParam(":groupName", $groupName);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $groups = null;
            for ($i = 0; $i < count($stmtAnswer); $i++) {
                $groups[$i] = new Group($stmtAnswer[$i]["id"], $stmtAnswer[$i]["prio"], $stmtAnswer[$i]["name"]);
                $groups[$i] = $groups[$i]->getObject();
            }
            return $groups;
        }
        $users[0] = "Failed";
        return $users;
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

    public function deleteGroup($groupID)
    {
        $stmt = $this->dbc->prepare("DELETE FROM `group` WHERE id=:groupID");
        $stmt->bindParam(":groupID", $groupID);
        $stmt->execute();
        if ($stmt->errorCode() == 00000) return true;
        return false;
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

}