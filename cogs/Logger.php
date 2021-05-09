<?php
$__colours__ = [
    "reset"=>'\033[0m',
    "red"=> '\033[38;2;247;0;0m',
    "green"=> "\033[38;2;32;247;0m",
    "orange"=> "\033[38;2;255;165;0m"
];



function Debug(string $str){
        // print_r(debug_backtrace()[1]);
        // echo debug_backtrace()[1]['function'];
        echo "\033[38;2;32;247;0m", format(debug_backtrace()[0]), " => ", $str, "\033[0m", PHP_EOL;

}



function format($trace){
    $date = new DateTime();
    $date = $date->format('[d M Y][h:i:sa]');

    $file = explode("\\", $trace['file']);
    $file = substr(end($file), 0, -4);
    $function = $trace['function'] ?? 'null';
    $line = $trace['line'] ?? '-1';
    return $date . "[". $file .".".$function.":".$line."]";



}