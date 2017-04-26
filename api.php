<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mattias Kågström
 * Date: 2017-03-31
 * Time: 00:37
 */

header('Access-Control-Allow-Origin: *');
spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

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
$db = new DatabaseConnection();
switch ($method) {
    case 'PUT':
        $output = $db->put($request);
        break;
    case 'POST':
        $output = $db->post($request);

        break;
    case 'GET':
        $output = $db->get($request);

        break;
    case 'HEAD':
        //do_something_with_head($request);
        break;
    case 'DELETE':
        $output = $db->delete($request);
        break;
    case 'OPTIONS':
        //do_something_with_options($request);
        break;
    default:
        //handle_error($request);
        break;

}
if($output != null) {
    switch ($fileType) {
        case 'json':
            echo json_encode($output, JSON_UNESCAPED_UNICODE);
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
}


