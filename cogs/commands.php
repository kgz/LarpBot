<?php
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Author;

use function React\Promise\Stream\first;

$COMMANDS = [
    "ping" => "ping",
    "create" => "createBet",
    "start" => "_start",
    "stop" => "_stop",
    "finish" => "_stop",
    "doubt" => "doubt",
    "believe" => "believe",
    "give" => "givePoints",
    "take" => "takePoints",
    'points' => 'points'
    // "create" => "createBet",
    // "create" => "createBet"
];


function ping($ctx, $bot, $str)
{
    $ctx->channel->sendMessage("pong");
};

function createBet($ctx, $bot, $str)
{

    if ($bot->bets[$ctx->channel->guild_id] ?? null) 
        return $ctx->channel->sendMessage("There is already a bet going, it will start in: {$bot->bets[$ctx->channel->guild_id]->timeLeft}");
    if (!trim($str))
        return $ctx->channel->sendMessage("usage: !create will x do y?");
    $bot->bets[$ctx->channel->guild_id] = new Bet($ctx, $str, $bot);
    $bot->bets[$ctx->channel->guild_id]->start($bot);
    $ctx->react("✅");

}

function _start($ctx, $bot, $str)
{ /**
 * 
 * 
 * check if theres already a bet going
 * 
 */
    if (!array_key_exists($ctx->channel->guild_id, $bot->bets))
        return $ctx->channel->sendMessage("you need to start a bet, !create");
    if ($ctx->author->id == $bot->bets[$ctx->channel->guild_id]->authorId) 
        $bot->bets[$ctx->channel->guild_id]->toStart = 0;
}

function _stop($ctx, $bot, $str)
{
    if (!$bot->bets[$ctx->channel->guild_id ?? null] ?? null)
        return $ctx->channel->sendMessage("you need to start a bet, !create");
        
    if ($ctx->author->id == $bot->bets[$ctx->channel->guild_id]->authorId) {

        $true = ["1", "b", "believe", "true", "t", "believers"];
        $false = ["0", "d", "doubt", "false", "f", "doubters"];
        $none = ["n", "none", "null", "draw"];
        $winners = null;

        switch(true){
            case in_array(strtolower(trim($str)), $true):
                $winners = 1; break;
            case in_array(strtolower(trim($str)), $false):
                $winners = 0; break;
            case in_array(strtolower(trim($str)), $none):
                $winners = 2; break;


        }
            
        if($winners === null)
            return $ctx->channel->sendMessage("Please indicate a winner. \n\n `!stop b` for believers,\n`!stop d` for doubters,\n`!stop draw`");
        
        $bot->bets[$ctx->channel->guild_id]->timeLeft = 0;
        $bot->bets[$ctx->channel->guild_id]->started = false;

        $bot->bets[$ctx->channel->guild_id]->finishBet($winners);
    }
}


function doubtBelievePreCheck($ctx, $bot, $str){ 
    $error = false;
    $bet =  $bot->bets[$ctx->channel->guild_id];
    
    if(trim($str) == "all")
        $str = $bot->db->balance($ctx->channel->guild_id, $ctx->author->id);
    
    $balance  = $bot->db->balance($ctx->channel->guild_id, $ctx->author->id);
    
    switch(true){

        case !$bot->bets[$ctx->channel->guild_id]->started:
            $error = "please wait for the bet to start ({$bot->bets[$ctx->channel->guild_id]->toStart} seconds)";
            break;
        case array_key_exists($ctx->author->id, $bet->believers):
            $error = "{$ctx->author->name}: You have already voted to believe!";
            break;
        case array_key_exists($ctx->author->id, $bet->doubters):
            $error = "{$ctx->author->name}: You have already voted to doubt!";
            break;

        case !is_numeric($str):
            $error = "Error: Amount must be a numeric value.";
            break;
        
        case intval($str) < 1:
            $error = "Value must be above 0";
            break;
        case $balance < intval($str):
            $error = "You only have $balance points.";
            break;

         
    }
    if($error) $ctx->channel->sendMessage($error);
    
    return $error ? false : intval($str);

}

function doubt($ctx, $bot, $str)
{
    $amount = doubtBelievePreCheck($ctx, $bot, $str);
    if(!$amount) return;

    $bot->db->removePoints($ctx->channel->guild_id, $ctx->author->id, $amount);
    $bot->bets[$ctx->channel->guild_id]->doubters[$ctx->author->id] = $amount;
    $ctx->react("✅");

    // print_r($bot->bets[$ctx->channel->guild_id]->doubters);
    // print_r($bot->bets[$ctx->channel->guild_id]->believers);
}


function believe($ctx, $bot, $str)
{
    $amount = doubtBelievePreCheck($ctx, $bot, $str);

    if(!$amount) return;
    $bot->db->removePoints($ctx->channel->guild_id, $ctx->author->id, $amount);
    $bot->bets[$ctx->channel->guild_id]->believers[$ctx->author->id] = $amount;
    $ctx->react("✅");

    // print_r($bot->bets[$ctx->channel->guild_id]->doubters);
    // print_r($bot->bets[$ctx->channel->guild_id]->believers);
}


function giveTakePreCheck($ctx, $bot, $str){
    $args = preg_split('/\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);
    preg_match('/(\d{17,18})/', $args[0], $out);
    $user = $out?[1]:null;
    $error = false;

    switch(true){
    
        case !isAdmin($ctx, $bot, "administrator, manage_guild"); 
            echo "invalid permissions", PHP_EOL;
            return false;
            break;
        case count($args) < 2:
            $error = "Usage:\n `!give <@user> <amount>`";
            break;

        case !$user;
            $error = "Invalid user supplied, must be a mention or id.";
            break;
        
        case !is_numeric($args[1]):
            $error = "Error: Amount must be a numeric value.";
            break;
    }

    //check if args [1] is a user id / DISCORD::Mention 
    if($error) $ctx->channel->sendMessage($error);

    return (bool)$error ? false : $args;
}

function givePoints($ctx, $bot, $str)
{
    $args = giveTakePreCheck($ctx, $bot, $str);
    echo (bool)$args, "----",  PHP_EOL;
    if(!$args) return;

    $amount = intval($args[1]);
    print_r($ctx->channel->guild_id);
    $result = $bot->db->givePoints($ctx->channel->guild_id, $ctx->author->id, $amount);

    print_r($result);
    $ctx->react("✅");
}



function takePoints($ctx, $bot, $str)
{
    $args = giveTakePreCheck($ctx, $bot, $str);
    if(!$args) return;

    $amount = intval($args[1]);

    $result = $bot->db->givePoints($ctx->channel->guild_id, $ctx->author->id, $amount);

    print_r($result);
    $ctx->react("✅");

}


function points($ctx, $bot, $str){
    $points  = number_format_short($bot->db->balance($ctx->channel->guild_id, $ctx->author->id));
    $ctx->channel->sendMessage("Balance: {$points}");
}