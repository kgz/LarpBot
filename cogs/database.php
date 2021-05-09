<?php

/**
 * Database Interface class
 * 
 * Database class that can be used to manage users and thier relevent data.
 * 
 * @param Discord                   $bot                        The discord bot instance
 * @param String                    $DATABASE_IP                Database ip adress
 * @param String                    $DATABASE_PORT              Database port
 * @param String                    $DATABASE_USERNAME          Database username
 * @param String                    $DATABASE_PASSWORD          Database password
 * @property mysqli                 $conn                       The main connection to mysql database
 */

class Database
{   
    private mysqli $conn;
    
    function __construct($bot, $DATABASE_IP,$DATABASE_PORT, $DATABASE_USERNAME, $DATABASE_PASSWORD)
    {   
        mysqli_report(MYSQLI_REPORT_ERROR);

        $this->conn = new mysqli($DATABASE_IP, $DATABASE_USERNAME, $DATABASE_PASSWORD, "larpbot");
        if ($this->conn->connect_error) 
            die("Connection failed: " . $this->conn->connect_error);
        
        mysqli_query($this->conn, "SET @@SESSION.sql_mode = '';"); //relax the strictness of mysql

        echo "SQL:Successfully connected to db on $DATABASE_IP ", PHP_EOL;
        $this->setup();
        $this->pocketMoney($bot);
    }

    /**
     * Sets up the database connection
     *
     * @return void
     */
    private function setup():void{
        $query = "CREATE TABLE IF NOT EXISTS users (id varchar(25), guild_id varchar(50), points BIGINT DEFAULT 100, wins INT DEFAULT 0, loses INT DEFAULT 0);";
        $result = $this->conn->query($query);

        $exists = " SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE table_schema=DATABASE() 
                    AND table_name='users' 
                    AND index_name='points_index';";

        $result = $this->conn->query($exists);
        
        // print_r($result->fetch_assoc()["COUNT(1)"] == 2 ? 'true' : 'false');
        if($result->fetch_assoc()["COUNT(1)"] == 0){
            echo "here";
            $this->conn->query(
                "ALTER TABLE users 
                ADD UNIQUE INDEX points_index (id, guild_id);");
                
            echo "MYSQL: Created UNIQUE INDEX points_index (id, guild_id)", PHP_EOL;
        } 
    }

    /**
     * Inserts a user into the database.
     *
     * @param string $guildId
     * @param string $id
     * @return boolean
     */
    private function insertUser(string $guildId, string $id):bool{
        $query = "INSERT IGNORE INTO users(id, guild_id) VALUES ($id, $guildId)";
        $result = $this->conn->query($query);
        // echo "insertUser: $result";
        // return boolval($result);
        return 1;
    }

    /**
     * Give a user a specific amount of points.
     *
     * @param string $guildId
     * @param string $id
     * @param integer $amount
     * @return void
     */
    public function givePoints(string $guildId, string $id, int $amount){
        if($amount == 0) return;

        $this->insertUser($guildId, $id);
        $query = "UPDATE users SET points = points + $amount WHERE id = $id AND guild_id = $guildId";
        $result = $this->conn->query($query);
        
        return $result;

    }
    /**
     * Remove points from a specific user
     *
     * @param string $guildId
     * @param string $id
     * @param integer $amount
     * @return void
     */
    public function removePoints(string $guildId, string $id, int $amount){
        if($amount == 0) return;

        $this->insertUser($guildId, $id);
        $query = "UPDATE users SET points = points - $amount WHERE id = $id AND guild_id = $guildId";
        $result = $this->conn->query($query);
        
        return $result;

    }

    /**
     * Retrieve a users balance
     *
     * @param string $guildId
     * @param string $id
     * @return Int
     */
    public function balance(string $guildId, string $id):Int{

        $this->insertUser($guildId, $id);
        $query = "SELECT points FROM users WHERE id = $id AND guild_id = $guildId LIMIT 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()["points"] ?? 0;

    }


    /**
     * Increase the win/loss value for a user.
     *
     * @param string        $guildId        The guild id.
     * @param string        $id             The users id.
     * @param boolean       $win            True to increase the users win, false to increase loss count.
     * @return void
     */
    public function updateWinLoss(string $guildId, string $id, bool $win):void{
        $this->insertUser($guildId, $id);
        $winVar = $win ? "wins" : "loses";
        $query = "UPDATE users SET $winVar = $winVar + 1 WHERE id = $id AND guild_id = $guildId;";
        $result = $this->conn->query($query);
    }



    /**
     * function used to give users points every x seconds.
     *
     * @param Discord $bot
     * @return void
     */
    public function pocketMoney($bot){
        $bot->loop->addtimer(36000, function() use ($bot){

            foreach($bot->guilds as $guild){
                $guild_id = $guild->id;
                foreach($guild->members as $member){
                    $this->insertUser($guild_id, $member->id);
                    $this->givePoints($guild_id, $member->id, 10);

                }
            }

            $this->pocketMoney($bot);
        });


    }

}