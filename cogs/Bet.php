<?php

use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Author;
use Discord\Parts\Channel\Channel;
use Larpbot\cogs\Utils;

/**
 * Creates and manages a 'Bet' instance.
 * 
 * @param Discord::Message          $ctx            The message context
 * @param string                    $bet            The bet the users wants to make.
 * @param Discord::Discord          $bot            The discord Bot
 * 
 * @property string                 $bet            The bet the user has made.
 * @property array                  $believers      List of current believers.
 * @property array                  $doubters       List of current doubters
 * @property Author                 $author         The owner of the bet.
 * @property string                 $authorId       The id of the Author.
 * @property Array                  $winPayout      Array containing the win payout percents for winiing and losing.
 * @property int                    $timeLeft       The time left for the current bet.
 * @property int                    $toStart        Time left until the start of the bet.
 * @property bool                   $started        Has the bet started.
 * @property Channel                $channel        The channel the origional message was sent in.
 * @property String                 $guildId        The guild id of the bet.
 * @property Discord                $bot            The discord bot.
 * @property Message                $embedMessage   The embed message.
 * @property Boolean                $waitingToDieFlag   Flag to tell it to die.
 *  
 */

class Bet{
    public string $bet;
    public array $believers = [];
    public array $doubters = [];
    public Author $author;
    public string $authorId;
    public Array $winPayout = ["believe"=>1, "doubt"=>1];
    public int $timeLeft = 200;
    public int $toStart = 60;
    public bool $started = false;
    public Channel $channel;
    public $guildId;
    public $bot;
    public $embedMessage;
    public $embed;

    public $waitingToDieFlag = 0;

    private $description = "Bet will auto start in 00";
    private $footerMessage = "";
    private $embedColor = 15525388;
    private $_loopInterval = 5;

    function __construct($ctx, $bet, $bot)
    {
        $this->channel = $ctx->channel;
        $this->guildId = $ctx->channel->guild_id;
        $this->bet = $bet;
        $this->author = new Author($bot, [
            "name"=>$ctx->author->username, 
            "icon_url"=> $ctx->author->avatar ?? $ctx->author->user->avatar,
        ]);
        $this->authorId = $ctx->author->id;
        $this->bot = $bot;
    }

    public function start($bot){
      $this->heartBeat($bot);
    }

    /**
     * Main loop of the class.
     *
     * @param Discord\Discord $bot
     * @return void
     */
    private function heartBeat($bot){
        $bot->loop->addtimer(1, function() use ($bot){
            if($this->waitingToDieFlag == 1) return;

            $newEmbed = $this->formEmbed($bot);
            $this->updateMessage($newEmbed);

            if(!$this->started){
                $this->toStart -= 1;
                $this->embedColor = 15525388;

                if($this->toStart < 1){

                    $this->started = true;
                    // $this->channel->sendMessage("The bet: " . $this->bet . " is now open for bets!");
                    $embed = new Embed($bot, [
                        "title" => "\n**{$this->bet}**", 
                        "description" => "do you belive? or do you doubt!",
                        "footer"=> [
                            "text"=> "type !believe <points> or \n!doubt <points> to place you bets!"
                        ]
                    ]);
                    $this->channel->sendEmbed($embed);


                }
            }
            else {
                $this->timeLeft -=1;
                $this->embedColor = 1441536;

            }

            switch(true){
                case !$this->started:
                    if($this->toStart % 5 == 0)
                        $this->description = "Bet will auto start in {$this->toStart}";
                    $this->footerMessage = "{$this->timeLeft}s | !start | !set time <seconds> | !delete";
                    break;
                    
                case $this->timeLeft > 60:
                    $t = $this->timeLeft;
                    $leftOver = $t%60;
                    $minsleft = (($t - $leftOver) / 60) +1;
                    $this->description = "";
                    $this->footerMessage = "{$minsleft}m Left.";
                    break;

                case $this->timeLeft > 30 && $this->timeLeft < 60:
                    $this->description = "";
                    $this->footerMessage = "less then 60s Left!";
                    break;

                case $this->timeLeft < 30:
                    $this->description = "";
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


    /**
     * Undocumented function
     *
     * @param Array             $winner                 List of users that won.
     * @param float             $winnerPayout           payout percent eg. 0.7 = payout 70% of the users bet. 
     * @param Array             $loser                  List of users that lost.
     * @return void
     */
    private function payoutLogic($winner, $winnerPayout, $loser){
        $totalWon = 0;
        $totalLost = 0;
        foreach(array_keys($winner) as $user){
            $won = $winner[$user] + 
            ($winner[$user] * $winnerPayout);

            $this->bot->db->givePoints(
                $this->channel->guild_id, 
                $user, 
                $won
            );
            $this->bot->db->updateWinLoss($this->channel->guild_id, $user, true);
            $totalWon = $totalWon + $won;
        } 
        foreach(array_keys($loser) as $user){ 
            $totalLost = $totalLost + $loser[$user];
            $this->bot->db->updateWinLoss($this->channel->guild_id, $user, false);

        } 
        return $totalWon;
    }


    /**
     * Logic used to finish the bet.
     *
     * @param int $winners          Can be one of -1, 0, 1, 2
     *  -1: bet has closed but no winner has been selected yet.
     *   0: doubters won.
     *   1: believers won.
     *   2: bet resulted in a draw.
     * 
     * @return void
     */
    public function finishBet($winners){

        if($winners == -1 and !$this->waitingToDieFlag == 1){
            $this->channel->sendMessage("The bet is now closed, type `!stop <'b', 'd' or 'draw'>` to select a winner");
            $this->footerMessage = "Finished, type !stop <'b', 'd' or 'draw'>";
            $this->embedColor = 15525388;
            return ;
        }
  
        $this->waitingToDieFlag = 1;

        switch(true){
            case $winners == 2:
                $this->payoutLogic($this->believers, 0, []);
                $this->payoutLogic($this->doubters, 0, []);
                $this->footerMessage = "Bet resulted in a draw";
                $this->description = "";
                break;

            case $winners == 1:
                $points = $this->payoutLogic($this->believers, $this->winPayout['believe'], $this->doubters);
                $this->footerMessage = "Bet resulted in believers winning, paid out $points points.";
                $this->description = rand_quote('believe');
                break;

            case $winners == 0:
                $points = $this->payoutLogic($this->doubters, $this->winPayout['doubt'], $this->believers);
                $points = number_format_short($points);
                $this->footerMessage = "Bet resulted in doubters winning, paid out $points points.";
                $this->description = rand_quote("doubt");

                break;
        }
        $this->embedColor = 16711684;
        $this->embedMessage->delete();
        $this->embedMessage = null;
        $newEmbed = $this->formEmbed($this->bot);
        $this->updateMessage($newEmbed);
        unset($this->bot->bets[$this->guildId]);
        return;
    }
    /**
     * Update the embed message if it has changed.
     *
     * @param Embed $embed
     * @return void
     */
    public function updateMessage(Embed $embed){

        $bot = $this->bot;
        if(!$this->embedMessage){
            $this->channel->sendEmbed($embed)->done(function (Message $message) {
                $this->embedMessage = $message;
            });
            return;
        }
        if($this->embed == $embed)return;
        $this->embedMessage->channel->editMessage(
            $this->embedMessage, "", 0, $embed);
        $this->embed = $embed;
    }


    /**
     * function used to form the embed.
     *
     * @param Discord\Discord $bot
     * @return Discord\Parts\Embed\Embed
     */
    private function formEmbed($bot){
        $b = count($this->believers);
        $d = count($this->doubters);
        $zws =  json_decode('"\u200b"'); //zero width space

        $embed = new Embed($bot, [
            "title" => "\n**{$this->bet}**", 
            "description" => $this->description,
            "author"=>$this->author,
            "color"=>$this->embedColor,
            "footer"=> [
                "icon_url"=> "https://66.media.tumblr.com/tumblr_macxij6ZBf1rfjowdo1_500.gif",
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
                    "value"=> "\t{$d}\t\n$zws",
                    "inline"=> true
                  ],
                  [
                    "name"=> $zws,
                    "value"=> $zws,
                    "inline"=> true
                ],
                  [
                    "name"=> "Payout:",
                    "value"=> $this->winPayout['believe'] * 100 . "%\n$zws",
                    "inline"=> true
                ],
                [
                    "name"=> json_decode('"\u200b"'),
                    "value"=> $this->winPayout['doubt'] * 100 . "%\n$zws",
                    "inline"=> true
                ],
                [
                    "name"=> $zws,
                    "value"=> $zws,
                    "inline"=> true
                ],
            
                // [
                //     "name"=> $zws,
                //     "value"=> $zws,
                //     "inline"=> true
                // ],
    
    
            ]
    
        ]);
        return $embed;
    }

}