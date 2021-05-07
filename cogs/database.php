<?php
$servername = "localhost";
$username = "username";
$password = "password";

class Database
{   
    private $conn;

    
    function __construct($DATABASE_IP,$DATABASE_PORT, $DATABASE_USERNAME, $DATABASE_PASSWORD)
    {   
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $this->conn = new mysqli($DATABASE_IP, $DATABASE_USERNAME, $DATABASE_PASSWORD, "larpbot");
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
        echo "SQL:Successfully connected to db on $DATABASE_IP ", PHP_EOL;
        $this->setup();
    }


    private function setup():void{
        $query = "CREATE TABLE IF NOT EXISTS users (id varchar(25), guild_id varchar(50) UNIQUE, points INT DEFAULT 0, wins INT DEFAULT 0, loses INT DEFAULT 0);";
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

    private function insertUser(string $guildId, string $id):bool{
        $query = "INSERT IGNORE INTO users(id, guild_id) VALUES ($id, $guildId)";
        $result = $this->conn->query($query);
        // echo "insertUser: $result";
        // return boolval($result);
        return 1;
    }

    public function givePoints(string $guildId, string $id, int $amount){
        if($amount == 0) return;

        $this->insertUser($guildId, $id);
        $query = "UPDATE users SET points = points + $amount WHERE id = $id AND guild_id = $guildId";
        $result = $this->conn->query($query);
        echo "givePoints: $result";
        
        return $result;

    }

    public function removePoints(string $guildId, string $id, int $amount){
        if($amount == 0) return;

        $this->insertUser($guildId, $id);
        $query = "UPDATE users SET points = points - $amount WHERE id = $id AND guild_id = $guildId";
        $result = $this->conn->query($query);
        echo "givePoints: $result";
        
        return $result;

    }


    public function balance(string $guildId, string $id){

        $this->insertUser($guildId, $id);
        $query = "SELECT points FROM users WHERE id = $id AND guild_id = $guildId LIMIT 1";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()["points"] ?? 0;

    }


}