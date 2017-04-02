<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-03-31
 * Time: 00:37
 */
$method = $_SERVER['REQUEST_METHOD'];
//remove the filetype from the string, so we can use it later
$request = explode(".", $_GET["url"]);
if (sizeof($request) <= 1) {
    die("<br>Please specify a unit to return<br>");
}
$fileType = $request[1];
$request = explode("/", $request[0]);
unset($request[0]);
$output = null;
switch ($method) {
    case 'PUT':
        //do_something_with_put($request);
        break;
    case 'POST':
        //do_something_with_post($request);
        break;
    case 'GET':
        if($request[1] == "user"){
            if(isset($_GET["imei"]) && isset($_GET["userAuth"])){
                $Device["imei"] = $_GET["imei"];
                $Device["Applications"]["Snapchat"]["apkName"] = "Snapchat-2.8.9.apk";
                $User["Name"] = "Mattias";
                $User["Email"] = "matkag@kth.se";
                $User["Devices"][$_GET["imei"]] = $Device;
                $User["Groups"] = array("Tidaa", "Tutus");

                $output = $User;
            }else{
                die("Missing variables");
            }
        }

        break;
    case 'HEAD':
        //do_something_with_head($request);
        break;
    case 'DELETE':
        //do_something_with_delete($request);
        break;
    case 'OPTIONS':
        //do_something_with_options($request);
        break;
    default:
        //handle_error($request);
        break;

}
switch ($fileType) {
    case 'json':
        echo json_encode($output);
        break;
    case 'xml':
        echo "Not supported";
        break;
    case 'html':
        echo "<pre>";
        print_r($output);
        echo "</pre>";
        break;
    default:
        echo "Please specify a file type, ex .json";
}