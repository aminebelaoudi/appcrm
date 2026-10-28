<?php

return [
    'contact_tag' => env('OPEN_HOUSE_TAG', 'src:visite-libre'),
    'allowed_location_ids' => array_filter(array_map('trim', explode(',', env('OPEN_HOUSE_ALLOWED_LOCATION_IDS', 'FqqkdWQ0F0QPYOpLYXnz')))),
];
