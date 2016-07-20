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

$friends = array();
$filter_friends = array($facebook_id);
$url = "https://graph.facebook.com/v2.7/$facebook_id/friends?access_token={$config['facebook_token']}&limit=50";

get_facebook_friends:
$result_facebook = file_get_contents($url);
$json_facebook = json_decode($result_facebook);

foreach ($json_facebook->data as $v) {
    $friends[$v->id] = $v->name;
    $filter_friends[] = $v->id;
}

if (isset($json_facebook->paging->next)) {
    $url = $json_facebook->paging->next;
    goto get_facebook_friends;
}

$filter = array('score' => array('$gte' => 0), 'facebook_id' => array('$in' => $filter_friends));
$sort = array('score' => -1, 'facebook_id' => -1); // desc(-1), asc(1)
$options = array('sort' => $sort, 'limit' => (int) $limit);

$documents = $db->User->find($filter, $options);

$result['status'] = TRUE;
$result['currentUser'] = bson_document_to_array($document);
$result['topPlayer'] = bson_documents_to_array($documents);
//$result['count_friend'] = count($friends);

$score = isset($document->score) ? $document->score : 0;
$count1 = $db->User->count(array(
                                'facebook_id' => array('$in' => $filter_friends), 
                                'score' => array('$gt' => $score)
                            ));
$count2 = $db->User->count(array(
                                'facebook_id' => array('$in' => $filter_friends), 
                                'score' => array('$eq' => $score), 
                                'facebook_id' => array('$gte' => $facebook_id)
                            ));

$url2 = "https://graph.facebook.com/?ids=" . $facebook_id . "&access_token=" . $config['facebook_token'];
$result_facebook2 = file_get_contents($url2);
$json_facebook2 = json_decode($result_facebook2);

$friends[$facebook_id] = $json_facebook2->$facebook_id->name;

$result['currentUser']['name'] = $json_facebook2->$facebook_id->name;
$result['currentUser']['rank'] = $count1 + $count2;
$result['currentUser']['count1'] = $count1;
$result['currentUser']['count2'] = $count2;

$i = 1;
foreach ($result['topPlayer'] as $k=>$v) {
    $result['topPlayer'][$k]['name'] = $friends[$v['facebook_id']];
    $result['topPlayer'][$k]['rank'] = $i;
    $i++;
}

return $result;