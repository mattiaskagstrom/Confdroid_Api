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
            $insertAuth = $this->dbc->prepare("UPDATE admin SET authToken=:authToken WHERE username=:username AND password=:password ");
            $insertAuth->bindParam(":authToken", $token);
            $insertAuth->bindParam(":username", $username);
            $insertAuth->bindParam(":password", $password);
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
        $stmt = $this->dbc->prepare("SELECT * FROM admin WHERE id=:adminID AND authToken=:authToken");
        $stmt->bindParam(":adminID", $adminId);
        $stmt->bindParam(":authToken", $authToken);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (sizeof($result) == 1) return true;
        return false;
    }

    /**
     * Search on user
     * @param string $name
     * @param string $mail
     * @return Mixed
     */
    public function searchUsers($name = "", $mail = "")
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE name LIKE '%$name%' OR mail LIKE '%$mail%'");
        $stmt->execute();
        $queriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        if (isset($queriedUsers[0])) {
            $users = $this->createUsersFromSqlAnswer($queriedUsers);
            return $users;
        }
        $users[0] = "Failed";
        return $users;
    }

    private function createUsersFromSqlAnswer($stmtAnswer)
    {
        $i = 0;
        $users = array(User::class);
        foreach ($stmtAnswer as $user) {
            $users[$i] = new User($user["id"], $user["name"], $user["mail"], $user["auth_token"], $user["date_created"]);
            $users[$i]->addDevices($this->getDevices($user["id"]));
            $users[$i]->addGroups($this->getGroupsByUserId($user["id"]));
            $users[$i] = $users[$i]->getObject();
            $i++;
        }
        return $users;
    }

    /**
     * Returns array with devices
     * @param $userId
     * @return Device
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
     * @return Device
     */
    private function getApplications(Device $device)
    {
        $stmt = $this->dbc->prepare("SELECT id, apk_name, apk_url, force_install, data_dir, friendly_name FROM application, application_device WHERE application.id = application_device.application_id AND application_device.device_id =:deviceID");
        $deviceId = $device->getId();
        $stmt->bindParam(":deviceID", $deviceId);
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
            $app = new Application($application["id"], $application["data_dir"], $application["apk_name"], $application["apk_url"], $application["friendly_name"], $application["force_install"]);
            foreach ($sqlSettings as $sqlSetting) {
                $app->addSQL_setting(new SqlSetting($sqlSetting["sql_location"], $sqlSetting["sql_setting"]));
            }
            foreach ($xmlSettings as $xmlSetting) {
                $app->addXML_setting(new XmlSetting($xmlSetting["file_location"], $xmlSetting["regularexp"], $xmlSetting["replacewith"]));
            }

            $device->addApplication($app);
        }
        return $device;
    }

    /**
     * @param $userId
     * @return Groups[]
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

    public function addUser($name, $email)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
        $stmt = $this->dbc->prepare("INSERT INTO user(name, mail, auth_token) VALUES(:name, :mail, :authToken)");
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":mail", $email);
        $stmt->bindParam(":authToken", $token);
        $stmt->execute();
        return true;
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
            $stmt->bindParam(":id", $id);

        } else {
            return false;
        }
        $stmt->execute();
    }

    public function searchGroups($groupName)
    {
        $stmt = $this->dbc->prepare("SELECT id, prio, name FROM `group` WHERE name LIKE '%$groupName%'");
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
}