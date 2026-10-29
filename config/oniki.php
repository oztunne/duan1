<?php

return [
    'map_style'=>env('ONIKI_MAP_STYLE','/oniki-map-style.json'),
    'map_tiles' => env('ONIKI_MAP_TILES', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'email' => env('ONIKI_EMAIL'), 'phone' => env('ONIKI_PHONE'), 'address' => env('ONIKI_ADDRESS'),
];
