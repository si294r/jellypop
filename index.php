<?php

$start_time = microtime(true);

define('IS_DEVELOPMENT', FALSE);
require '/var/www/token.php';

function show_error($response_code, $status_code, $message) {
    http_response_code($response_code);
    header('Content-Type: application/json');
    echo json_encode(array('status_code' => $status_code, 'message' => $message));
//    include("error.php");
    die;
}

if (function_exists("getallheaders")) {
    $headers = getallheaders();
} else {
    $headers['Jelly-Pop-Token'] = isset($_SERVER["HTTP_JELLY_POP_TOKEN"]) ? $_SERVER["HTTP_JELLY_POP_TOKEN"] : "";
    $headers['Content-Type'] = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : "";
}
if (!isset($headers['Jelly-Pop-Token']) || $headers['Jelly-Pop-Token'] != JELLY_POP_TOKEN) {
    show_error(401, "401 Unauthorized", "Invalid Token");
}

//print_r($headers);
//echo "_SERVER[\"REQUEST_METHOD\"]" . $_SERVER["REQUEST_METHOD"] . "\r\n";
//echo "_SERVER[\"QUERY_STRING\"]" . $_SERVER["QUERY_STRING"] . "\r\n";

$query_string = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
$params = explode("/", $query_string);

$service = isset($params[0]) ? $params[0] : "";

switch ($service) {
    case 'connect' :
//        if ($_SERVER["REQUEST_METHOD"] != 'POST') {
//            show_error(405, "405 Method Not Allowed", "Invalid Method");
//        }
//        if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'application/json') {
//            show_error(400, "400 Bad Request", "Invalid Content Type");
//        }
//        $input = file_get_contents("php://input");
//        include('connect.php');
//        break;
    case 'score' :
        if ($_SERVER["REQUEST_METHOD"] != 'POST') {
            show_error(405, "405 Method Not Allowed", "Invalid Method");
        }
        if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'application/json') {
            show_error(400, "400 Bad Request", "Invalid Content Type");
        }
        $input = file_get_contents("php://input");
//        include('score.php');
        try {
            $service_result = include($service.'.php');
        } catch (Exception $ex) {
            show_error(500, "500 Internal Server Error", $ex->getMessage());
        }
        break;
    case 'friend_score' :
//        if ($_SERVER["REQUEST_METHOD"] != 'GET') {
//            show_error(405, "405 Method Not Allowed", "Invalid Method");
//        }
//        include('friend_score.php');
//        break;
    case 'leaderboard' :
    case 'global_score' :
//        if ($_SERVER["REQUEST_METHOD"] != 'GET') {
//            show_error(405, "405 Method Not Allowed", "Invalid Method");
//        }
//        include('global_score.php');
//        break;
    case 'country_score' :
        if ($_SERVER["REQUEST_METHOD"] != 'GET') {
            show_error(405, "405 Method Not Allowed", "Invalid Method");
        }
//        include('country_score.php');
        try {
            $service_result = include($service.'.php');
        } catch (Exception $ex) {
            show_error(500, "500 Internal Server Error", $ex->getMessage());
        }
        break;
    default :
        show_error(503, "503 Service Unavailable", "Invalid Service");
}

$end_time = microtime(true);

$service_result['execution_time'] = number_format($end_time - $start_time, 5);
$service_result['memory_usage'] = memory_get_usage(true);

header('Content-Type: application/json');

echo json_encode($service_result);
