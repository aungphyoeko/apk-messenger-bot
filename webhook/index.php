<?php
$messenger = new Messenger();
$messenger->verify_webhook();
$messenger->listen_message();
$messenger->get_name();
$messenger->send_message();
class Messenger{
    protected $PAGE_ACCESS_TOKEN;
    protected $message;
    protected $sender_id;
    protected $sender_name;
    public function __construct(){
        $this->PAGE_ACCESS_TOKEN =getenv('PAGE_ACCESS_TOKEN');
    }
    public function listen_message(){
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {

            $this->sender_id = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $this->message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent
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

public function get_name(){
    $url = "https://graph.facebook.com/v2.6/$this->sender_id?fields=first_name,last_name&access_token=$this->PAGE_ACCESS_TOKEN";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);
    $obj = json_decode($result,true);
    curl_close($ch);
    $this->sender_name = $obj["first_name"];
    return $this->sender_name;
}
public function send_message(){
    $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$this->PAGE_ACCESS_TOKEN";
 /*initialize curl*/
    $ch = curl_init($url);
    /*prepare response*/

    $jsonData = '{
    "recipient":{
        "id":"' . $this->sender_id . '"
        },
        "message":{
            "text":"(Bot): Hi '.$this->sender_name.','.$this->message . '"
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