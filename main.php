<?php
use Discord\Discord;
require_once __DIR__.'/cogs/Bet.php';

include_once __DIR__.'/cogs/commands.php';

include __DIR__.'/vendor/autoload.php';
include __DIR__.'/cogs/database.php';

class Bot extends Discord{
    public $commands;
    public $prefix;
    public $db;
    public Array $bets = [];

    public function setup(string $prefix,  array $commands, $DATABASE_IP, $DATABASE_PORT, $DATABASE_USERNAME, $DATABASE_PASSWORD){
        $this->commands = $commands;
        $this->prefix = $prefix;
        $this->db = new Database($DATABASE_IP, $DATABASE_PORT, $DATABASE_USERNAME, $DATABASE_PASSWORD);
    }
}

// DATABASE STUFF
$DATABASE_IP = 'localhost';
$DATABASE_PORT = '3306';
$DATABASE_USERNAME = 'root';
$DATABASE_PASSWORD = '';// ?: getenv('MYSQLPASS');

$BOT_PREFIX = '!';

// try and start mysql database
pclose( popen( 'start /B mysqld  > NUL', 'r' ) );


$bot = new Bot([
    'token' => getenv('LarpBot'),
    'loggerLevel' => Monolog\Logger::ERROR]);
$bot->setup("!", $COMMANDS, "localhost", 3306, 'root', '');


//MAIN LOOP
$bot->on('ready', function ($discord) {
	echo "Bot is ready!", PHP_EOL;

    $discord->on('message', function ($message, $discord) {
        // print_r($message);

        if($message->author->id == $discord->id) return;
        if($message->content[0] != $discord->prefix) return;

        $mess = substr($message->content, 1);
        foreach(array_keys($discord->commands) as $command){
            $messCommand = substr($mess, 0, strlen($command));
            if($messCommand == $command){
                // print_r($message);
                $f = $discord->commands[$messCommand];
                // TODO: Sanitize and check 
                $f($message, $discord, substr($mess, strlen($command)));
                echo "{$message->author->username}: {$message->content}",PHP_EOL;

                return;
            }

        }

        
		// echo "{$message->author->username}: {$message->content}",PHP_EOL;
	});
});

$bot->run();






// $discord->run();