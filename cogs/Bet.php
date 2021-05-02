<?php

use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Author;


/**
 * The Bet class
 * 
 * Creates and manages a 'Bet'.
 * 
 * @property Discord::Message $ctx 
 * @property string $bet 
 * @property Discord::Discord $bot
 * 
 * 
 */

class Bet{
    public string $bet;
    public array $believers = [];
    public array $doubters = [];
    public Author $author;
    public string $authorId;
    public $winPayout = ["believe"=>1, "doubt"=>1];
    public int $timeLeft = 90;
    public int $toStart = 90;
    public bool $started = false;
    public $channel;
    public $bot;

    private $footerMessage = "";
    private $embed;
    private $embedMessage;
    private $_loopInterval = 5;

    function __construct($ctx, $bet, $bot)
    {
        $this->channel = $ctx->channel;
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
                    $this->footerMessage = "< {$minsleft}m Left";
                    break;

                case $this->timeLeft > 30 && $this->timeLeft < 60:
                    $this->footerMessage = "< 60s Left";
                    break;

                case $this->timeLeft < 30:
                    $this->footerMessage = "< 30s Left";
                    break;

            }
            if($this->timeLeft < 1) 
            {
                $this->footerMessage = "Finished, type !finish <'b' or 'd'>";
                $this->finishBet(-1);
                return;
            }
            echo $this->timeLeft . " | " . $this->toStart, PHP_EOL;
            $this->heartBeat($bot);

                       

        });
    }

    public function finishBet($winners){
        if($winners != -1){
            $this->channel->sendMessage("the bet is complete $winners");
            $this->footerMessage = "Bet Complete <paid out>";
            return;
        }
        $this->channel->sendMessage("The bet is now closed, please select a winner using !finish b or d");
        $this->footerMessage = "Finished, type !finish <'b' or 'd'>";

        /**
         * 
         * 
         * TODO winning logic
         * 
         */

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
            "title" => "\n**Will Brimz live beyond 40?**", 
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
                  ]
    
    
            ]
    
        ]);
        return $embed;
    }

}