<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$db = get_mongodb(IS_DEVELOPMENT);

$json = json_decode($input);

$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['score'] = isset($json->score) ? $json->score : 0;

$document = $db->User->findOne([ 'facebook_id' => $data['facebook_id'] ]);

$affected_row = 0;
if (is_object($document)) {
    if ($data['score'] > $document->score || (isset($json->overwrite) && $json->overwrite == true)) {
        $data['updated_date'] = date('Y-m-d H:i:s');
        $db->User->updateOne(['_id' => bson_oid((string) $document->_id)], ['$set' => $data]);
        $affected_row = 1;
    }
} else {
    $data['created_date'] = date('Y-m-d H:i:s');
    $db->User->insertOne($data);
    $affected_row = 1;
}

//echo json_encode(array("status" => TRUE));

return array("status" => TRUE, "affected_row" => $affected_row);