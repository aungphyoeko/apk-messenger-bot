<?php
/* create messenger instance */
$fbMessenger = new Messenger();
if($fbMessenger->verify_token('my_secure_verify_token')) return;
$fbMessenger->verify_page_access('PAGE_ACCESS_TOKEN');

if ($fbMessenger->listen_message() == ''){
    return;
}

$myTeam = new Team();

$command = new Command($fbMessenger,$myTeam);
/**** COMMAND CLASS ****/
class Command{
    protected $KEYWORDS;
    protected $fbMessenger;
    protected $myTeam;
    public function __construct($fbMessenger,$myTeam){
        $hear = $fbMessenger->listen_message();
        if($hear == '') return;
        $this->fbMessenger = $fbMessenger;
        $this->myTeam = $myTeam;
        $this->myTeam->read_data_file();
        $this->KEYWORDS = json_decode(file_get_contents('keywords.json'),true);
        $this->myTeam->read_data_file();
        foreach($this->KEYWORDS as $command=>$keywords){
            foreach ($keywords as $keyword){
                $matches = preg_match_all('/\b('.$keyword.')\b/',strtolower($hear)); 
                if($matches > 0) break;  
            }
            if($matches > 0) break;
            $command = '';
        }
        switch($command){
            case 'GREETING':
                $this->command_greeting();
                break;
            case 'BYE':
                $this->command_bye();
                break;
            case 'MEMBERS':
                $this->command_members();
                break;
            case 'MEETING':
                $this->command_meeting();
                break;
            case 'INFO':
                $this->command_info();
                break; 
            case 'THANK':
                $this->command_thank();
                break;
            case 'WEBSITE':
                $this->command_website();
                break;
            default:
                $this->fbMessenger->set_reply_message();
                $this->fbMessenger->send_message(); 
        }
    }
    public function command_website(){
        $message = $this->myTeam->get_team_info();
        $button = new Button('template','web_url','Visit Our Website!','',$message['website']);
        $this->fbMessenger->insert($button);
        $this->fbMessenger->set_reply_message('Our website link is '.$message['website']);
        $this->fbMessenger->send_message(); 

    }
    public function command_meeting(){
        $message = $this->myTeam->get_team_meeting();
        $this->fbMessenger->set_reply_message('There are meetings on every '.$message['day'].' at '.$message['time'].' in '.$message['location'].'.');
        $this->fbMessenger->send_message(); 
    }
    public function command_info(){
        $message = $this->myTeam->get_team_info();
        $this->fbMessenger->set_reply_message('Our club name is '.$message['name'].'. Our club description is '. $message['description']);
        $this->fbMessenger->send_message(); 
    }
    public function command_thank(){
        $this->fbMessenger->set_reply_message($this->myTeam->get_thank_message());
        $this->fbMessenger->send_message(); 

    }
    public function command_greeting(){
        $this->fbMessenger->set_reply_message($this->myTeam->get_greeting_message());
        $this->fbMessenger->send_message(); 
    }
    public function command_bye(){
        $this->fbMessenger->set_reply_message($this->myTeam->get_goodbye_message());
        $this->fbMessenger->send_message(); 
    }
    public function command_members(){
        $this->fbMessenger->set_reply_message('Our current active members are:');
        $this->fbMessenger->send_message(); 
        $count = 0;
        foreach($this->myTeam->get_team_members() as $position => $name){
            $count ++;
            if(is_array($name)){
                foreach ($name as $each){
                    $this->fbMessenger->set_reply_message($count.'. '.$each);
                    $this->fbMessenger->send_message(); 
                    $count++;
                }
            }
            else{
                $this->fbMessenger->set_reply_message($count.'. '.$position.' : '.$name);
                $this->fbMessenger->send_message(); 
            }
        }
    }
}
/**** TEAM CLASS ****/
class Team{
    protected $TEAM_DATA;
    public function __construct(){
        $this->TEAM_DATA = array();
    }
    public function read_data_file(){
        $this->TEAM_DATA = json_decode(file_get_contents('teamdata.json'),true);
    }
    public function get_team_members(){
        return $this->TEAM_DATA['members'];
    }
    public function get_team_info(){
        return $this->TEAM_DATA['info'];
    }    
    public function get_team_meeting(){
        return $this->TEAM_DATA['meeting'];
    }
    public function get_greeting_message(){
        return $this->TEAM_DATA['messages']['greeting'];
    }
    public function get_thank_message(){
        return $this->TEAM_DATA['messages']['thank'];
    }
    public function get_goodbye_message(){
         return $this->TEAM_DATA['messages']['bye'];
    }
}
/**** Messenger class ****/
class Messenger{
    protected $PAGE_ACCESS_TOKEN;
    protected $sender_message;
    protected $sender_id;
    protected $sender_name;
    protected $reply_message;
    protected $reply_json;
    protected $buttons;

    public function __construct(){
        $this->sender_message = '';
        $this->sender_id = 0;
        $this->sender_name = '';
        $this->reply_message = '';
        $this->buttons = array();
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
    public function insert($object){
        if(get_class($object) == 'Button'){
            array_push($this->buttons,$object);
        }
    }
    public function encode_reply_message(){
        /*prepare response json */
        $this->reply_json = '{
        "recipient":{
            "id":"' . $this->sender_id . '"
            }, "message":{';
        if(sizeof($this->buttons)>0){
            $this->reply_json .='
                    "attachment":{
                    "type":"template",
                    "payload":{
                        "template_type":"button",
                        "text":"What do you want to do next?",
                        "buttons":[';
            $this->reply_json .= $this->buttons[0]->get_button();
            $this->reply_json .= ']}}';   
        }
        else{
            $this->reply_json .= '"text":" '.$this->reply_message.'"';
        }
        $this->reply_json .='
        }}';  
        
    }
    public function set_reply_message($data = ''){
        if($data == '' && $this->sender_message != ''){
            /* default message to reply what sender said*/
            $data =  '(Bot): Hi '.$this->sender_name.', you said, '.$this->sender_message.'. But, I cannot process your command right now because I need to be fully developed. Sorry!';
        }
        $data = preg_replace('/\{name\}/',$this->sender_name,$data);
        $this->reply_message = $data;
    }
    public function listen_message(){
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['entry'][0]['messaging'][0]['sender']['id'])) {
            $this->sender_id = $input['entry'][0]['messaging'][0]['sender']['id']; //sender facebook id
            $this->sender_message = $input['entry'][0]['messaging'][0]['message']['text']; //text that user sent
            $this->sender_name = $this->request_sender_name();
            $this->reply_message = '';
            return $this->sender_message;
        }
        $this->sender_message = '';
        $this->reply_message = '';
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
/**** Button class ****/

class Button{
    protected $type;
    protected $title;
    protected $payload;
    protected $url;
    public function __construct($template_type = 'template',$type = 'postback',$title = 'Button',$payload = 'PAYLOAD',$url = 'URL'){
        $this->type = $type;
        $this->title = $title;
        $this->payload = $payload;
        $this->url = $url;
    }
    public function get_template(){
        switch ($this->template_type){
            default:
            case 'template':
            return;
        }
    }
    public function get_button(){
        switch ($this->type){
            default:
            case 'postback':
                return '{"type":"postback","title":"'.$this->title.'","payload":"'.$this->payload.'"}';
            case 'web_url':
                return '{
            "type":"web_url",
            "url":"'.$this->url.'",
            "title":"'.$this->title.'"
          }';
        }
    }
}
?>