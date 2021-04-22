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
        $query = "CREATE TABLE IF NOT EXISTS users (id TEXT, points INT DEFAULT 0, wins INT DEFAULT 0, loses INT DEFAULT 0);";
        $result = $this->conn->query($query);
        if($result)
            echo "SQL: Created User Table.", PHP_EOL;
   
    }

}