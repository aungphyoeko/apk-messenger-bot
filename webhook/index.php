<?php
$messenger = new Messenger();
$messenger->verify_token('my_secure_verify_token');
$messenger->listen_message();
$messenger->process_reply_message();
$messenger->send_message();

class Messenger{
    protected $PAGE_ACCESS_TOKEN;
    protected $sender_message;
    protected $sender_id;
    protected $sender_name;
    protected $reply_message;

    public function __construct(){
        $this->PAGE_ACCESS_TOKEN = getenv('PAGE_ACCESS_TOKEN');
        $this->sender_message = '';
        $this->sender_id = 0;
        $this->sender_name = '';
    }
    public function verify_token($my_token){
        /* validate verify token needed for setting up web hook */ 
        if (isset($_GET['hub_verify_token'])) { 
            if ($_GET['hub_verify_token'] === $my_token) {
                echo $_GET['hub_challenge'];
                return;
            } else {
                echo 'Invalid Verify Token';
                return;
            }
        }
    }
    public function process_reply_message(){
        /*prepare response*/
        $this->reply_message = '{
        "recipient":{
            "id":"' . $this->sender_id . '"
            },
            "message":{
                "text":"(Bot): Hi '.$this->sender_name. ', You said, '.$this->sender_message. '"
            }
        }';
    }
    public function listen_message(){
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {
            $this->sender_id = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $this->sender_message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent
            $this->sender_name = $this->request_sender_name();
            return true;
        }
        $this->sender_message = '';
        return false;
    }
    protected function request_sender_name(){
        $url = "https://graph.facebook.com/v2.6/$this->sender_id?fields=first_name,last_name&access_token=$this->PAGE_ACCESS_TOKEN";
        $sender = $this->curl_send_get_request($url);
        return $sender["first_name"];
    }

    public function send_message(){
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$this->PAGE_ACCESS_TOKEN";
        $this->curl_send_post_request($url,$this->reply_message);
    }
    protected function curl_send_post_request($url,$data){
         $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->reply_message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        if ($this->sender_message != ''){
            $result = curl_exec($ch); // user will get the message
        }
        curl_close($ch);
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
?>