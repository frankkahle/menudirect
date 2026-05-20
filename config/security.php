<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content-Security-Policy enforcement
    |--------------------------------------------------------------------------
    |
    | When true, the SecurityHeaders middleware sends an enforcing
    | "Content-Security-Policy" header. When false (the default), it sends
    | "Content-Security-Policy-Report-Only" instead, so a misconfigured policy
    | cannot break customer-facing pages — notably the Mapbox maps on the
    | restaurant templates, which are CSP-sensitive.
    |
    | Roll-out: deploy with this false, open a few restaurant pages + the
    | portal login in a browser, confirm the console shows no CSP violations,
    | then set CSP_ENFORCE=true in .env and re-run `php artisan optimize`.
    |
    */

    'csp_enforce' => env('CSP_ENFORCE', false),

];
