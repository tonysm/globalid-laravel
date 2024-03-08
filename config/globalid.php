<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Application Name
     |--------------------------------------------------------------------------
     |
     | This name will be used as the default app name when creating global IDs. It will be
     | converted to a slug and used as the HOST portion of the GlobalID, so if you leave
     | it as "Rich Text Laravel", the GIDs will be "gid://rich-text-laravel/Model/1".
     |
     */
    'app_name' => env('GLOBALID_APP_NAME', env('APP_NAME', 'Laravel')),
];
