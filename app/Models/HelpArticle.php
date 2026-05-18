<?php

namespace App\Models;

use App\Models\RestaurantModel;

class HelpArticle extends RestaurantModel
{

    protected $fillable = [
        'slug',
        'title',
        'category',
        'summary',
        'body',
        'tags',
        'sort_order',
        'is_published',
        'last_updated_by',
        'view_count',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
    ];

    public const CATEGORIES = [
        'getting-started' => 'Getting Started',
        'site-design' => 'Site Design',
        'menu' => 'Menu Management',
        'hours-location' => 'Hours & Location',
        'online-ordering' => 'Online Ordering',
        'online-payments' => 'Online Payments',
        'orders' => 'Order Management',
        'reservations' => 'Reservations',
        'catering' => 'Catering',
        'staff' => 'Staff & Permissions',
        'announcements' => 'Announcements',
        'delivery' => 'Delivery Zones',
        'domain-seo' => 'Domains & SEO',
        'multi-location' => 'Multi-Location',
        'billing' => 'Billing & Plans',
        'troubleshooting' => 'Troubleshooting',
    ];

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('-', ' ', $this->category));
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('category')->orderBy('sort_order')->orderBy('title');
    }

    public function recordView(): void
    {
        $this->increment('view_count');
    }
}
