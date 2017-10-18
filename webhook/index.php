<?php
/* create messenger instance */
$fbMessenger = new Messenger();
if($fbMessenger->verify_token('my_secure_verify_token')) return;
$fbMessenger->verify_page_access('PAGE_ACCESS_TOKEN');

if ($fbMessenger->listen_message() == ''){
    return;
}
$command = new Command($fbMessenger);
/**** COMMAND CLASS ****/
class Command {
    protected $fbMessenger;
    protected $myTeam;
    public function __construct($fbMessenger){
        $hear = $fbMessenger->listen_message();
        if($hear == '') return;
        $this->fbMessenger = $fbMessenger;
        $this->fbMessenger->set_reply_data($hear);
    }
}
/* Messenger class */
class Messenger {
    protected $PAGE_ACCESS_TOKEN;
    protected $sender_message;
    protected $sender_id;
    protected $sender_name;
    protected $reply_data;
    protected $reply_json;

    public function __construct(){
        $this->sender_message = '';
        $this->sender_id = 0;
        $this->sender_name = '';
        $this->reply_data = new Text('Hello');
    }
    public function verify_page_access($page_token){
        $this->PAGE_ACCESS_TOKEN = getenv($page_token);
    }
    public function verify_token($my_token){
        /* validate verify token for setting up web hook */ 
        if (isset($_GET['hub_verify_token'])) { 
            if ($_GET['hub_verify_token'] === $my_token) {
                echo $_GET['hub_challenge'];
                return true;
            } else {
                echo 'Invalid Verify Token';
                return true;
            }
        }
        return false;
    }
    public function set_reply_data($msg=''){
        $this->reply_data = new Text($msg);
        
    }
    public function encode_reply_message(){
        /*prepare response json */
        $this->reply_json = '{
            "recipient":{
                "id":"'.$this->sender_id.'"},';
        $this->reply_json .= '"message":{';
        
        $this->reply_json .= '}';  
        $this->reply_json .='}';
    }

    public function listen_message(){
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {
            $this->sender_id = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $this->sender_message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent
            $this->sender_name = $this->request_sender_name();
            $this->reply_data = '';
            return $this->sender_message;
        }
        $this->sender_message = '';
        $this->reply_data = '';
        return '';
    }
    protected function request_sender_name(){
        $url = "https://graph.facebook.com/v2.6/$this->sender_id?fields=first_name,last_name&access_token=$this->PAGE_ACCESS_TOKEN";
        $sender = $this->curl_send_get_request($url);
        return $sender["first_name"];
    }

    public function send_message(){
        $this->encode_reply_message();
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$this->PAGE_ACCESS_TOKEN";
        $result = $this->curl_send_post_request($url,$this->reply_json);
    }
    protected function curl_send_post_request($url,$data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch); // user will get the message
        curl_close($ch);
        return $result;
    }
    protected function curl_send_get_request($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($result,true);
        curl_close($ch);
        return $obj;
    }
}
class Text{
    protected $message;
    public function __construct($msg =''){
        $this->message = '"text": "'.$msg.'"';
    }
    public function set_message($msg){
        $this->message = '"text": "'.$msg.'"';
    }
    public function join_new_message($msg){
        $this->message .= $msg;
    }
    public function get_text(){
        return $this->message;
    }
}
?>