<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$json = json_decode($input);

$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['score'] = (isset($json->score) && $json->score >= 0) ? $json->score : 0;

if (trim($data['facebook_id']) == "") {
    return array(
        "status" => FALSE,
        "affected_row" => 0,
        "message" => "Error: facebook_id is empty"
    );
}

$db = get_mongodb(IS_DEVELOPMENT);

$document = $db->User->findOne([ 'facebook_id' => $data['facebook_id']]);

$affected_row = 0;
if (is_object($document)) {
    if ($data['score'] > $document->score || (isset($json->overwrite) && $json->overwrite == true)) {
        $data['updated_date'] = date('Y-m-d H:i:s');
        $db->User->updateOne(['_id' => bson_oid((string) $document->_id)], ['$set' => $data]);
        $affected_row = 1;
    }
    return array("status" => TRUE, "affected_row" => $affected_row);
} else {
   return array("status" => FALSE, "affected_row" => $affected_row, "message" => "User not found"); 
}

//echo json_encode(array("status" => TRUE));

