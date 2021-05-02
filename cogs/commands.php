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
    "take" => "takePoints"
    // "create" => "createBet",
    // "create" => "createBet"
];


function ping($ctx, $bot, $str)
{
    $ctx->channel->sendMessage("pong");
};

function createBet($ctx, $bot, $str)
{
    if ($bot->bet) 
        return $ctx->channel->sendMessage("There is already a bet going, it will start in: {$bot->bet->timeLeft}");
    if (!trim($str))
        return $ctx->channel->sendMessage("usage: !create will x do y?");

    $ctx->channel->sendMessage("create");
    $bot->bet = new Bet($ctx, $str, $bot);
    $bot->bet->start($bot);
}

function _start($ctx, $bot, $str)
{
    if (!$bot->bet) 
        return $ctx->channel->sendMessage("you need to start a bet, !create");
    if ($ctx->author->id == $bot->bet->authorId) 
        $bot->bet->toStart = 0;
}

function _stop($ctx, $bot, $str)
{
    if (!$bot->bet)
        return $ctx->channel->sendMessage("you need to start a bet, !create");
        
    if ($ctx->author->id == $bot->bet->authorId) {

        $true = ["1", "b", "believe", "true", "t", "believers"];
        $false = ["0", "d", "doubt", "false", "f", "doubters"];
        $winners = null;

        switch(true){
            case in_array(strtolower(trim($str)), $true):
                $winners = 1; break;
            case in_array(strtolower(trim($str)), $false):
                $winners = 0; break;
        }
            
        if($winners === null)
            return $ctx->channel->sendMessage("Please indicate a winner. \n\n `!stop b` for believers or\n`!stop d` for doubters");
        
        $bot->bet->timeLeft = 0;
        $bot->bet->finishBet($winners);
        $bot->bet = null;
    }
}


function doubt($ctx, $bot, $str)
{
    if (!in_array($ctx->author->id, $bot->bet->believers) &&
        !in_array($ctx->author->id, $bot->bet->doubters)) 
            return array_push($bot->bet->doubters, $ctx->author->id);
    $ctx->channel->sendMessage("{$ctx->author->name}: You can only vote once.");
}


function believe($ctx, $bot, $str)
{
    if (!in_array($ctx->author->id, $bot->bet->believers) &&
        !in_array($ctx->author->id, $bot->bet->doubters)) 
            return array_push($bot->bet->believers, $ctx->author->id);
    $ctx->channel->sendMessage("{$ctx->author->name}: You can only vote once.");

}


function giveTakePreCheck($ctx, $bot, $str){
    
    $args = preg_split('/\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);
    preg_match('/(\d{17,18})/', $args[0], $out);
    $user = $out?[1]:null;
    $error = false;

    switch(true){
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
    /**
     * TODO PERMISSIONS 
     */
    $args = giveTakePreCheck($ctx, $bot, $str);
    echo (bool)$args, "----",  PHP_EOL;
    if(!$args) return;

    $amount = intval($args[1]);

    $result = $bot->db->givePoints($ctx->author->id, $amount);

    print_r($result);
}



function takePoints($ctx, $bot, $str)
{
    /**
     * TODO PERMISSIONS 
     */
    $args = giveTakePreCheck($ctx, $bot, $str);
    if(!$args) return;

    $amount = intval($args[1]);

    $result = $bot->db->givePoints($ctx->author->id, $amount);

    print_r($result);
}