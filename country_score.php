<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$db = get_mongodb(IS_DEVELOPMENT);

$facebook_id = isset($params[1]) ? $params[1] : "";
$limit = isset($params[2]) ? $params[2] : 50;
        
$document = $db->User->findOne([ 'facebook_id' => $facebook_id ]);

if (!is_object($document)) {
   echo json_encode(array("status" => FALSE, "message" => "User not found")); 
   die;
}

$filter = array('score' => array('$gt' => 0), 'country' => $document->country);
$sort = array('score' => -1); // desc(-1), asc(1)
$options = array('sort' => $sort, 'limit' => (int) $limit);

$documents = $db->User->find($filter, $options);

$result['status'] = TRUE;
$result['currentUser'] = bson_document_to_array($document);
$result['topPlayer'] = bson_documents_to_array($documents);

$result['currentUser']['name'] = 'YOU';
$result['currentUser']['rank'] = 0;

$i = 1;
$facebook_ids = array();
foreach ($result['topPlayer'] as $k=>$v) {
//    $result['topPlayer'][$k]['name'] = 'Player '.$i;
    $facebook_ids[] = $v['facebook_id'];
    $result['topPlayer'][$k]['rank'] = $i;
    $i++;
}

$url = "https://graph.facebook.com/?ids=" . implode(",", $facebook_ids) . "&access_token=371475076265908%7C16006ef2fbbc5d9466cf2dad4f688642";
$result_facebook = file_get_contents($url);
$json_facebook = json_decode($result_facebook);

foreach ($result['topPlayer'] as $k=>$v) {
    $result['topPlayer'][$k]['name'] = $json_facebook->$v['facebook_id']->name;
}

echo json_encode($result);
