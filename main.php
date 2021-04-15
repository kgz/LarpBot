<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;

function t() {
    echo "TT",PHP_EOL;
};

$commands = ["t" => "t"];


$discord = new Discord([
	'token' => getenv('LarpBot'),
]);

$discord->on('ready', function ($discord) {
	echo "Bot is ready!", PHP_EOL;

	// Listen for messages.
	$discord->on('message', function ($message, $discord) {
        global $commands;

        foreach(array_keys($commands) as $command){
            if($message->content == $command){
                print_r($message);
                $f = $commands["t"];
                $f();
                $message->channel->sendMessage('Hello '.$message->author.'!');

            }

        }

        
		echo "{$message->author->username}: {$message->content}",PHP_EOL;
	});
});



$discord->run();