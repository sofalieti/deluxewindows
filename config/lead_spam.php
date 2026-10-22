<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Lead form spam guards
    |--------------------------------------------------------------------------
    | Matched against name, email, city, message (case-insensitive).
    | Prefer whole-token / distinctive phrases so legit window leads stay clean.
    */

    'block_cyrillic' => true,

    /*
    | Block random letter-salad names/emails like "kpPkLqFYbTtRsOzMZRHWmfm".
    */
    'block_gibberish_names' => true,

    'stopwords' => [
        // Casino / gambling (EN)
        'casino',
        'casinos',
        'gambling',
        'gamble',
        'jackpot',
        'roulette',
        'blackjack',
        'slot machine',
        'slot machines',
        'online slots',
        'spin the wheel',
        'bookmaker',
        'bookmakers',
        'sportsbook',
        'sports betting',
        'betting odds',
        '1xbet',
        'mostbet',
        'melbet',
        'pin-up',
        'pinup casino',
        'stake.com',
        'bet365',
        'bovada',
        'draftkings casino',

        // Casino / gambling (RU translit + mixed spam)
        'kazino',
        'kazino online',
        'onlayn kazino',
        'stavki na sport',
        'bukmeker',

        // Casino / gambling (Cyrillic — also blocked by cyrillic rule; kept for clarity)
        'казино',
        'ставки',
        'букмекер',
        'слоты',
        'рулетка',
        'покер',
        'игровые автоматы',
        '1хбет',
        'мостбет',

        // Pharma / adult spam (high-confidence)
        'viagra',
        'cialis',
        'levitra',
        'porn',
        'xxx',

        // SEO / link spam (high-confidence phrases)
        'buy backlinks',
        'guest post',
        'guest posting',
        'link building service',
        'seo service cheap',
        'crypto airdrop',
        'binance giveaway',
    ],

];
