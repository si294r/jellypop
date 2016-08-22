<?php

defined('IS_DEVELOPMENT') OR exit('No direct script access allowed');

require 'mongodb_helper.php';

$facebook_id = isset($params[1]) ? $params[1] : "";
$limit = isset($params[2]) ? $params[2] : 50;

if (trim($facebook_id) == "") {
    return array(
        "status" => FALSE,
        "message" => "Error: facebook_id is empty"
    );
}

$db = get_mongodb(IS_DEVELOPMENT);

$document = $db->User->findOne([ 'facebook_id' => $facebook_id]);

if (!is_object($document)) {
    return array("status" => FALSE, "message" => "User not found");
}

function display_name_exists($value) {
    return isset($value['display_name']) && trim($value['display_name']) != "";
}

function display_name_expired($value) {
    if (!isset($value['display_name'])) {
        return true;
    }
    if (trim($value['display_name']) == "") {
        return true;
    }
    $tgl = new DateTime($value['display_name_updated_date'], new DateTimeZone("GMT"));
    $expired_date =  gmdate("Y-m-d H:i:s", $tgl->modify("+1 day")->getTimestamp());
    if (gmdate("Y-m-d H:i:s") > $expired_date) {
        return true;
    }
    return false;
}

function get_global_score(&$result) {

    global $config, $document, $db, $facebook_id, $limit;

    $filter = array('score' => array('$gte' => 0));
    $sort = array('score' => -1, 'facebook_id' => -1); // desc(-1), asc(1)
    $options = array('sort' => $sort, 'limit' => (int) $limit);

    $documents = $db->User->find($filter, $options);

//    $result['status'] = TRUE;
    $result['currentUser'] = bson_document_to_array($document);
    $result['topPlayer'] = bson_documents_to_array($documents);

    $score = isset($document->score) ? $document->score : 0;
    $count1 = $db->User->count(array('score' => array('$gt' => $score)));
    $count2 = $db->User->count(array('score' => array('$eq' => $score), 'facebook_id' => array('$gte' => $facebook_id)));

    $i = 1;
    $facebook_ids = [];
    if (display_name_expired($result['currentUser'])) {
        $facebook_ids[] = $facebook_id;
    }
    foreach ($result['topPlayer'] as $k => $v) {
//    $result['topPlayer'][$k]['name'] = 'Player '.$i;
        if (trim($v['facebook_id']) != "") {
            if (display_name_expired($v)) {
                $facebook_ids[] = $v['facebook_id'];
            }
        }
        $result['topPlayer'][$k]['rank'] = $i;
        $i++;
    }

    if (count($facebook_ids) > 0) {
        $url = "https://graph.facebook.com/?ids=" . implode(",", $facebook_ids) . "&access_token=" . $config['facebook_token'];
        $result_facebook = file_get_contents($url);
        $json_facebook = json_decode($result_facebook);
    }

    if (isset($json_facebook->$facebook_id->name)) {
        $result['currentUser']['name'] = $json_facebook->$facebook_id->name;
        // update display_name
        $data['display_name'] = $json_facebook->$facebook_id->name;
        $data['display_name_updated_date'] = gmdate('Y-m-d H:i:s');
        $db->User->updateOne(['_id' => bson_oid((string) $result['currentUser']['_id'])], ['$set' => $data]);
    } elseif (display_name_exists($result['currentUser'])) {
        $result['currentUser']['name'] = $result['currentUser']['display_name'];
    } else {
        $result['currentUser']['name'] = "N/A";
    }
    $result['currentUser']['rank'] = $count1 + $count2;

    foreach ($result['topPlayer'] as $k => $v) {
        if (trim($v['facebook_id']) != "" && isset($json_facebook->$v['facebook_id']->name)) {
            $result['topPlayer'][$k]['name'] = $json_facebook->$v['facebook_id']->name;
            // update display_name
            $data['display_name'] = $json_facebook->$v['facebook_id']->name;
            $data['display_name_updated_date'] = gmdate('Y-m-d H:i:s');
            $db->User->updateOne(['_id' => bson_oid((string) $v['_id'])], ['$set' => $data]);
        } elseif (display_name_exists($v)) {
            $result['topPlayer'][$k]['name'] = $v['display_name'];
        } else {
            $result['topPlayer'][$k]['name'] = "N/A";
        }
    }
}

function get_friend_score(&$result) {

    global $config, $document, $db, $facebook_id, $limit;

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

//    $result['status'] = TRUE;
    $result['currentUser'] = bson_document_to_array($document);
    $result['topPlayer'] = bson_documents_to_array($documents);
//$result['count_friend'] = count($friends);

    $score = isset($document->score) ? $document->score : 0;
    $count1 = $db->User->count(array(
        'facebook_id' => array('$in' => $filter_friends),
        'score' => array('$gt' => $score)
    ));
    $count2 = $db->User->count(array('$and' =>
        array(
            array('facebook_id' => array('$in' => $filter_friends)),
            array('score' => array('$eq' => $score)),
            array('facebook_id' => array('$gte' => $facebook_id))
        )
    ));

    if (display_name_expired($result['currentUser'])) {
        $url2 = "https://graph.facebook.com/?ids=" . $facebook_id . "&access_token=" . $config['facebook_token'];
        $result_facebook2 = file_get_contents($url2);
        $json_facebook2 = json_decode($result_facebook2);
    }

    if (isset($json_facebook2->$facebook_id->name)) {
        $friends[$facebook_id] = $json_facebook2->$facebook_id->name;
        $result['currentUser']['name'] = $json_facebook2->$facebook_id->name;
        // update display_name
        $data['display_name'] = $json_facebook2->$facebook_id->name;
        $data['display_name_updated_date'] = gmdate('Y-m-d H:i:s');
        $db->User->updateOne(['_id' => bson_oid((string) $result['currentUser']['_id'])], ['$set' => $data]);        
    } elseif (display_name_exists($result['currentUser'])) {
        $friends[$facebook_id] = $result['currentUser']['display_name'];
        $result['currentUser']['name'] = $result['currentUser']['display_name'];
    } else {
        $friends[$facebook_id] = "N/A";
        $result['currentUser']['name'] = "N/A";
    }
    $result['currentUser']['rank'] = $count1 + $count2;

    $i = 1;
    foreach ($result['topPlayer'] as $k => $v) {
        $result['topPlayer'][$k]['name'] = $friends[$v['facebook_id']];
        $result['topPlayer'][$k]['rank'] = $i;
        $i++;
    }
}

$result = array('status' => true, 'global' => array(), 'friend' => array());

get_global_score($result['global']);
get_friend_score($result['friend']);

return $result;
