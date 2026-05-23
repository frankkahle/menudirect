<?php

return [
    "enabled" => env("DEMO_SANDBOX_ENABLED", true),
    "duration_hours" => env("DEMO_DURATION_HOURS", 2),
    "max_per_ip_per_hour" => 5,
    "template_site_slug" => "demo-bistro",
    "demo_plan_slug" => "sitefresh-pro",
    "cleanup_after_hours" => 24,
];
