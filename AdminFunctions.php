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
//        $stmt = $this->dbc->prepare("SELECT authToken FROM admin WHERE id=:id AND authToken=:authToken");
//        $stmt->bindParam(":id", $adminId);
//        $stmt->bindParam(":authToken", $authToken);
//        $stmt->execute();
//
//        $stmtAnswer = $stmt->fetchAll(PDO::FETCH_ASSOC);
//        $retValue["auth"] = null;
//        if($stmtAnswer[0]["authToken"] == null)
//        {
//            $retValue["auth"] = false;
//            return $retValue;
//        }
//        $retValue["auth"] = true;
//
//        $_SESSION[""];
//        return $retValue;
    }

    /**
     * @param string $name
     * @param string $mail
     * @return User
     */
    private function getUser($name = "", $mail = "")
    {
        $stmt = $this->dbc->prepare("SELECT * FROM user WHERE name LIKE :name AND mail LIKE :mail");
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":mail", $mail);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    }
}


