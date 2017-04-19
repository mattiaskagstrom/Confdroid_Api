<?php

/**
 * Created by IntelliJ IDEA.
 * User: Elias
 * Date: 2017-04-12
 * Time: 15:44
 */
class AdminFunctions
{
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
        $userSession["id"] = null;
        $userSession["Token"] = null;

        if (!isset($user[0]["id"]))                 //If does not exist a matching username and password return failed
        {
            $userSession["Token"] = "Failed";
            return $userSession;
        }
        else
        {
            $userSession["id"] = $user[0]["id"];
            $token = bin2hex(openssl_random_pseudo_bytes(16));          //Creates random hex Token
            $insertAuth = $this->dbc->prepare("UPDATE admin SET authToken=:authToken WHERE username=:username AND password=:password ");
            $insertAuth->bindParam(":authToken", $token);
            $insertAuth->bindParam(":username", $username);
            $insertAuth->bindParam(":password", $password);
            $insertAuth->execute();
            $userSession["Token"] = $token;
            $_SESSION["authToken"] = $token;                                    //Starts session variables used in authorzation
            $_SESSION["adminId"] = $user[0]["id"];
            return $userSession;
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
        if(isset($_SESSION["authToken"]) && isset($_SESSION["adminId"]))
        {
            if($_SESSION["authToken"] == $authToken && $_SESSION["adminId"] == $adminId)
                return true;
            else
                return false;
        }
        else
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
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE name LIKE '%$name%' AND mail LIKE '%$mail%'");
        $stmt->execute();
        $querriedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $i = 0;
        foreach ($querriedUsers as $user)
        {
            $users[$i] = new User($user["id"], $user["name"], $user["mail"], $user["auth_token"], $user["date_created"]);
            $users[$i]->addDevices($this->getDevices($user["id"]));
            $users[$i] = $users[$i]->getObject();
            $i++;
        }
        return $users;
    }

    /**
     * Returns array with devices
     * @param $userId
     * @return Devices[]
     */
    private function getDevices($userId)
    {
        $stmt = $this->dbc->prepare("SELECT id, name, imei, date_created FROM device, user_device WHERE device.id = user_device.device_id AND user_device.user_id=:userId");
        $stmt->bindParam(":userId", $userId);
        $stmt->execute();
        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(isset($stmtAnswer[0]))
        {
            for($i = 0; $i < count($stmtAnswer); $i++)
                $devices[$i] = new Device($stmtAnswer[$i]["id"], $stmtAnswer[$i]["name"], $stmtAnswer[$i]["imei"], $stmtAnswer[$i]["date_created"]);
            return $devices;
        }
        return null;
    }
}


