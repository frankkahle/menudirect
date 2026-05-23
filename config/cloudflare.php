<?php

return [
    "enabled" => env("CF_FEATURE_ENABLED", false),
    "base_url" => env("CF_BASE_URL", "https://api.cloudflare.com/client/v4/"),
    "api_token" => env("CF_API_TOKEN"),
    "read_token" => env("CF_API_READ_TOKEN"),
    "write_token" => env("CF_API_WRITE_TOKEN"),
    "read_label" => env("CF_READ_LABEL"),
    "write_label" => env("CF_WRITE_LABEL"),
    "per_page" => (int) env("CF_DEFAULT_PER_PAGE", 100),
    "timeout" => (int) env("CF_HTTP_TIMEOUT", 20),
    "retry" => [
        "max_attempts" => (int) env("CF_RETRY_MAX_ATTEMPTS", 5),
        "base_delay_ms" => (int) env("CF_RETRY_BASE_DELAY_MS", 500),
        "max_delay_ms" => (int) env("CF_RETRY_MAX_DELAY_MS", 5000),
    ],
    "default_cname_target" => env("CF_DEFAULT_CNAME_TARGET", "frank.myddns.me"),
];
