<?php


/**
 * convert in into short string.

 * @param Int $n
 * @return String
 * 
 * @example
 *  echo number_format_short(15200)
 *  output: 15.2k
 *
 */
function number_format_short($n) {
    switch(true){
        case $n < 1000:
            return $n;
        case $n < 1000000:
            return round($n / 1000, 1, PHP_ROUND_HALF_DOWN) . "k";
        case $n < 1000000000:
            return round($n / 1000000, 1, PHP_ROUND_HALF_DOWN) . "M";
        case $n < 1000000000000:
            return round($n / 1000000000, 1, PHP_ROUND_HALF_DOWN) . "B";
        case $n > 1000000000000:
            return round($n / 1000000000000, 1, PHP_ROUND_HALF_DOWN) . "T";
        default:
            return "You broke me.";      
    }
}



/**
 * Used to check whether a Discord Member has on of x permissions.
 *
 * @param Message           $ctx            The Message context.                
 * @param String            $permissions    String of permissions seperated by a ','.
 * 
 * @see https://github.com/discord-php/DiscordPHP/blob/69460e466da8bf13373f67a0514baa33348add65/src/Discord/Parts/Permissions/RolePermission.php#L12
 * @return boolean
 */
function isAdmin($ctx, $permissions){
    $member = $ctx->author->roles;
    if(!$member) return false;
    $args = preg_split('/,+/', $permissions, -1, PREG_SPLIT_NO_EMPTY);
    foreach($member as $m){
        foreach($args as $arg){
            if($m->permissions[$arg]) return true;
        }
    };
    return false;
}


function rand_quote($key){
    $quotes=[


        "doubt"=>[
            "We learn from failure, not from success!",
            "We’ll never survive!",
            "Doubt everything. ",
            "Doubt is an uncomfortable condition, but certainty is a ridiculous one.",
            "If you would be a real seeker after truth, it is necessary that at least once in your life you doubt, as far as possible, all things."
            ,"Doubt isn't the opposite of faith; it is an element of faith."
            ,"Doubt … is an illness that comes from knowledge and leads to madness."
            ,"For so many years I lived in constant terror of myself. Doubt had married my fear and moved into my mind."
            ,"I have always felt that doubt was the beginning of wisdom."
            ,"Doubts are the ants in the pants of faith. They keep it awake and moving."
            ,"Life is doubt, And faith without doubt is nothing but death."
            ,"When in doubt, win apparantly...."
            ,"Doubt your doubts before you doubt your beliefs."
            ,"Doubt grows with knowledge."
        ,"Doubt is the father of invention."
            ,"To believe with certainty we must begin with doubting."
            ,"It is by doubting that we come to investigate, and by investigating that we recognize the truth."
            ,"Doubt is the vestibule through which all must pass before they can enter into the temple of wisdom."












        ],
        "believe"=>[

            "Every man should believe in something. If not.. he would doubt everything, even himself.",
            "If you don’t give up on something you truly believe in, you will find a way.",
            "My father gave me the greatest gift anyone could give another person, he believed in me.",
            "We all want to believe in impossible things, I suppose, to persuade ourselves that miracles can happen.",
            "You cannot be truly humble, unless you truly believe.",
            "Sometimes the hardest things to believe are the only things worth believing at all.",
            "As for believing things, I can believe anything, provided that it is quite incredible.",
            "Wish it, believe it, and it will be so.",
            "If you believe it, they’ll believe it."

        ]
    ];



    return $quotes[$key][array_rand($quotes[$key], 1)] ?? null;
}