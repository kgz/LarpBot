<?php

function number_format_short( $n, $precision = 1 ) {
    // if ($n < 900) {
    //     // 0 - 900
    //     $n_format = number_format($n, $precision);
    //     $suffix = '';
    // } else if ($n < 900000) {
    //     // 0.9k-850k
    //     $n_format = number_format($n / 1000, $precision);
    //     $suffix = 'K';
    // } else if ($n < 900000000) {
    //     // 0.9m-850m
    //     $n_format = number_format($n / 1000000, $precision);
    //     $suffix = 'M';
    // } else if ($n < 900000000000) {
    //     // 0.9b-850b
    //     $n_format = number_format($n / 1000000000, $precision);
    //     $suffix = 'B';
    // } else {
    //     // 0.9t+
    //     $n_format = number_format($n / 1000000000000, $precision);
    //     $suffix = 'T';
    // }


    switch(true){

        case $n < 1000:
            return $n;

        case $n < 1000000:
            return round($n / 1000, 2, PHP_ROUND_HALF_DOWN) . "k";

        case $n < 1000000000:
            return round($n / 1000000, 2, PHP_ROUND_HALF_DOWN) . "M";
        case $n < 1000000000000:
            return round($n / 1000000000, 2, PHP_ROUND_HALF_DOWN) . "B";
        case $n > 1000000000000:
            return round($n / 1000000000000, 2, PHP_ROUND_HALF_DOWN) . "T";
        default:
            return "You broke me.";

        
    }

    // return $n_format;
}




function isAdmin($ctx, $bot, $permissions){
    // $ctx->channel->guild->members->freshen();
    // $member = $ctx->channel->guild->members;//->get('id', $ctx->user_id);
    $member = $ctx->author->roles;
    if(!$member) return false;

    $args = preg_split('/,+/', $permissions, -1, PREG_SPLIT_NO_EMPTY);

    foreach($member as $m){
        foreach($args as $arg){
            if($m->permissions[$arg]) return true;
        }
    };
//ctx->member->getPermissions($ctx->channel)


}