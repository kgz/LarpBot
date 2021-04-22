<?php
/**
 * The Bet class
 * 
 * @property string $bet 
 */
class Bet{
    public string $bet;
    public array $believers = [];
    public array $doubters = [];
    public string $author;
    public Float $winPayout = 1;
    public int $timeLeft = 90;
    public int $toStart = 90;
    public bool $started = false;
    public $channel;
    private $_loopInterval = 5;


    function __construct($ctx, $bet)
    {
        $this->channel = $ctx->channel;
        $this->bet = $bet;
        $this->author = $ctx->author->id;
    }

    public function start($bot){
      $this->heartBeat($bot);
    }


    /**
     * heartBeat function
     *
     * @param $bot
     * @return void
     */
    private function heartBeat($bot){
        $bot->loop->addtimer(1, function() use ($bot){

            if(!$this->started){
                $this->toStart -= 1;
                if($this->toStart < 1){
                    $this->started = true;
                    $this->channel->sendMessage("The bet: " . $this->bet . " is now open for bets!");
                }
            }
            else $this->timeLeft -=1;

            if(in_array($this->timeLeft, [60, 30, 10]))
                $this->channel->sendMessage("{$this->timeLeft}s Left, get your bets in!\n\n{$this->bet}\n\nType:\n\t!believe\n\t!doubt");

            if($this->timeLeft < 1) 
            {
                $this->finishBet(-1);
                return;
            }
            echo $this->timeLeft . " | " . $this->toStart, PHP_EOL;
            $this->heartBeat($bot);
        });
    }

    public function finishBet($winners){

        if($winners != -1)  
            $this->channel->sendMessage("the bet is comeplete $winners");
        else
            $this->channel->sendMessage("The bet is now closed, please select a winner using !finish b or d");

        


    }

}