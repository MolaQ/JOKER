<?php

/*
|--------------------------------------------------------------------------
| Zdjęcia tematyczne (darmowe, licencja Unsplash)
|--------------------------------------------------------------------------
|
| Zdjęcia zastępcze, używane tam, gdzie klub nie wgrał jeszcze własnego
| zdjęcia (drużyna, artykuł, slajd). Pochodzą z Unsplash
| (https://unsplash.com/license) - darmowe także do użytku komercyjnego.
| Wystarczy wgrać własne zdjęcie w panelu admina, a zastępcze zniknie.
|
*/

return [

    'photos' => [
        'hero-team' => [
            'url' => 'https://images.unsplash.com/photo-1547347298-4074fc3086f0',
            'author' => 'Vince Fleming',
            'page' => 'https://unsplash.com/photos/women-playing-volleyball-inside-court-aZVpxRydiJk',
        ],
        'ball-court' => [
            'url' => 'https://images.unsplash.com/photo-1666901356149-93f2eb3ba5a2',
            'author' => 'Çağlar Oskay',
            'page' => 'https://unsplash.com/photos/volleyball-on-colorful-indoor-court-pYHFDv6vI-o',
        ],
        'ball-floor' => [
            'url' => 'https://images.unsplash.com/photo-1731939762362-90e98b56b3d9',
            'author' => 'Francesco Solomita',
            'page' => 'https://unsplash.com/photos/a-yellow-and-blue-ball-sitting-on-top-of-a-hard-wood-floor-JFHj01fPNAY',
        ],
        'men-game-1' => [
            'url' => 'https://images.unsplash.com/photo-1728971124745-423b12df446d',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-men-playing-a-game-of-volleyball-Iuhme32mv9I',
        ],
        'men-game-2' => [
            'url' => 'https://images.unsplash.com/photo-1728971121202-04896f2a35a4',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-people-playing-a-game-of-volleyball-FcuE1a_pOA0',
        ],
        'young-men-1' => [
            'url' => 'https://images.unsplash.com/photo-1728971124746-607ba6d3fff0',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-young-men-playing-a-game-of-volleyball-cDKMfHJ5JNw',
        ],
        'young-men-2' => [
            'url' => 'https://images.unsplash.com/photo-1728968920002-4225a376499a',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-young-men-playing-a-game-of-volleyball-BgFVRevbvrg',
        ],
        'young-men-3' => [
            'url' => 'https://images.unsplash.com/photo-1728968915389-ae489da53c5b',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-young-men-playing-a-game-of-volleyball-5B843rAACZQ',
        ],
        'young-men-4' => [
            'url' => 'https://images.unsplash.com/photo-1728968916776-7aab52f5ea99',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-young-men-playing-a-game-of-volleyball-a1Pqg4PhD3A',
        ],
        'group-game' => [
            'url' => 'https://images.unsplash.com/photo-1728971121170-2c8bae90d6fb',
            'author' => 'HorseRat',
            'page' => 'https://unsplash.com/photos/a-group-of-people-playing-a-game-of-volleyball-Q1zyaUy3Bjs',
        ],
        'team-play' => [
            'url' => 'https://images.unsplash.com/photo-1673058577973-68b6b6d53ccd',
            'author' => 'Micah McKerlich',
            'page' => 'https://unsplash.com/photos/a-group-of-people-playing-a-game-of-volleyball-WNMfEYSlyUY',
        ],
        'spike' => [
            'url' => 'https://images.unsplash.com/photo-1686753767715-37cb0c34212c',
            'author' => 'Junior REIS',
            'page' => 'https://unsplash.com/photos/a-woman-reaching-up-to-hit-a-volleyball-0xFEL_vDU5I',
        ],
        'player-woman' => [
            'url' => 'https://images.unsplash.com/photo-1686753767461-35673e3dfa77',
            'author' => 'Junior REIS',
            'page' => 'https://unsplash.com/photos/a-woman-in-black-shorts-and-a-white-top-playing-volleyball-tTw4S3aGVak',
        ],
        'jersey-13' => [
            'url' => 'https://images.unsplash.com/photo-1553005746-9245ba190489',
            'author' => 'Erik Mclean',
            'page' => 'https://unsplash.com/photos/man-wearing-black-13-volleyball-jersey--PkEytgjLm0',
        ],
        'women-play' => [
            'url' => 'https://images.unsplash.com/photo-1567781830902-685fb3401f1d',
            'author' => 'Raja Tilkian',
            'page' => 'https://unsplash.com/photos/women-playing-volleyball-tnUcI53e7zA',
        ],
        'player-yellow' => [
            'url' => 'https://images.unsplash.com/photo-1567880325673-ccc01edca61c',
            'author' => 'Ildar Garifullin',
            'page' => 'https://unsplash.com/photos/man-wearing-yellow-and-black-long-sleeved-shirt-playing-volleyball-UPjZWkyZ6aw',
        ],
        'court-aerial' => [
            'url' => 'https://images.unsplash.com/photo-1479859546309-cd77fa21c8f6',
            'author' => 'Michele Feola',
            'page' => 'https://unsplash.com/photos/white-and-red-volleyball-court-aerial-photo--t40mnV3vAU',
        ],
    ],

    // Kolejność zdjęć zastępczych dla drużyn (wg kolejności wyświetlania).
    'team_fallbacks' => ['men-game-1', 'young-men-1', 'young-men-2', 'young-men-3', 'young-men-4', 'group-game', 'team-play', 'men-game-2'],

    // Zdjęcia zastępcze dla artykułów bez własnej grafiki.
    'article_fallbacks' => ['spike', 'ball-court', 'women-play', 'player-yellow', 'jersey-13', 'court-aerial', 'player-woman', 'ball-floor', 'team-play'],

];
