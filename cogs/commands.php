<?php
$COMMANDS = [
    "ping" => "ping",
    "create" => "createBet",
    "start" => "_start",
    "stop" => "_stop",
    "finish" => "_stop",
    "doubt" => "doubt",
    "believe" => "believe"
    // "create" => "createBet",
    // "create" => "createBet"
];


function ping($ctx, $bot, $str)
{
    //returns pong
    $ctx->channel->sendMessage("pong");
};

function createBet($ctx, $bot, $str)
{


    //NEED TO CHANGE 
    if ($bot->bet) {
        $ctx->channel->sendMessage("There is already a bet going, it will start in: {$bot->bet->timeLeft}");
        return;
    }

    if (!trim($str)) {
        $ctx->channel->sendMessage("usage: !create will x do y?");
        return;
    }

    $ctx->channel->sendMessage("create");
    $bot->bet = new Bet($ctx, $str);
    $bot->bet->start($bot);
}

function _start($ctx, $bot, $str)
{
    if (!$bot->bet) {
        $ctx->channel->sendMessage("you need to start a bet, !create");
        return;
    }

    if ($ctx->author->id == $bot->bet->author) {
        $bot->bet->toStart = 0;
    }
}

function _stop($ctx, $bot, $str)
{
    if (!$bot->bet) {
        $ctx->channel->sendMessage("you need to start a bet, !create");
        return;
    }

    if ($ctx->author->id == $bot->bet->author) {

        $true = ["1", "b", "believe", "true", "t", "believers"];
        $false = ["0", "d", "doubt", "false", "f", "doubters"];

        $winners = 0;
        if (in_array(strtolower(trim($str)), $true))
            $winners = 1;
        elseif (in_array(strtolower(trim($str)), $false))
            $winners = 0;
        else {
            $ctx->channel->sendMessage("Please indicate a winner. \n\n `!stop b` for believers or\n`!stop d` for doubters");
            return;
        }
        $bot->bet->timeLeft = 0;

        $bot->bet->finishBet($winners);
        $bot->bet = null;
    }
}


function doubt($ctx, $bot, $str)
{
    if (!in_array($ctx->author->id, $bot->bet->believers) &&
        !in_array($ctx->author->id, $bot->bet->doubters)) 
        array_push($bot->bet->doubters, $ctx->author->id);
    else
        $ctx->channel->sendMessage("{$ctx->author->name}: You can only vote once.");
}


function believe($ctx, $bot, $str)
{
    if (!in_array($ctx->author->id, $bot->bet->believers) &&
        !in_array($ctx->author->id, $bot->bet->doubters)) 
        array_push($bot->bet->believers, $ctx->author->id);
    else
    $ctx->channel->sendMessage("{$ctx->author->name}: You can only vote once.");

}