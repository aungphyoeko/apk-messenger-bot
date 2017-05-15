<?php

/* validate verify token needed for setting up web hook */ 
if (isset($_GET['hub_verify_token'])) { 
    if ($_GET['hub_verify_token'] === 'my_secure_verify_token') {
        echo $_GET['hub_challenge'];
        return;
    } else {
        echo 'Invalid Verify Token';
        return;
    }
}

$PAGE_ACCESS_TOKEN = getenv('PAGE_ACCESS_TOKEN');
/* receive and send messages */
$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {

    $sender = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
    $message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent

    $sender_curl = "https://graph.facebook.com/v2.6/1050211921746518?fields=first_name,last_name,profile_pic,locale,timezone,gender&access_token=$PAGE_ACCESS_TOKEN" ;
    $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$PAGE_ACCESS_TOKEN";
    
    /*prepare response*/
    $name = getUserName($sender_curl);

    $jsonData = '{
    "recipient":{
        "id":"' . $sender . '"
        },
        "message":{
            "text":"(Bot): Hello '.$name.$reply.', '.GetResponseMessage($message ). '"
        }
    }';
    $ch = curl_init($url);

    /* curl setting to send a json post data */
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    if (!empty($message)) {
        $result = curl_exec($ch); // user will get the message
    }
    
}
function getUserName($curl){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $curl);
    $result = curl_exec($ch);
    curl_close($ch);

    $obj = json_decode($result);
    return $obj['first_name'];

}

// Processing Messages To Reply
$TEAM_DATA;
function GetResponseMessage($userInput){
    global $TEAM_DATA;
    $TEAM_DATA = json_decode(file_get_contents('teamdata.json'),true);
    return $TEAM_DATA['messages']['greeting'];
}
?>