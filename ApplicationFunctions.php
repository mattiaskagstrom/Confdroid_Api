<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:45
 */

/**
 * Class ApplicationFunctions
 * It gets a user for the application
 */
class ApplicationFunctions
{
    private $dbc;

    function __construct($dbc)
    {
        $this->dbc = $dbc;
    }

    /**
     * Used when application needs authorization
     * @param $userAuth
     * @return User
     */
    public function authorizeUser($userAuth)
    {
        $stmt = $this->dbc->prepare("SELECT id, name, mail FROM user WHERE auth_token=:authToken");
        $stmt->bindParam(":authToken", $userAuth);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    }

    /**
     * @param $userId
     * @param $imei
     * @return Device
     */
    public function getDevice($userId, $imei)
    {
        $stmt = $this->dbc->prepare("SELECT id, name, imei FROM device, user_device WHERE imei=:imei AND device.id = user_device.device_id AND user_device.user_id=:userId");
        $stmt->bindParam(":imei", $imei);
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (isset($stmtAnswer[0])) {
            $device = new Device($stmtAnswer[0]["id"], $stmtAnswer[0]["name"], $stmtAnswer[0]["imei"]);
            return $device;
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
}