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

$fbMessenger = Messenger();

function get_name($surl){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $surl);
    $result = curl_exec($ch);
    curl_close($ch);
    $obj = json_decode($result,true);
    curl_close($ch);
    return $obj["first_name"];
}


// Processing Messages To Reply
$TEAM_DATA;
function GetResponseMessage($userInput){
    global $TEAM_DATA;
    $TEAM_DATA = json_decode(file_get_contents('teamdata.json'),true);
    return $TEAM_DATA['messages']['greeting'];
}
?>
<?php
class Messenger {
    protected $PAGE_ACCESS_TOKEN;
    protected $input;
    protected $sender_id;
    protected $url;
    public function __construct(){
        $this->PAGE_ACCESS_TOKEN  = getenv('PAGE_ACCESS_TOKEN');
        $this->input = json_decode(file_get_contents('php://input'), true);
        /* receive and send messages */
            if (isset($this->input['entry'][0]['messaging'][0]['sender']['id'])) {

                $this->sender_id = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
                $message = $this->input['entry'][0]['messaging'][0]['message']['text']; //text that user sent

                $this->url = "https://graph.facebook.com/v2.6/me/messages?access_token=$PAGE_ACCESS_TOKEN";
                //$surl = "https://graph.facebook.com/v2.6/$sender?fields=first_name,last_name&access_token=$PAGE_ACCESS_TOKEN";
                $this->send_message($message);
            }
    }
    public function send_message($message = ''){
 /*initialize curl*/
    $ch = curl_init($this->url);
    /*prepare response*/

    $jsonData = '{
    "recipient":{
        "id":"' . $this->sender_id . '"
        },
        "message":{
            "text":"(Bot): Hello,'.$message. '"
        }
    }';
    /* curl setting to send a json post data */
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    if (!empty($message)) {
        $result = curl_exec($ch); // user will get the message
    }
    curl_close($ch);
}
   
}

?>