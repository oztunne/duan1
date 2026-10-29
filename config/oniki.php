<?php

return [
    'address_search_url' => env('ONIKI_ADDRESS_SEARCH_URL', 'https://msearch.gsi.go.jp/address-search/AddressSearch'),
    'map_style'=>env('ONIKI_MAP_STYLE','/oniki-map-style.json?v=clear-1'),
    'map_tiles' => env('ONIKI_MAP_TILES', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'email' => env('ONIKI_EMAIL'), 'phone' => env('ONIKI_PHONE'), 'address' => env('ONIKI_ADDRESS'),
];
