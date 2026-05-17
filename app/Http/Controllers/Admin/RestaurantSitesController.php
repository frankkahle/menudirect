<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodOrder;
use App\Models\RestaurantSite;
use App\Models\RestaurantCustomDomain;
use App\Services\Cloudflare\CloudflareClient;
use App\Models\Client;
use App\Models\RestaurantPlan;
use App\Models\Service;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Mail\RestaurantWelcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RestaurantSitesController extends Controller
{
    /**
     * Show create demo site form
     */
    public function create()
    {
        return view('admin.restaurant.create');
    }

    /**
     * Store a new demo site
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:menudirect.restaurant_sites,slug',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'tagline' => 'nullable|string|max:255',
            'cuisine_type' => 'nullable|string|max:100',
            'price_range' => 'nullable|in:$,$$,$$$,$$$$',
            'color_primary' => 'nullable|string|max:7',
            'color_secondary' => 'nullable|string|max:7',
            'color_accent' => 'nullable|string|max:7',
        ]);

        // Get or create the samples client for demo sites
        $samplesClient = Client::firstOrCreate(
            ['email' => 'samples@sos-tech.ca'],
            [
                'name' => 'SOS Tech Samples',
                'first_name' => 'SOS Tech',
                'last_name' => 'Samples',
                'password' => bcrypt(Str::random(32)),
                'company_name' => 'SOS Tech Sample Sites',
            ]
        );

        $colors = RestaurantSite::DEFAULT_COLORS;
        if (!empty($validated['color_primary'])) {
            $colors['primary'] = $validated['color_primary'];
        }
        if (!empty($validated['color_secondary'])) {
            $colors['secondary'] = $validated['color_secondary'];
        }
        if (!empty($validated['color_accent'])) {
            $colors['accent'] = $validated['color_accent'];
        }

        $site = RestaurantSite::create([
            'client_id' => $samplesClient->id,
            'slug' => $validated['slug'],
            'business_name' => $validated['business_name'],
            'tagline' => $validated['tagline'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'cuisine_type' => $validated['cuisine_type'] ?? null,
            'price_range' => $validated['price_range'] ?? null,
            'status' => RestaurantSite::STATUS_DEMO,
            'plan' => RestaurantSite::PLAN_BASIC,
            'colors' => $colors,
            'hours' => RestaurantSite::DEFAULT_HOURS,
        ]);

        return redirect()->route('admin.restaurant.show', $site)
            ->with('status', "Created demo site: {$site->business_name}");
    }

    public function index(Request $request)
    {
        $query = RestaurantSite::with(['client', 'restaurantPlan', 'service']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('custom_domain', 'like', "%{$search}%");
            });
        }

        $sites = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => RestaurantSite::count(),
            'active' => RestaurantSite::where('status', 'active')->count(),
            'demo' => RestaurantSite::where('status', 'demo')->count(),
            'suspended' => RestaurantSite::where('status', 'suspended')->count(),
        ];

        // Platform fee analytics — revenue collected from MenuDirect online payments.
        // Only 'paid' orders count; platform_fee_cents is set at webhook confirmation time.
        $paidOrdersBase = FoodOrder::where('payment_status', 'paid');

        $payments = [
            'connected_sites' => RestaurantSite::where('online_payments_enabled', true)
                ->where('stripe_charges_enabled', true)
                ->count(),
            'onboarding_sites' => RestaurantSite::whereNotNull('stripe_account_id')
                ->where('stripe_charges_enabled', false)
                ->count(),
            'paid_orders_all_time' => (clone $paidOrdersBase)->count(),
            'platform_fees_all_time' => (int) (clone $paidOrdersBase)->sum('platform_fee_cents'),
            'gross_volume_all_time' => (float) (clone $paidOrdersBase)->sum('total'),
            'paid_orders_mtd' => (clone $paidOrdersBase)
                ->where('paid_at', '>=', now()->startOfMonth())->count(),
            'platform_fees_mtd' => (int) (clone $paidOrdersBase)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('platform_fee_cents'),
            'gross_volume_mtd' => (float) (clone $paidOrdersBase)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
        ];

        // Top-earning restaurants by platform fees (last 90 days)
        $topEarners = FoodOrder::select(
                'restaurant_site_id',
                DB::raw('SUM(platform_fee_cents) as total_fees_cents'),
                DB::raw('SUM(total) as gross_volume'),
                DB::raw('COUNT(*) as order_count')
            )
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', now()->subDays(90))
            ->groupBy('restaurant_site_id')
            ->orderByDesc('total_fees_cents')
            ->limit(5)
            ->with('restaurantSite:id,business_name,slug')
            ->get();

        return view('admin.restaurant.index', compact('sites', 'stats', 'payments', 'topEarners'));
    }

    public function show(RestaurantSite $site)
    {
        $site->load(['client', 'restaurantPlan', 'service', 'categories.items', 'announcements']);

        // Platform fee analytics for this specific restaurant
        $paidOrders = FoodOrder::where('restaurant_site_id', $site->id)
            ->where('payment_status', 'paid');

        $sitePayments = [
            'paid_orders' => (clone $paidOrders)->count(),
            'platform_fees_cents' => (int) (clone $paidOrders)->sum('platform_fee_cents'),
            'gross_volume' => (float) (clone $paidOrders)->sum('total'),
            'paid_orders_mtd' => (clone $paidOrders)
                ->where('paid_at', '>=', now()->startOfMonth())->count(),
            'platform_fees_mtd_cents' => (int) (clone $paidOrders)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('platform_fee_cents'),
            'last_paid_at' => (clone $paidOrders)->max('paid_at'),
        ];

        return view('admin.restaurant.show', compact('site', 'sitePayments'));
    }

    public function edit(RestaurantSite $site)
    {
        $site->load(['client', 'restaurantPlan', 'service', 'customDomains']);
        $clients = Client::orderBy('name')->get();
        $plans = RestaurantPlan::active()->ordered()->get();

        return view('admin.restaurant.edit', compact('site', 'clients', 'plans'));
    }

    public function update(Request $request, RestaurantSite $site)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'restaurant_plan_id' => 'nullable|exists:menudirect.restaurant_plans,id',
            'business_name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|alpha_dash|unique:menudirect.restaurant_sites,slug,' . $site->id,
            'custom_domain' => 'nullable|string|max:255',
            'status' => 'required|in:demo,active,suspended',
            'plan' => 'required|in:basic,selfservice,premium,max',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'tagline' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|string|max:500',
            'cuisine_type' => 'nullable|string|max:100',
            'price_range' => 'nullable|string|in:$,$$,$$$,$$$$',
            'google_place_id' => 'nullable|string|max:255',
            'og_image' => 'nullable|image|max:2048',
            'ordering_enabled' => 'nullable|boolean',
            'ordering_settings' => 'nullable|array',
            'ordering_settings.accepts_delivery' => 'nullable|boolean',
            'ordering_settings.accepts_pickup' => 'nullable|boolean',
            'ordering_settings.minimum_order' => 'nullable|numeric|min:0',
            'ordering_settings.delivery_fee' => 'nullable|numeric|min:0',
            'ordering_settings.tax_rate' => 'nullable|numeric|min:0|max:100',
            'ordering_settings.estimated_prep_time_minutes' => 'nullable|integer|min:5|max:180',
            'ordering_settings.notification_email' => 'nullable|email|max:255',
            'ordering_settings.notification_phone' => 'nullable|string|max:50',
            'ordering_settings.auto_confirm' => 'nullable|boolean',
        ]);

        // Handle ordering settings
        $orderingEnabled = $request->boolean('ordering_enabled');
        $orderingSettings = $request->input('ordering_settings', []);

        // Convert tax rate from percentage to decimal
        if (isset($orderingSettings['tax_rate'])) {
            $orderingSettings['tax_rate'] = floatval($orderingSettings['tax_rate']) / 100;
        }

        // Ensure booleans are properly set
        $orderingSettings['accepts_pickup'] = $request->boolean('ordering_settings.accepts_pickup');
        $orderingSettings['accepts_delivery'] = $request->boolean('ordering_settings.accepts_delivery');
        $orderingSettings['auto_confirm'] = $request->boolean('ordering_settings.auto_confirm');

        $validated['ordering_enabled'] = $orderingEnabled;
        $validated['ordering_settings'] = $orderingSettings;

        // Handle OG image upload
        if ($request->hasFile('og_image')) {
            // Delete old OG image if exists
            if ($site->og_image_path) {
                \Storage::disk('public')->delete($site->og_image_path);
            }
            $validated['og_image_path'] = $request->file('og_image')
                ->store("restaurants/{$site->slug}/seo", 'public');
        }
        unset($validated['og_image']);

        $site->update($validated);

        return redirect()->route('admin.restaurant.index')
            ->with('status', "Updated {$site->business_name}");
    }

    public function destroy(RestaurantSite $site)
    {
        $name = $site->business_name;
        $site->delete();

        return redirect()->route('admin.restaurant.index')
            ->with('status', "Deleted {$name}");
    }

    public function toggleStatus(Request $request, RestaurantSite $site)
    {
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['demo', 'active', 'suspended'])) {
            return back()->with('error', 'Invalid status');
        }

        $site->update(['status' => $newStatus]);

        // If activating, also update the service status
        if ($newStatus === 'active' && $site->service) {
            $site->service->update(['status' => 'active']);
        } elseif ($newStatus === 'suspended' && $site->service) {
            $site->service->update(['status' => 'suspended']);
        }

        return back()->with('status', "Changed {$site->business_name} status to {$newStatus}");
    }

    /**
     * Show the convert to paying form
     */
    public function showConvertForm(RestaurantSite $site)
    {
        if ($site->status !== 'demo') {
            return redirect()->route('admin.restaurant.show', $site)
                ->with('error', 'Only demo sites can be converted to paying.');
        }

        $plans = RestaurantPlan::active()->ordered()->get();

        return view('admin.restaurant.convert', compact('site', 'plans'));
    }

    /**
     * Convert a demo site to a paying customer
     */
    public function convertToPaying(Request $request, RestaurantSite $site)
    {
        $validated = $request->validate([
            'customer_email' => 'required|email|max:255',
            'customer_name' => 'required|string|max:255',
            'plan_id' => 'required|exists:menudirect.restaurant_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'waive_setup_fee' => 'nullable|boolean',
            'send_welcome_email' => 'nullable|boolean',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_reason' => 'nullable|string|max:255',
        ]);

        $plan = RestaurantPlan::findOrFail($validated['plan_id']);

        try {
            DB::beginTransaction();

            // Find or create the client
            $client = Client::where('email', $validated['customer_email'])->first();
            $isNewClient = false;
            $tempPassword = null;

            if (!$client) {
                $isNewClient = true;
                $tempPassword = Str::random(12);
                $client = Client::create([
                    'email' => $validated['customer_email'],
                    'name' => $validated['customer_name'],
                    'first_name' => explode(' ', $validated['customer_name'])[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $validated['customer_name']), 1)) ?: null,
                    'password' => bcrypt($tempPassword),
                    'force_password_change' => true,
                ]);
            }

            // Calculate pricing
            $isAnnual = $validated['billing_cycle'] === 'annual';
            $recurringPrice = $isAnnual ? $plan->price_annual : $plan->price_monthly;
            $setupFee = $request->boolean('waive_setup_fee') ? 0 : $plan->setup_fee;
            $total = $recurringPrice + $setupFee;

            // Update the site
            $site->update([
                'client_id' => $client->id,
                'restaurant_plan_id' => $plan->id,
                'plan' => $this->mapPlanToType($plan->slug),
                // Keep as demo until payment - will be activated when invoice is paid
            ]);

            // Create the service record
            $nextBillDate = $isAnnual ? now()->addYear() : now()->addMonth();
            $service = Service::create([
                'client_id' => $client->id,
                'name' => "Restaurant Website: {$site->business_name}",
                'type' => Service::TYPE_RESTAURANT,
                'serviceable_type' => RestaurantSite::class,
                'serviceable_id' => $site->id,
                'status' => Service::STATUS_PENDING,
                'billing_cycle' => $isAnnual ? 'annual' : 'monthly',
                'amount' => $recurringPrice,
                'next_bill_date' => $nextBillDate,
                'starts_at' => now(),
                'auto_renew' => true,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'plan_slug' => $plan->slug,
                ],
            ]);

            // Create the invoice
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'status' => 'pending',
                'issue_date' => now(),
                'due_date' => now()->addDays(7),
                'subtotal' => $total,
                'total' => $total,
            ]);

            // Add invoice items
            if ($setupFee > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => InvoiceItem::TYPE_SERVICE,
                    'description' => "{$plan->name} Restaurant Website - Setup Fee",
                    'quantity' => 1,
                    'unit_price' => $setupFee,
                    'total' => $setupFee,
                ]);
            }

            $cycleLabel = $isAnnual ? 'Annual' : 'Monthly';
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => InvoiceItem::TYPE_SERVICE,
                'description' => "{$plan->name} Restaurant Website - {$cycleLabel} Subscription",
                'quantity' => 1,
                'unit_price' => $recurringPrice,
                'total' => $recurringPrice,
                'service_id' => $service->id,
            ]);

            DB::commit();

            // Send welcome email if requested
            if ($request->boolean('send_welcome_email')) {
                Mail::to($client->email)->send(new RestaurantWelcome(
                    $client,
                    $site,
                    $invoice,
                    $isNewClient,
                    $tempPassword
                ));
            }

            return redirect()->route('admin.restaurant.show', $site)
                ->with('status', "Converted {$site->business_name} to paying customer. Invoice #{$invoice->invoice_number} created." .
                    ($request->boolean('send_welcome_email') ? ' Welcome email sent.' : ''));

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()->with('error', 'Failed to convert: ' . $e->getMessage());
        }
    }

    /**
     * Map plan slug to RestaurantSite plan type
     */
    protected function mapPlanToType(string $slug): string
    {
        return match ($slug) {
            'basic' => RestaurantSite::PLAN_BASIC,
            'sitefresh' => RestaurantSite::PLAN_SELFSERVICE,
            'sitefresh-pro' => RestaurantSite::PLAN_PREMIUM,
            'menudirect-max' => RestaurantSite::PLAN_MAX,
            default => RestaurantSite::PLAN_BASIC,
        };
    }

    /**
     * Setup billing for a restaurant site (pre-activation)
     */
    public function setupBilling(Request $request, RestaurantSite $site)
    {
        $validated = $request->validate([
            'billing_client_id' => 'required|exists:clients,id',
            'plan_id' => 'required|exists:menudirect.restaurant_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'waive_setup_fee' => 'nullable|boolean',
            'create_invoice' => 'nullable|boolean',
            'payment_terms' => 'nullable|in:cc,net10,net15,net30',
            'activate_now' => 'nullable|boolean',
            'send_welcome_email' => 'nullable|boolean',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percent,fixed',
            'discount_reason' => 'nullable|string|max:255',
        ]);

        $plan = RestaurantPlan::findOrFail($validated['plan_id']);
        $client = Client::findOrFail($validated['billing_client_id']);

        try {
            DB::beginTransaction();

            $isAnnual = $validated['billing_cycle'] === 'annual';
            $recurringPrice = $isAnnual ? $plan->price_annual : $plan->price_monthly;
            $setupFee = $request->boolean('waive_setup_fee') ? 0 : $plan->setup_fee;

            // Apply client discount if any
            $recurringPrice = $client->applyServiceDiscount($recurringPrice);

            // Apply manual discount on top
            $manualDiscountAmount = 0;
            if ($request->filled('discount_amount') && floatval($request->discount_amount) > 0) {
                if (($validated['discount_type'] ?? 'percent') === 'percent') {
                    $manualDiscountAmount = $recurringPrice * (floatval($request->discount_amount) / 100);
                } else {
                    $manualDiscountAmount = floatval($request->discount_amount);
                }
                $recurringPrice = max(0, $recurringPrice - $manualDiscountAmount);
            }

            // Update the site with client and plan
            $site->update([
                'client_id' => $client->id,
                'restaurant_plan_id' => $plan->id,
                'plan' => $this->mapPlanToType($plan->slug),
            ]);

            // Create or update the Service record
            $service = Service::updateOrCreate(
                [
                    'serviceable_type' => RestaurantSite::class,
                    'serviceable_id' => $site->id,
                ],
                [
                    'client_id' => $client->id,
                    'name' => "Restaurant Website: {$site->business_name}",
                    'type' => Service::TYPE_RESTAURANT,
                    'status' => $request->boolean('activate_now') ? Service::STATUS_ACTIVE : Service::STATUS_PENDING,
                    'billing_cycle' => $isAnnual ? 'annual' : 'monthly',
                    'amount' => $recurringPrice,
                    'next_bill_date' => $isAnnual ? now()->addYear() : now()->addMonth(),
                    'starts_at' => now(),
                    'auto_renew' => true,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'plan_name' => $plan->name,
                        'plan_slug' => $plan->slug,
                        'setup_fee_waived' => $request->boolean('waive_setup_fee'),
                        'manual_discount' => $manualDiscountAmount > 0 ? [
                            'amount' => round($manualDiscountAmount, 2),
                            'type' => $validated['discount_type'] ?? 'percent',
                            'value' => floatval($request->discount_amount),
                            'reason' => $validated['discount_reason'] ?? 'Manual discount',
                        ] : null,
                    ],
                ]
            );

            // If activating now, flip the site status
            if ($request->boolean('activate_now')) {
                $site->update(['status' => RestaurantSite::STATUS_ACTIVE]);
            }

            // Create invoice if requested
            $invoice = null;
            if ($request->boolean('create_invoice')) {
                $total = $recurringPrice + $setupFee;  // recurringPrice already has all discounts applied

                // Determine due date based on payment terms
                $dueDays = match($validated['payment_terms'] ?? $client->payment_terms ?? 'cc') {
                    'net10' => 10,
                    'net15' => 15,
                    'net30' => 30,
                    default => 7,
                };

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'status' => 'pending',
                    'issue_date' => now(),
                    'due_date' => now()->addDays($dueDays),
                    'subtotal' => $total,
                    'total' => $total,
                    'notes' => "Restaurant website: {$site->business_name}",
                ]);

                if ($setupFee > 0) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'type' => InvoiceItem::TYPE_SERVICE,
                        'description' => "{$plan->name} Restaurant Website - Setup Fee",
                        'quantity' => 1,
                        'unit_price' => $setupFee,
                        'total' => $setupFee,
                    ]);
                }

                $cycleLabel = $isAnnual ? 'Annual' : 'Monthly';
                $baseRecurring = $isAnnual ? $plan->price_annual : $plan->price_monthly;
                $clientDiscountedPrice = $client->applyServiceDiscount($baseRecurring);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'type' => InvoiceItem::TYPE_SERVICE,
                    'description' => "{$plan->name} Restaurant Website - {$cycleLabel} Subscription",
                    'quantity' => 1,
                    'unit_price' => $clientDiscountedPrice,
                    'total' => $clientDiscountedPrice,
                    'service_id' => $service->id,
                ]);

                // Add manual discount line if applicable
                if ($manualDiscountAmount > 0) {
                    $discReason = $validated['discount_reason'] ?? 'Manual discount';
                    $discLabel = ($validated['discount_type'] ?? 'percent') === 'percent'
                        ? $discReason . ' (' . floatval($request->discount_amount) . '%)'
                        : $discReason;
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'type' => InvoiceItem::TYPE_SERVICE,
                        'description' => "Discount: {$discLabel}",
                        'quantity' => 1,
                        'unit_price' => -$manualDiscountAmount,
                        'total' => -$manualDiscountAmount,
                    ]);
                }
            }

            // Send welcome email if requested
            if ($request->boolean('send_welcome_email') && $request->boolean('activate_now')) {
                try {
                    Mail::to($client->email)->send(new RestaurantWelcome(
                        $client,
                        $site,
                        $invoice,
                        false,
                        null
                    ));
                } catch (\Exception $e) {
                    report($e);
                    // Don't fail the whole operation for email
                }
            }

            DB::commit();

            $msg = "Billing configured for {$site->business_name}.";
            if ($service->wasRecentlyCreated) {
                $msg .= " Service record created.";
            } else {
                $msg .= " Service record updated.";
            }
            if ($invoice) {
                $msg .= " Invoice #{$invoice->invoice_number} created.";
            }
            if ($request->boolean('activate_now')) {
                $msg .= " Site activated.";
            }

            return redirect()->route('admin.restaurant.edit', $site)
                ->with('status', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->with('error', 'Failed to setup billing: ' . $e->getMessage());
        }
    }


    /**
     * Add a custom domain to a restaurant site and configure DNS in Cloudflare.
     */
    public function addCustomDomain(Request $request, RestaurantSite $site)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:menudirect.restaurant_custom_domains,domain',
            'is_primary' => 'nullable|boolean',
        ]);

        $domainName = strtolower(trim($validated['domain']));
        $domainName = preg_replace('/^www\./', '', $domainName);

        try {
            DB::beginTransaction();

            if ($request->boolean('is_primary')) {
                $site->customDomains()->update(['is_primary' => false]);
            }

            $customDomain = RestaurantCustomDomain::create([
                'restaurant_site_id' => $site->id,
                'domain' => $domainName,
                'is_primary' => $request->boolean('is_primary'),
                'status' => 'pending',
            ]);

            // Auto-set as primary if first domain
            if (!$request->boolean('is_primary') && $site->customDomains()->count() === 1) {
                $customDomain->update(['is_primary' => true]);
            }

            // Update legacy field with primary domain
            $primary = $site->customDomains()->where('is_primary', true)->first();
            if ($primary) {
                $site->update(['custom_domain' => $primary->domain]);
            }

            // Configure Cloudflare DNS
            $dnsResult = $this->configureDnsForCustomDomain($customDomain, $domainName);

            DB::commit();

            $msg = "Custom domain {$domainName} added.";
            if ($dnsResult['success']) {
                $msg .= " DNS records configured in Cloudflare.";
            } else {
                $msg .= " DNS note: " . $dnsResult['message'];
            }

            return redirect()->route('admin.restaurant.edit', $site)->with('status', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return redirect()->route('admin.restaurant.edit', $site)
                ->with('error', "Failed to add custom domain: " . $e->getMessage());
        }
    }

    /**
     * Remove a custom domain from a restaurant site.
     */
    public function removeCustomDomain(Request $request, RestaurantSite $site, RestaurantCustomDomain $customDomain)
    {
        if ($customDomain->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $domainName = $customDomain->domain;
        $wasPrimary = $customDomain->is_primary;
        $customDomain->delete();

        if ($wasPrimary) {
            $nextDomain = $site->customDomains()->first();
            if ($nextDomain) {
                $nextDomain->update(['is_primary' => true]);
                $site->update(['custom_domain' => $nextDomain->domain]);
            } else {
                $site->update(['custom_domain' => null]);
            }
        }

        return redirect()->route('admin.restaurant.edit', $site)
            ->with('status', "Custom domain {$domainName} removed.");
    }

    /**
     * Set a custom domain as the primary domain.
     */
    public function setPrimaryCustomDomain(Request $request, RestaurantSite $site, RestaurantCustomDomain $customDomain)
    {
        if ($customDomain->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $site->customDomains()->update(['is_primary' => false]);
        $customDomain->update(['is_primary' => true]);
        $site->update(['custom_domain' => $customDomain->domain]);

        return redirect()->route('admin.restaurant.edit', $site)
            ->with('status', "{$customDomain->domain} set as primary domain.");
    }

    /**
     * Configure DNS records in Cloudflare for a custom domain.
     * Creates CNAME records pointing to edge.sos-tech.ca.
     */
    private function configureDnsForCustomDomain(RestaurantCustomDomain $customDomain, string $domainName): array
    {
        try {
            $cf = new CloudflareClient();
            $connection = \App\Models\CloudflareConnection::where('is_active', true)->first();
            $accountId = $connection ? $connection->account_id : null;

            // Extract root domain
            $parts = explode('.', $domainName);
            $rootDomain = count($parts) > 2
                ? implode('.', array_slice($parts, -2))
                : $domainName;

            // Find Cloudflare zone
            $cfZone = \App\Models\CloudflareZone::where('name', $rootDomain)->first();
            $zoneId = $cfZone ? $cfZone->zone_id : null;

            if (!$zoneId) {
                $zones = $cf->listZones(['name' => $rootDomain]);
                if (!empty($zones['result'])) {
                    $zoneId = $zones['result'][0]['id'];
                    \App\Models\CloudflareZone::updateOrCreate(
                        ['zone_id' => $zoneId],
                        ['name' => $rootDomain, 'status' => $zones['result'][0]['status']]
                    );
                } elseif ($accountId) {
                    $resp = $cf->createZone($rootDomain, $accountId, 'full', false);
                    if (!empty($resp['result']['id'])) {
                        $zoneId = $resp['result']['id'];
                        \App\Models\CloudflareZone::updateOrCreate(
                            ['zone_id' => $zoneId],
                            [
                                'name' => $rootDomain,
                                'status' => $resp['result']['status'] ?? 'pending',
                                'meta' => json_encode(['name_servers' => $resp['result']['name_servers'] ?? []]),
                            ]
                        );
                    }
                }
            }

            if (!$zoneId) {
                $customDomain->update([
                    'status' => 'pending',
                    'notes' => 'Cloudflare zone not found for ' . $rootDomain . '. DNS needs manual setup.',
                ]);
                return ['success' => false, 'message' => 'Zone not found for ' . $rootDomain];
            }

            $customDomain->update(['cloudflare_zone_id' => $zoneId]);

            $target = 'edge.sos-tech.ca';
            $recordsToCreate = [];

            if ($domainName === $rootDomain) {
                $recordsToCreate[] = ['type' => 'CNAME', 'name' => $rootDomain, 'content' => $target, 'proxied' => false, 'ttl' => 1];
                $recordsToCreate[] = ['type' => 'CNAME', 'name' => 'www', 'content' => $target, 'proxied' => false, 'ttl' => 1];
            } else {
                $recordsToCreate[] = ['type' => 'CNAME', 'name' => $domainName, 'content' => $target, 'proxied' => false, 'ttl' => 1];
            }

            $created = 0;
            $errors = [];
            foreach ($recordsToCreate as $record) {
                try {
                    $searchName = $record['name'];
                    if ($searchName !== $rootDomain && !str_contains($searchName, '.')) {
                        $searchName = $searchName . '.' . $rootDomain;
                    }
                    $existing = $cf->listDnsRecords($zoneId, ['name' => $searchName, 'type' => $record['type']]);
                    if (!empty($existing['result'])) {
                        $cf->updateRecord($zoneId, $existing['result'][0]['id'], $record, false);
                        $created++;
                    } else {
                        $cf->createRecord($zoneId, $record);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = $record['name'] . ': ' . $e->getMessage();
                }
            }

            if ($created > 0) {
                $customDomain->update([
                    'dns_configured' => true,
                    'status' => 'active',
                    'notes' => "DNS: {$created} CNAME record(s) -> {$target}" . ($errors ? '. Errors: ' . implode('; ', $errors) : ''),
                ]);
                return ['success' => true, 'message' => "{$created} DNS record(s) created"];
            }

            $customDomain->update([
                'status' => 'failed',
                'notes' => 'DNS creation failed: ' . implode('; ', $errors),
            ]);
            return ['success' => false, 'message' => implode('; ', $errors)];

        } catch (\Exception $e) {
            $customDomain->update([
                'status' => 'failed',
                'notes' => 'DNS error: ' . $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    /**
     * Check Cloudflare DNS status for a custom domain.
     * Also creates missing DNS records if zone exists.
     */
    public function checkCustomDomainStatus(Request $request, RestaurantSite $site, RestaurantCustomDomain $customDomain)
    {
        if ($customDomain->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $results = [
            'domain' => $customDomain->domain,
            'checks' => [],
        ];

        try {
            $cf = new CloudflareClient();

            // Extract root domain
            $parts = explode('.', $customDomain->domain);
            $rootDomain = count($parts) > 2
                ? implode('.', array_slice($parts, -2))
                : $customDomain->domain;

            // Find Cloudflare zone (local DB first, then API)
            $cfZone = \App\Models\CloudflareZone::where('name', $rootDomain)->first();
            $zoneId = $cfZone ? $cfZone->zone_id : null;

            if (!$zoneId) {
                // Try the Cloudflare API directly
                $zones = $cf->listZones(['name' => $rootDomain]);
                if (!empty($zones['result'])) {
                    $zoneId = $zones['result'][0]['id'];
                    $cfZone = \App\Models\CloudflareZone::updateOrCreate(
                        ['zone_id' => $zoneId],
                        ['name' => $rootDomain, 'status' => $zones['result'][0]['status']]
                    );
                }
            }

            if (!$zoneId) {
                $results['checks']['zone'] = ['status' => 'missing', 'message' => 'No Cloudflare zone found for ' . $rootDomain . '. Add the zone in Cloudflare first.'];
                return redirect()->route('admin.restaurant.edit', $site)
                    ->with('domain_status', $results);
            }

            // Always update the custom domain's zone ID
            if (!$customDomain->cloudflare_zone_id) {
                $customDomain->update(['cloudflare_zone_id' => $zoneId]);
            }

            // Get fresh zone info from Cloudflare
            $zone = $cf->getZone($zoneId);
            $zoneStatus = $zone['result']['status'] ?? 'unknown';
            $nameServers = $zone['result']['name_servers'] ?? [];

            $cfZone->update([
                'status' => $zoneStatus,
                'meta' => json_encode(['name_servers' => $nameServers]),
            ]);

            $results['checks']['zone'] = [
                'status' => $zoneStatus,
                'message' => $zoneStatus === 'active'
                    ? 'Cloudflare zone is active'
                    : 'Zone status: ' . $zoneStatus . '. Nameservers: ' . implode(', ', $nameServers) . ' — point your domain NS here.',
                'name_servers' => $nameServers,
            ];

            // Check DNS records for root and www
            $target = 'edge.sos-tech.ca';
            $searchName = $customDomain->domain;
            $records = $cf->listDnsRecords($zoneId, ['name' => $searchName]);
            $cnameFound = false;
            foreach ($records['result'] ?? [] as $r) {
                if ($r['type'] === 'CNAME' && $r['content'] === $target) {
                    $cnameFound = true;
                    break;
                }
            }

            $wwwSearchName = 'www.' . $customDomain->domain;
            $wwwRecords = $cf->listDnsRecords($zoneId, ['name' => $wwwSearchName]);
            $wwwFound = false;
            foreach ($wwwRecords['result'] ?? [] as $r) {
                if ($r['type'] === 'CNAME' && $r['content'] === $target) {
                    $wwwFound = true;
                    break;
                }
            }

            // AUTO-FIX: Create missing DNS records
            $recordsCreated = 0;
            $recordErrors = [];

            if (!$cnameFound) {
                try {
                    $payload = ['type' => 'CNAME', 'name' => $searchName, 'content' => $target, 'proxied' => false, 'ttl' => 1];
                    $cf->createRecord($zoneId, $payload);
                    $cnameFound = true;
                    $recordsCreated++;
                } catch (\Exception $e) {
                    $recordErrors[] = $searchName . ': ' . $e->getMessage();
                }
            }

            if (!$wwwFound) {
                try {
                    $payload = ['type' => 'CNAME', 'name' => 'www', 'content' => $target, 'proxied' => false, 'ttl' => 1];
                    $cf->createRecord($zoneId, $payload);
                    $wwwFound = true;
                    $recordsCreated++;
                } catch (\Exception $e) {
                    $recordErrors[] = 'www: ' . $e->getMessage();
                }
            }

            $dnsMsg = '';
            if ($recordsCreated > 0) {
                $dnsMsg = "Created {$recordsCreated} missing CNAME record(s) -> {$target}. ";
            }
            if ($cnameFound && $wwwFound) {
                $dnsMsg .= 'Both root and www CNAME records point to ' . $target;
            } elseif ($cnameFound) {
                $dnsMsg .= 'Root CNAME OK, www still missing';
            } else {
                $dnsMsg .= 'CNAME records missing or could not be created';
            }
            if ($recordErrors) {
                $dnsMsg .= '. Errors: ' . implode('; ', $recordErrors);
            }

            $results['checks']['dns'] = [
                'status' => ($cnameFound && $wwwFound) ? 'ok' : ($cnameFound ? 'partial' : 'missing'),
                'root_cname' => $cnameFound,
                'www_cname' => $wwwFound,
                'records_created' => $recordsCreated,
                'message' => $dnsMsg,
            ];

            // Update custom domain record if DNS now configured
            if ($cnameFound) {
                $customDomain->update([
                    'dns_configured' => true,
                    'cloudflare_zone_id' => $zoneId,
                    'notes' => trim($dnsMsg),
                ]);
            }

            // Check if domain resolves externally
            $resolved = @dns_get_record($customDomain->domain, DNS_CNAME | DNS_A);
            $results['checks']['resolution'] = [
                'status' => !empty($resolved) ? 'ok' : 'pending',
                'records' => array_map(function($r) {
                    return $r['type'] . ': ' . ($r['target'] ?? $r['ip'] ?? 'unknown');
                }, $resolved ?: []),
                'message' => !empty($resolved)
                    ? 'Domain resolves: ' . implode(', ', array_map(fn($r) => ($r['target'] ?? $r['ip'] ?? ''), $resolved ?: []))
                    : 'Domain not resolving yet — NS propagation in progress. Records are ready in Cloudflare.',
            ];

            // Check HAProxy status
            $results['checks']['haproxy'] = $this->checkHaproxyStatus($customDomain->domain);

        } catch (\Exception $e) {
            $results['checks']['error'] = [
                'status' => 'error',
                'message' => 'Check failed: ' . $e->getMessage(),
            ];
        }

        return redirect()->route('admin.restaurant.edit', $site)
            ->with('domain_status', $results);
    }

    /**
     * Check if a domain is configured in HAProxy on .12
     */
    private function checkHaproxyStatus(string $domain): array
    {
        try {
            $haproxyHost = '192.168.22.12';
            $sshKey = storage_path('app/.ssh/haproxy.key');
            $cmd = "ssh -i {$sshKey} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=5 frank@{$haproxyHost} " .
                   "'grep -c \"" . addslashes($domain) . "\" /etc/haproxy/haproxy.cfg 2>/dev/null || echo 0'";
            $output = trim(shell_exec($cmd) ?? '0');
            $found = intval($output) > 0;

            // Check if cert exists
            $certCmd = "ssh -i {$sshKey} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=5 frank@{$haproxyHost} " .
                       "'sudo ls /etc/haproxy/certs/ 2>/dev/null | grep -c \"" . addslashes($domain) . "\" || echo 0'";
            $certOutput = trim(shell_exec($certCmd) ?? '0');
            $certFound = intval($certOutput) > 0;

            return [
                'status' => ($found && $certFound) ? 'active' : 'not_configured',
                'config_found' => $found,
                'cert_found' => $certFound,
                'message' => ($found && $certFound)
                    ? 'Domain is configured in HAProxy with SSL cert'
                    : 'Domain needs HAProxy activation' .
                      (!$found ? ' (no config entry)' : '') .
                      (!$certFound ? ' (no SSL cert)' : ''),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Could not check HAProxy: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Activate a custom domain on HAProxy - obtains SSL cert and adds config.
     */
    public function activateCustomDomainHaproxy(Request $request, RestaurantSite $site, RestaurantCustomDomain $customDomain)
    {
        if ($customDomain->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $domain = $customDomain->domain;
        $haproxyHost = '192.168.22.12';
        $sshKey = storage_path('app/.ssh/haproxy.key');
        $sshPrefix = "ssh -i {$sshKey} -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=10 frank@{$haproxyHost}";
        $results = [];

        try {
            // Step 1: Obtain Let's Encrypt certificate via dns-cloudflare
            $certCheckCmd = "{$sshPrefix} 'sudo ls /etc/letsencrypt/live/{$domain}/fullchain.pem 2>/dev/null && echo EXISTS || echo MISSING'";
            $certExists = trim(shell_exec($certCheckCmd) ?? 'MISSING');

            if ($certExists !== 'EXISTS') {
                $certCmd = "{$sshPrefix} 'sudo certbot certonly --dns-cloudflare --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini -d {$domain} -d www.{$domain} --non-interactive --agree-tos --key-type ecdsa 2>&1'";
                $certOutput = shell_exec($certCmd) ?? '';
                if (strpos($certOutput, 'Successfully') !== false || strpos($certOutput, 'Certificate not yet due') !== false) {
                    $results[] = 'SSL certificate obtained for ' . $domain;
                } else {
                    return redirect()->route('admin.restaurant.edit', $site)
                        ->with('error', 'Failed to obtain SSL cert: ' . substr($certOutput, 0, 500));
                }
            } else {
                $results[] = 'SSL certificate already exists for ' . $domain;
            }

            // Step 2: Create combined PEM for HAProxy
            $pemCmd = "{$sshPrefix} 'sudo bash -c \"cat /etc/letsencrypt/live/{$domain}/fullchain.pem /etc/letsencrypt/live/{$domain}/privkey.pem > /etc/haproxy/certs/{$domain}.pem\" 2>&1'";
            shell_exec($pemCmd);
            $results[] = 'HAProxy PEM file created';

            // Step 3: Add ACL to haproxy.cfg if not already present
            $grepCmd = "{$sshPrefix} 'grep -c \"{$domain}\" /etc/haproxy/haproxy.cfg 2>/dev/null || echo 0'";
            $alreadyInConfig = intval(trim(shell_exec($grepCmd) ?? '0')) > 0;

            if (!$alreadyInConfig) {
                // Create a safe ACL name from domain
                $aclName = 'host_' . str_replace(['.', '-'], '_', $domain);

                // Build the config lines to insert
                $aclLines = "    acl {$aclName} hdr(host) -i {$domain}\\n    acl {$aclName} hdr(host) -i www.{$domain}\\n    use_backend portal_backend if {$aclName}";

                // Insert before the menudirect ACL block (custom restaurant domains go before menudirect wildcard)
                $sedCmd = "{$sshPrefix} 'sudo cp /etc/haproxy/haproxy.cfg /etc/haproxy/haproxy.cfg.backup-\$(date +%s) && sudo sed -i \"/acl host_menudirect hdr(host) -i menudirect.ca/i\\    # Custom restaurant domain: {$domain}\\n{$aclLines}\\n\" /etc/haproxy/haproxy.cfg 2>&1'";
                $sedOutput = shell_exec($sedCmd) ?? '';
                $results[] = 'HAProxy config updated with ACL for ' . $domain;
            } else {
                $results[] = 'Domain already in HAProxy config';
            }

            // Step 4: Test and reload HAProxy
            $testCmd = "{$sshPrefix} 'sudo haproxy -c -f /etc/haproxy/haproxy.cfg 2>&1'";
            $testOutput = trim(shell_exec($testCmd) ?? '');

            if (strpos($testOutput, 'Configuration file is valid') !== false || strpos($testOutput, 'valid') !== false || empty($testOutput)) {
                $reloadCmd = "{$sshPrefix} 'sudo systemctl reload haproxy 2>&1'";
                shell_exec($reloadCmd);
                $results[] = 'HAProxy reloaded successfully';

                // Update custom domain record
                $customDomain->update([
                    'status' => 'active',
                    'notes' => 'Fully activated: DNS + SSL + HAProxy. ' . implode(' | ', $results),
                ]);
            } else {
                // Restore backup
                $restoreCmd = "{$sshPrefix} 'sudo cp \$(ls -t /etc/haproxy/haproxy.cfg.backup-* | head -1) /etc/haproxy/haproxy.cfg 2>&1'";
                shell_exec($restoreCmd);
                return redirect()->route('admin.restaurant.edit', $site)
                    ->with('error', 'HAProxy config test failed: ' . $testOutput . '. Config restored from backup.');
            }

            return redirect()->route('admin.restaurant.edit', $site)
                ->with('status', 'HAProxy activated for ' . $domain . '. ' . implode(' | ', $results));

        } catch (\Exception $e) {
            report($e);
            return redirect()->route('admin.restaurant.edit', $site)
                ->with('error', 'HAProxy activation failed: ' . $e->getMessage());
        }
    }

}
