<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$db = get_mongodb(IS_DEVELOPMENT);

$json = json_decode($input);

$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['score'] = isset($json->score) ? $json->score : 0;

$document = $db->User->findOne([ 'facebook_id' => $data['facebook_id'] ]);

if (is_object($document)) {
    $data['updated_date'] = date('Y-m-d H:i:s');
    $db->User->updateOne(['_id' => bson_oid((string) $document->_id)], ['$set' => $data]);
} else {
    $data['created_date'] = date('Y-m-d H:i:s');
    $db->User->insertOne($data);
}

//echo json_encode(array("status" => TRUE));

return array("status" => TRUE);