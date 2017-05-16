<?php
/* create messenger instance */
$fbMessenger = new Messenger();
$myTeam = new Team();

/* configure verification between bot and fb */
$fbMessenger->verify_token('my_secure_verify_token');
$fbMessenger->verify_page_access('PAGE_ACCESS_TOKEN');

/* set team data */
$myTeam->read_data_file('teamdata.json');

/* Testing conversation
$fbMessenger->listen_message();
$fbMessenger->set_reply_message();
$fbMessenger->encode_reply_message();
$fbMessenger->send_message();
*/
$greeting = $myTeam->get_greeting_message();
$fbMessenger->listen_message();

$fbMessenger->set_reply_message($greeting);
$fbMessenger->encode_reply_message();
$fbMessenger->send_message();
/**** COMMANDS CLAS ****/
$command = new Command($fbMessenger,$myTeam);
class Command{
    protected $KEYWORDS;
    protected $fbMessenger;
    protected $myTeam;
    public function __construct($fbMessenger,$myTeam){
        $this->fbMessenger = $fbMessenger;
        $this->myTeam = $myTeam;
        $this->KEYWORDS = json_decode(file_get_contents('keywords.json'),true);
        $hear = $fbMessenger->listen_message();
        foreach($this->KEYWORDS as $command => $keyword){
            switch($command){
                case 'GREETING':
                    in_array($hear,$keyword)?$this->command_greeting():false;
                    break;
                case 'BYE':
                    in_array($hear,$keyword)?$this->command_bye():false;
                    break;
                case 'MEMBERS':
                    in_array($hear,$keyword)?$this->command_members():false;
                    break;
                default:
            }
        }
    }
    
    public function command_greeting(){
        $message = $this->myTeam->get_greeting_message();
        $this->fbMessenger->set_reply_message($message);
        $fbMessenger->encode_reply_message();
        $this->fbMessenger->send_message(); 
    }
    public function command_bye(){
        $message = $this->myTeam->get_goodbye_message();
        $this->fbMessenger->set_reply_message($message);
        $fbMessenger->encode_reply_message();
        $this->fbMessenger->send_message(); 
    }
    public function command_members(){
        $this->myTeam->set_team_members();
        $message='Our Team members are:';
        $this->fbMessenger->set_reply_message($message);
        $fbMessenger->encode_reply_message();
        $this->fbMessenger->send_message(); 
        foreach($this->myTeam->get_team_members() as $member){
            $message = 'Name : '.$member['name'];
            $this->fbMessenger->set_reply_message($message);
            $fbMessenger->encode_reply_message();
            $this->fbMessenger->send_message(); 
            $message = 'Posittion : '.$member['position'];
            $this->fbMessenger->set_reply_message($message);
            $fbMessenger->encode_reply_message();
            $this->fbMessenger->send_message(); 
        }
    }
}

/**** TEAM CLASS ****/
class Team{
    protected $TEAM_DATA;
    protected $team_info;
    protected $team_members; 
    public function __construct(){
        $this->TEAM_DATA = array();
        $this->team_info = array(
            'name'=>'',
            'description'=>''
            );
        $this->team_members = array(
            array(
                'name'=>'',
                'position'=>''
                )
            );
    }
    public function read_data_file($filename){
        $this->TEAM_DATA = json_decode( file_get_contents($filename),true);
    }
    public function set_team_info(){
        $this->team_info['name'] = $this->TEAM_DATA['name'];
        $this->team_info['description'] = $this->TEAM_DATA['description'];
    }
    public function get_team_info(){
        return $this->team_info;
    }
    public function set_team_members(){
        $this->team_members = array();
        array_push($this->team_members, array('name'=> $this->TEAM_DATA['team']['president'],'position'=>'President'));
        array_push($this->team_members, array('name'=> $this->TEAM_DATA['team']['vice-president'],'position'=>'Vice President'));
        foreach($this->TEAM_DATA['team']['members'] as $member_name){
            array_push($this->team_members, array('name'=> $member_name,'position'=>'Member'));
        }
    }
    public function get_team_members(){
        return $this->team_members;
    }
    public function get_greeting_message(){
        return $this->TEAM_DATA['messages']['greeting'];
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

    public function __construct(){
        $this->sender_message = '';
        $this->sender_id = 0;
        $this->sender_name = '';
        $this->reply_message = '';
    }
    public function verify_page_access($page_token){
        $this->PAGE_ACCESS_TOKEN = getenv($page_token);
    }
    public function verify_token($my_token){
        /* validate verify token for setting up web hook */ 
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
    public function encode_reply_message(){
        /*prepare response json */
        $this->reply_json = '{
        "recipient":{
            "id":"' . $this->sender_id . '"
            },
            "message":{
                "text":" '.$this->reply_message. '"
            }
        }';
    }
    public function set_reply_message($data = ''){
        if($data == '' && $this->sender_message != ''){
            /* default message to reply what sender said*/
            $data =  '(Bot): Hi '.$this->sender_name.',you said, '.$this->sender_message;
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
        $url = "https://graph.facebook.com/v2.6/me/messages?access_token=$this->PAGE_ACCESS_TOKEN";
        if ($this->sender_message != ''){
            $result = $this->curl_send_post_request($url,$this->reply_json);
        }
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
?>