<?php
$messenger = new Messenger();
$messenger->verify_webhook();
$messenger->listen_message();
class Messenger{
    protected $PAGE_ACCESS_TOKEN;
    protected $input;
    protected $message;
    public function __construct(){
        $this->PAGE_ACCESS_TOKEN =getenv('PAGE_ACCESS_TOKEN');
    }
    public function listen_message(){
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {

            $sender = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent

            $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$PAGE_ACCESS_TOKEN";
            $surl = "https://graph.facebook.com/v2.6/$sender?fields=first_name,last_name&access_token=$PAGE_ACCESS_TOKEN";
            $name = $this->get_name($surl);
            $this->send_message($sender,$url,$message,$name);    
        }
    }
    public function verify_webhook(){
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
    }
    /* receive and send messages */

public function get_name($surl){
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
public function send_message($sender,$url,$message = '',$name){
 /*initialize curl*/
    $ch = curl_init($url);
    /*prepare response*/

    $jsonData = '{
    "recipient":{
        "id":"' . $sender . '"
        },
        "message":{
            "text":"(Bot): Hi '.$name.','.GetResponseMessage($message ). '"
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

// Processing Messages To Reply
$TEAM_DATA;
function GetResponseMessage($userInput){
    global $TEAM_DATA;
    $TEAM_DATA = json_decode(file_get_contents('teamdata.json'),true);
    return $TEAM_DATA['messages']['greeting'];
}
?>