<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$db = get_mongodb(IS_DEVELOPMENT);

$json = json_decode($input);

$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['token'] = isset($json->token) ? $json->token : "";
$data['country'] = isset($json->country) ? $json->country : "";

if ($data['facebook_id'] == "") {
    
    return array(
        "status" => FALSE,
        "affected_row" => 0,
        "message" => "Error: facebook_id is empty"
    );
    
} else {

    $document = $db->User->findOne([ 'facebook_id' => $data['facebook_id']]);

    if (is_object($document)) {
        $data['updated_date'] = date('Y-m-d H:i:s');
//    $data['facebook_id'] = '115346812166325';
        $db->User->updateOne(['_id' => bson_oid((string) $document->_id)], ['$set' => $data]);
    } else {
        $data['created_date'] = date('Y-m-d H:i:s');
        if (!isset($data['score'])) {
            $data['score'] = 0;
        }
        $db->User->insertOne($data);
    }

    $new_document = $db->User->findOne([ 'facebook_id' => $data['facebook_id']]);
    $currentUser = bson_document_to_array($new_document);
    if (!isset($currentUser['score']))
        $currentUser['score'] = 0;

//echo json_encode(array("status" => TRUE));

    return array(
        "status" => TRUE,
        "affected_row" => 1,
        "currentUser" => $currentUser
    );
    
}