<?php

$start_time = microtime(true);

define('IS_DEVELOPMENT', true);
define('JELLY_POP_TOKEN', '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ');

function show_error($response_code, $status_code, $message) {
    http_response_code($response_code);
    include("error.php");
    die;
}

$headers = getallheaders();
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
        include($service.'.php');
        break;
    case 'friend_score' :
//        if ($_SERVER["REQUEST_METHOD"] != 'GET') {
//            show_error(405, "405 Method Not Allowed", "Invalid Method");
//        }
//        include('friend_score.php');
//        break;
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
        include($service.'.php');
        break;
    default :
        show_error(503, "503 Service Unavailable", "Invalid Service");
}


