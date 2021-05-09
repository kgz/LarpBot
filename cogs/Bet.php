<?php

use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Author;

/**
 * Creates and manages a 'Bet' instance.
 * 
 * @property Discord::Message           $ctx        The message context
 * @property string                     $bet        The bet the users wants to make.
 * @property Discord::Discord           $bot        The discord Bot
 *  
 */

class Bet{
    public string $bet;
    public array $believers = [];
    public array $doubters = [];
    public Author $author;
    public string $authorId;
    public $winPayout = ["believe"=>1, "doubt"=>1];
    public int $timeLeft = 25;
    public int $toStart = 25;
    public bool $started = false;
    public $channel;
    public $guildId;
    public $bot;

    private $footerMessage = "";
    private $embed;
    private $embedMessage;
    private $_loopInterval = 5;

    function __construct($ctx, $bet, $bot)
    {
        $this->channel = $ctx->channel;
        $this->guildId = $ctx->channel->guild_id;
        $this->bet = $bet;
        $this->author = new Author($bot, [
            "name"=>$ctx->author->username, 
            "icon_url"=> $ctx->author->avatar,
        ]);
        $this->authorId = $ctx->author->id;
        $this->bot = $bot;
    }

    public function start($bot){
      $this->heartBeat($bot);
    }


    private function heartBeat($bot){
        $bot->loop->addtimer(1, function() use ($bot){
            $newEmbed = $this->formEmbed($bot);
            $this->updateMessage($newEmbed);
            if(!$this->started){
                $this->toStart -= 1;
                if($this->toStart < 1){
                    $this->started = true;
                    $this->channel->sendMessage("The bet: " . $this->bet . " is now open for bets!");
                }
            }
            else $this->timeLeft -=1;

            switch(true){
                case !$this->started:
                    $this->footerMessage = "{$this->timeLeft}s | !start | !time <seconds> | !delete";
                    break;
                    
                case $this->timeLeft > 60:
                    $t = $this->timeLeft;
                    $leftOver = $t%60;
                    $minsleft = (($t - $leftOver) / 60) +1;
                    $this->footerMessage = "less then {$minsleft}m Left!";
                    break;

                case $this->timeLeft > 30 && $this->timeLeft < 60:
                    $this->footerMessage = "less then 60s Left!";
                    break;

                case $this->timeLeft < 30:
                    $this->footerMessage = "less then 30s Left!";
                    break;

            }
            if($this->timeLeft < 1) 
            {
                $this->footerMessage = "Finished, type !finish <'b' or 'd'>";
                if($this->started)
                    $this->finishBet(-1);
                return;
            }
            // echo $this->timeLeft . " | " . $this->toStart, PHP_EOL;
            $this->heartBeat($bot);

                       

        });
    }

    private function payoutLogic($winner, $winnerPayout, $loser){
        $totalWon = 0;
        $totalLost = 0;
        foreach(array_keys($winner) as $user){
            $won = $this->believers[$user] + 
            ($this->believers[$user] * $winnerPayout);

            $this->bot->db->givePoints(
                $this->channel->guild_id, 
                $user, 
                $won
            );
            $totalWon = $totalWon + $won;
        } 
        foreach(array_keys($loser) as $user){ 
            $totalLost = $totalLost + $loser[$user];
        } 
        return $totalWon;
    }

    public function finishBet($winners){

        if($winners == -1){
            $this->channel->sendMessage("The bet is now closed, type `!stop <'b', 'd' or 'draw'>` to select a winner");
            $this->footerMessage = "Finished, type !stop <'b', 'd' or 'draw'>";
            return ;
        }

        unset($this->bot->bets[$this->guildId]);

        switch(true){
            case $winners == 2:
                $this->payoutLogic($this->believers, 0, []);
                $this->payoutLogic($this->doubters, 0, []);
                $this->footerMessage = "Bet resulted in a draw";
                break;

            case $winners == 1:
                $points = $this->payoutLogic($this->believers, $this->winPayout['believe'], $this->doubters);
                $this->footerMessage = "Bet resulted in believers winning, paid out $points points.";
                break;

            case $winners == 0:
                $points = $this->payoutLogic($this->doubters, $this->winPayout['doubt'], $this->believers);
                $points = number_format_short($points);
                $this->footerMessage = "Bet resulted in believers winning, paid out $points points.";
                break;
        }

        $this->channel->sendMessage("the bet is complete $winners");
        $newEmbed = $this->formEmbed($this->bot);
        $this->updateMessage($newEmbed);
        return;
    }

    private function updateMessage(Embed $embed){

        $bot = $this->bot;
        if(!$this->embedMessage){
            $this->channel->sendEmbed($embed)->done(function (Message $message) {
                $this->embedMessage = $message;
            });
            return;
        }
        if($this->embed == $embed)return;
        echo "embed updated", PHP_EOL;
        $this->embedMessage->channel->editMessage(
            $this->embedMessage, "", 0, $embed);
        $this->embed = $embed;
    }

    private function formEmbed($bot){
        $b = count($this->believers);
        $d = count($this->doubters);
        $embed = new Embed($bot, [
            "title" => "\n**{$this->bet}**", 
            "discription" => "teasdfasd",
            "author"=>$this->author,
            "footer"=> [
                "icon_url"=> "https://cdn.discordapp.com/embed/avatars/0.png",
                "text"=> $this->footerMessage
            ],
            "fields" =>[
                
                [
                    "name"=> "Believers:",
                    "value"=> "\t{$b}\t",
                    "inline"=> true
                ],
                  [
                    "name"=> "Doubters:",
                    "value"=> "\t{$d}\t",
                    "inline"=> true
                  ],
                  [
                    "name"=> json_decode('"\u200b"'),
                    "value"=> json_decode('"\u200b"'),
                    "inline"=> true
                ],
                  [
                    "name"=> "Payout:",
                    "value"=> $this->winPayout['believe'] * 100 . '%',
                    "inline"=> true
                ],
                [
                    "name"=> json_decode('"\u200b"'),
                    "value"=> $this->winPayout['doubt'] * 100 . '%',
                    "inline"=> true
                ],
                [
                    "name"=> json_decode('"\u200b"'),
                    "value"=> json_decode('"\u200b"'),
                    "inline"=> true
                ],
    
    
            ]
    
        ]);
        return $embed;
    }

}