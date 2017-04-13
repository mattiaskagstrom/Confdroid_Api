<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:45
 */

/**
 * Class ApplicationFunctoins
 * It gets a user for the application
 */
class ApplicationFunctoins
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
        if(isset($stmtAnswer[0]))
        {
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
    public function getAplications(Device $device)
    {
        $stmt = $this->dbc->prepare("SELECT id, apk_name, apk_url, force_install, data_dir, friendly_name FROM application, application_device WHERE application.id = application_device.application_id AND application_device.device_id =:deviceID");
        $deviceId = $device->getId();
        $stmt->bindParam(":deviceID", $deviceId);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($applications as $application) {
            $device->addApplication(new Application($application["id"], $application["data_dir"], $application["apk_name"],$application["apk_url"],$application["friendly_name"],$application["force_install"]));
        }
        return $device  ;
    }

}