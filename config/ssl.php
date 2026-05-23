<?php

return [
    "demo" => (bool) env("SSL_DEMO", false),
    "poll_seconds" => (int) env("SSL_POLL_SECONDS", 60),

    "openprovider" => [
        "base_url" => env("OPENPROVIDER_BASE_URL", "https://api.openprovider.eu"),
        "token"    => env("OPENPROVIDER_TOKEN"),
        "products" => [],
    ],
];
