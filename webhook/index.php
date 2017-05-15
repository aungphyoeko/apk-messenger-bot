<?php
require('../vendor/autoload.php');
use Mpociot\BotMan\BotManFactory;
use Mpociot\BotMan\BotMan;
use Mpociot\BotMan\Cache\DoctrineCache;


$config = [
    'hipchat_urls' => [
        'YOUR-INTEGRATION-URL-1',
        'YOUR-INTEGRATION-URL-2',
    ],
    'nexmo_key' => 'YOUR-NEXMO-APP-KEY',
    'nexmo_secret' => 'YOUR-NEXMO-APP-SECRET',
    'microsoft_bot_handle' => 'YOUR-MICROSOFT-BOT-HANDLE',
    'microsoft_app_id' => 'YOUR-MICROSOFT-APP-ID',
    'microsoft_app_key' => 'YOUR-MICROSOFT-APP-KEY',
    'slack_token' => 'YOUR-SLACK-TOKEN-HERE',
    'telegram_token' => 'YOUR-TELEGRAM-TOKEN-HERE',
    'facebook_token' => getenv('PAGE_ACCESS_TOKEN'),
    'facebook_app_secret' => getenv('APP_SECRET'),
    'wechat_app_id' => 'YOUR-WECHAT-APP-ID',
    'wechat_app_key' => 'YOUR-WECHAT-APP-KEY',
];

// create an instance
$botman = BotManFactory::create($config);
$botman->verifyServices('my_secure_verify_token');
$botman = BotManFactory::create($config, new DoctrineCache($doctrineCacheDriver));

// give the bot something to listen for.
$botman->hears('hello', function (BotMan $bot) {
    $user = $bot->getUser();
	$bot->reply('Hello '.$user->getFirstName().',');
    $bot->reply(get_greeting_message());
});

// start listening
$botman->listen();

$botman->hears("call me {name}", function (BotMan $bot, $name) {
    // Store information for the currently logged in user.
    // You can also pass a user-id / key as a second parameter.
    $bot->userStorage()->save([
        'name' => $name
    ]);

    $bot->reply('I will call you '.$name);
});

$team_data_file = json_decode(file_get_contents('teamdata.json'));
function get_greeting_message(){
    global $team_data_file;
    return $team_data_file['messages']['greeting'];
}
?>