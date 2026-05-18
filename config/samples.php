<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sample Sites Configuration
    |--------------------------------------------------------------------------
    |
    | Each entry creates a sample site at sos-tech.ca/s/{slug}
    | Add a new business by copying an entry and customizing.
    |
    */

    'sites' => [

        // Berc's Cookhouse - Now served from Portal database
        // See: php artisan restaurant:seed-bercs

        // Example: Restaurant
        'joes-pizza' => [
            'name' => "Joe's Pizza",
            'tagline' => 'Authentic Italian Pizza Since 1985',
            'template' => 'restaurant',
            'phone' => '(506) 555-1234',
            'email' => 'info@joespizza.ca',
            'address' => '123 Main Street, Moncton, NB',
            'hours' => [
                'Mon-Thu' => '11am - 10pm',
                'Fri-Sat' => '11am - 11pm',
                'Sunday' => '12pm - 9pm',
            ],
            'colors' => [
                'primary' => '#dc2626',    // red-600
                'secondary' => '#16a34a',   // green-600
                'accent' => '#fbbf24',      // amber-400
            ],
            'features' => [
                'Dine-In & Takeout',
                'Online Ordering',
                'Catering Available',
                'Family Recipes',
            ],
            'menu_highlights' => [
                ['name' => 'Margherita Pizza', 'price' => '$16.99', 'desc' => 'Fresh mozzarella, tomato, basil'],
                ['name' => 'Meat Lovers', 'price' => '$19.99', 'desc' => 'Pepperoni, sausage, bacon, ham'],
                ['name' => 'House Salad', 'price' => '$8.99', 'desc' => 'Mixed greens, tomato, cucumber'],
            ],
            'cta_text' => 'Order Online',
            'cta_url' => '#order',
        ],

        // Example: Trades/Contractor
        'atlantic-plumbing' => [
            'name' => 'Atlantic Plumbing',
            'tagline' => 'Your Trusted Local Plumbers',
            'template' => 'trades',
            'phone' => '(506) 555-5678',
            'email' => 'service@atlanticplumbing.ca',
            'address' => 'Serving Greater Moncton Area',
            'colors' => [
                'primary' => '#1d4ed8',    // blue-700
                'secondary' => '#0891b2',   // cyan-600
                'accent' => '#f59e0b',      // amber-500
            ],
            'services' => [
                ['name' => 'Emergency Repairs', 'icon' => 'wrench', 'desc' => '24/7 emergency service'],
                ['name' => 'Drain Cleaning', 'icon' => 'droplet', 'desc' => 'Professional drain solutions'],
                ['name' => 'Water Heaters', 'icon' => 'flame', 'desc' => 'Installation & repair'],
                ['name' => 'Renovations', 'icon' => 'home', 'desc' => 'Bathroom & kitchen plumbing'],
            ],
            'features' => [
                'Licensed & Insured',
                'Free Estimates',
                '24/7 Emergency Service',
                'Satisfaction Guaranteed',
            ],
            'cta_text' => 'Get a Free Quote',
            'cta_url' => '#contact',
        ],

        // Example: Retail
        'harbourview-gifts' => [
            'name' => 'Harbourview Gifts',
            'tagline' => 'Unique Maritime Treasures',
            'template' => 'retail',
            'phone' => '(506) 555-9012',
            'email' => 'hello@harbourviewgifts.ca',
            'address' => '456 Waterfront Dr, Saint John, NB',
            'hours' => [
                'Mon-Sat' => '10am - 6pm',
                'Sunday' => '12pm - 5pm',
            ],
            'colors' => [
                'primary' => '#0d9488',    // teal-600
                'secondary' => '#7c3aed',   // violet-600
                'accent' => '#f472b6',      // pink-400
            ],
            'categories' => [
                ['name' => 'Local Artisan Crafts', 'desc' => 'Handmade by local artists'],
                ['name' => 'Maritime Souvenirs', 'desc' => 'Take home a piece of the coast'],
                ['name' => 'Home Decor', 'desc' => 'Coastal-inspired pieces'],
            ],
            'products' => [
                ['name' => 'Lighthouse Candle', 'price' => '$24.99'],
                ['name' => 'Lobster Tea Towel', 'price' => '$12.99'],
                ['name' => 'Driftwood Frame', 'price' => '$34.99'],
                ['name' => 'Sea Glass Jewelry', 'price' => '$45.00'],
            ],
            'about' => 'Harbourview Gifts has been bringing the best of Maritime craftsmanship to visitors and locals alike since 2005. Every item in our store is carefully selected to represent the spirit and beauty of Atlantic Canada.',
            'features' => [
                'Locally Made Products',
                'Gift Wrapping',
                'Ship Anywhere in Canada',
                'Corporate Gifting',
            ],
            'cta_text' => 'Shop Now',
            'cta_url' => '#products',
        ],

        // Example: Professional Services
        'clearview-accounting' => [
            'name' => 'Clearview Accounting',
            'tagline' => 'Financial Clarity for Your Business',
            'template' => 'professional',
            'phone' => '(506) 555-3456',
            'email' => 'info@clearviewaccounting.ca',
            'address' => '789 Business Park, Fredericton, NB',
            'colors' => [
                'primary' => '#1e40af',    // blue-800
                'secondary' => '#4f46e5',   // indigo-600
                'accent' => '#10b981',      // emerald-500
            ],
            'services' => [
                ['name' => 'Tax Preparation', 'desc' => 'Personal and business tax returns'],
                ['name' => 'Bookkeeping', 'desc' => 'Monthly bookkeeping services'],
                ['name' => 'Payroll', 'desc' => 'Complete payroll management'],
                ['name' => 'Business Advisory', 'desc' => 'Strategic financial planning'],
            ],
            'features' => [
                'CPA Certified',
                '20+ Years Experience',
                'Free Consultation',
                'Cloud Accounting',
            ],
            'credentials' => [
                'CPA New Brunswick',
                'QuickBooks Certified ProAdvisor',
            ],
            'cta_text' => 'Book Consultation',
            'cta_url' => '#contact',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'template' => 'generic',
        'colors' => [
            'primary' => '#2563eb',
            'secondary' => '#7c3aed',
            'accent' => '#f59e0b',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sales Banner
    |--------------------------------------------------------------------------
    | Shown on all sample sites to convert prospects
    */

    'sales_banner' => [
        'enabled' => true,
        'message' => 'Like what you see? This could be YOUR website!',
        'cta_text' => 'Get Started for $15/month',
        'cta_url' => 'https://portal.sos-tech.ca/register',
        'preview_url' => 'https://menudirect.ca/#try-demo',
        'phone' => '(506) 910-5547',
    ],
];
