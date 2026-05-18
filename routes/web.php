<?php

use Illuminate\Support\Facades\Route;

// --------------------------------------------------------------------
// Marketing apex — menudirect.ca
// --------------------------------------------------------------------
Route::domain('menudirect.ca')->group(function () {
    Route::get('/', function () {
        return view('menudirect.landing');
    })->name('menudirect.home');

    Route::post('/lead', [\App\Http\Controllers\MenuDirectController::class, 'submitLead'])
        ->name('menudirect.lead');

    Route::post('/try-demo', [\App\Http\Controllers\MenuDirectController::class, 'createDemo'])
        ->middleware('throttle:10,1')
        ->name('menudirect.create-demo');
});

// Redirect www.menudirect.ca → apex
Route::domain('www.menudirect.ca')->group(function () {
    Route::get('/', function () {
        return redirect('https://menudirect.ca', 301);
    });
    Route::get('/{any}', function () {
        return redirect('https://menudirect.ca', 301);
    })->where('any', '.*');
});

// --------------------------------------------------------------------
// Owner portal — portal.menudirect.ca only (auth, restaurant management)
// (defined BEFORE {slug}.menudirect.ca so the {slug} wildcard doesn't
//  swallow "portal" as a slug match)
// --------------------------------------------------------------------
Route::domain('portal.menudirect.ca')->group(function () {

    Route::get('/', function () {
        return redirect()->route('login');
    });

    Route::middleware('guest')->group(function () {
        Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'show'])->name('login');
        Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.post');
        Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->name('password.email');
        Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'update'])->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
        Route::get('/two-factor-challenge', [\App\Http\Controllers\Auth\LoginController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [\App\Http\Controllers\Auth\LoginController::class, 'verifyTwoFactorChallenge'])->name('two-factor.verify');
    });

    // Public tracking pages (no auth)
    Route::get('/order/{token}', [\App\Http\Controllers\SampleSiteController::class, 'trackOrder'])->name('order.track');
    Route::get('/reservation/{token}', [\App\Http\Controllers\SampleSiteController::class, 'reservationStatus'])->name('reservation.status');
});

// --------------------------------------------------------------------
// Demo kitchen display (public, no auth — demo sites only)
// --------------------------------------------------------------------
Route::get('/demo-kitchen/{slug}', [\App\Http\Controllers\Api\DemoKitchenController::class, 'show'])
    ->name('demo-kitchen.show');
Route::get('/demo-kitchen/{slug}/server', [\App\Http\Controllers\Api\DemoKitchenController::class, 'server'])
    ->name('demo-kitchen.server');

// --------------------------------------------------------------------
// Staff portal (restaurant staff — separate guard via RestaurantStaff model)
// --------------------------------------------------------------------
Route::prefix('staff')->name('staff.')->group(function () {
    // Auth routes (no middleware)
    Route::get('/login', [\App\Http\Controllers\Staff\StaffAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Staff\StaffAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [\App\Http\Controllers\Staff\StaffAuthController::class, 'logout'])->name('logout');
    Route::get('/invite/{token}', [\App\Http\Controllers\Staff\StaffAuthController::class, 'showAcceptInvite'])->name('invite.show');
    Route::post('/invite/{token}', [\App\Http\Controllers\Staff\StaffAuthController::class, 'acceptInvite'])->name('invite.accept');

    // Authenticated staff routes
    Route::middleware('staff.auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\Staff\StaffDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'index'])->name('orders.index');
        Route::get('/orders/live', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'live'])->name('orders.live');
        Route::get('/server', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'server'])->name('server');
        Route::get('/orders/{order}', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/confirm', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{order}/preparing', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'preparing'])->name('orders.preparing');
        Route::post('/orders/{order}/ready', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'ready'])->name('orders.ready');
        Route::post('/orders/{order}/complete', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'complete'])->name('orders.complete');
        Route::post('/orders/{order}/cancel', [\App\Http\Controllers\Staff\StaffOrdersController::class, 'cancel'])->name('orders.cancel');
    });
});

// --------------------------------------------------------------------
// Owner portal — under portal.menudirect.ca, prefix /client
// --------------------------------------------------------------------
Route::domain('portal.menudirect.ca')->prefix('client')->name('client.')->middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        // No dedicated dashboard yet — owners land directly on their restaurant listing.
        return redirect()->route('client.restaurant.index');
    })->name('dashboard');

    Route::prefix('restaurant')->name('restaurant.')->group(function () {
        // Site CRUD
        Route::get('/', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'store'])->name('store');
        Route::get('/{site}', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'show'])->name('show');
        Route::get('/{site}/edit', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'edit'])->name('edit');
        Route::put('/{site}', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'update'])->name('update');

        // Quick actions
        Route::put('/{site}/hours', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateHours'])->name('hours');
        Route::put('/{site}/colors', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateColors'])->name('colors');
        Route::post('/{site}/logo', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'uploadLogo'])->name('logo');
        Route::delete('/{site}/logo', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'removeLogo'])->name('logo.remove');
        Route::post('/{site}/cover', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'uploadCoverPhoto'])->name('cover');
        Route::delete('/{site}/cover', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'removeCoverPhoto'])->name('cover.remove');
        Route::get('/{site}/preview', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'preview'])->name('preview');
        Route::post('/{site}/publish', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'publish'])->name('publish');
        Route::get('/{site}/templates', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'templates'])->name('templates');
        Route::put('/{site}/templates', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateTemplate'])->name('templates.update');

        // Menu management
        Route::get('/{site}/menu', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'index'])->name('menu');
        Route::post('/{site}/categories', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'storeCategory'])->name('categories.store');
        Route::put('/{site}/categories/{category}', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'updateCategory'])->name('categories.update');
        Route::delete('/{site}/categories/{category}', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/{site}/categories/reorder', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'reorderCategories'])->name('categories.reorder');
        Route::post('/{site}/categories/{category}/items', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'storeItem'])->name('items.store');
        Route::put('/{site}/items/{item}', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'updateItem'])->name('items.update');
        Route::delete('/{site}/items/{item}', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'destroyItem'])->name('items.destroy');
        Route::post('/{site}/categories/{category}/items/reorder', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'reorderItems'])->name('items.reorder');
        Route::post('/{site}/items/{item}/move', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'moveItem'])->name('items.move');
        Route::post('/{site}/items/{item}/inline-update', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'inlineUpdateItem'])->name('items.inline-update');
        Route::post('/{site}/items/{item}/active', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'toggleItemActive'])->name('items.active');
        Route::post('/{site}/items/{item}/duplicate', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'duplicateItem'])->name('items.duplicate');
        Route::post('/{site}/items/{item}/image', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'uploadItemImage'])->name('items.image');
        Route::post('/{site}/items/{item}/featured', [\App\Http\Controllers\Client\RestaurantMenuController::class, 'toggleItemFeatured'])->name('items.featured');

        // Announcements
        Route::get('/{site}/announcements', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'index'])->name('announcements');
        Route::post('/{site}/announcements', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/{site}/announcements/{announcement}', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/{site}/announcements/{announcement}', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::post('/{site}/announcements/{announcement}/toggle', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'toggle'])->name('announcements.toggle');
        Route::post('/{site}/announcements/{announcement}/image', [\App\Http\Controllers\Client\RestaurantAnnouncementController::class, 'updateImage'])->name('announcements.image');

        // Gallery management
        Route::post('/{site}/gallery', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'uploadGalleryPhoto'])->name('gallery.store');
        Route::post('/{site}/gallery/{index}/replace', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'replaceGalleryPhoto'])->name('gallery.replace');
        Route::post('/{site}/gallery/{index}/caption', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateGalleryCaption'])->name('gallery.caption');
        Route::delete('/{site}/gallery/{index}', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'deleteGalleryPhoto'])->name('gallery.destroy');

        // Holiday hours
        Route::post('/{site}/holiday-hours', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'storeHolidayHour'])->name('holiday-hours.store');
        Route::put('/{site}/holiday-hours/{holidayHour}', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateHolidayHour'])->name('holiday-hours.update');
        Route::delete('/{site}/holiday-hours/{holidayHour}', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'destroyHolidayHour'])->name('holiday-hours.destroy');

        // Ordering settings
        Route::put('/{site}/ordering', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateOrderingSettings'])->name('ordering.update');

        // Reservation settings
        Route::put('/{site}/reservations', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateReservationSettings'])->name('reservations.update');

        // Reservation management
        Route::get('/{site}/reservations', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'index'])->name('reservations.index');
        Route::get('/{site}/reservations/{reservation}', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'show'])->name('reservations.show');
        Route::post('/{site}/reservations/{reservation}/confirm', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'confirm'])->name('reservations.confirm');
        Route::post('/{site}/reservations/{reservation}/seat', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'seat'])->name('reservations.seat');
        Route::post('/{site}/reservations/{reservation}/complete', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'complete'])->name('reservations.complete');
        Route::post('/{site}/reservations/{reservation}/cancel', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'cancel'])->name('reservations.cancel');
        Route::post('/{site}/reservations/{reservation}/no-show', [\App\Http\Controllers\Client\RestaurantReservationsController::class, 'noShow'])->name('reservations.no-show');

        // Catering settings + management
        Route::put('/{site}/catering-settings', [\App\Http\Controllers\Client\RestaurantSiteController::class, 'updateCateringSettings'])->name('catering.settings.update');
        Route::get('/{site}/catering', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'index'])->name('catering.index');
        Route::get('/{site}/catering/packages', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'packages'])->name('catering.packages');
        Route::post('/{site}/catering/packages', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'storePackage'])->name('catering.packages.store');
        Route::put('/{site}/catering/packages/{package}', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'updatePackage'])->name('catering.packages.update');
        Route::delete('/{site}/catering/packages/{package}', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'destroyPackage'])->name('catering.packages.destroy');
        Route::post('/{site}/catering/packages/reorder', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'reorderPackages'])->name('catering.packages.reorder');
        Route::post('/{site}/catering/packages/{package}/image', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'uploadPackageImage'])->name('catering.packages.image');
        Route::get('/{site}/catering/{inquiry}', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'show'])->name('catering.show');
        Route::post('/{site}/catering/{inquiry}/status', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'updateStatus'])->name('catering.status');
        Route::post('/{site}/catering/{inquiry}/note', [\App\Http\Controllers\Client\RestaurantCateringController::class, 'addNote'])->name('catering.note');

        // Orders management
        Route::get('/{site}/orders', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'index'])->name('orders.index');
        Route::get('/{site}/orders/{order}', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'show'])->name('orders.show');
        Route::post('/{site}/orders/{order}/confirm', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'confirm'])->name('orders.confirm');
        Route::post('/{site}/orders/{order}/preparing', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'preparing'])->name('orders.preparing');
        Route::post('/{site}/orders/{order}/ready', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'ready'])->name('orders.ready');
        Route::post('/{site}/orders/{order}/complete', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'complete'])->name('orders.complete');
        Route::post('/{site}/orders/{order}/cancel', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'cancel'])->name('orders.cancel');
        Route::put('/{site}/orders/{order}/estimate', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'updateEstimate'])->name('orders.estimate');
        Route::get('/{site}/orders-poll', [\App\Http\Controllers\Client\RestaurantOrdersController::class, 'poll'])->name('orders.poll');

        // Staff management (owner-controlled)
        Route::get('/{site}/staff', [\App\Http\Controllers\Client\RestaurantStaffController::class, 'index'])->name('staff.index');
        Route::post('/{site}/staff', [\App\Http\Controllers\Client\RestaurantStaffController::class, 'store'])->name('staff.store');
        Route::post('/{site}/staff/{staff}/resend', [\App\Http\Controllers\Client\RestaurantStaffController::class, 'resendInvite'])->name('staff.resend');
        Route::delete('/{site}/staff/{staff}', [\App\Http\Controllers\Client\RestaurantStaffController::class, 'destroy'])->name('staff.destroy');
    });
});

// --------------------------------------------------------------------
// Admin (MenuDirect-only admin — Frank) — portal.menudirect.ca, prefix /admin
// --------------------------------------------------------------------
Route::domain('portal.menudirect.ca')->prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Restaurant sites
    Route::get('/restaurant', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'index'])->name('restaurant.index');
    Route::get('/restaurant/create', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'create'])->name('restaurant.create');
    Route::post('/restaurant', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'store'])->name('restaurant.store');
    Route::get('/restaurant/{site}', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'show'])->name('restaurant.show');
    Route::get('/restaurant/{site}/edit', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'edit'])->name('restaurant.edit');
    Route::put('/restaurant/{site}', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'update'])->name('restaurant.update');
    Route::delete('/restaurant/{site}', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'destroy'])->name('restaurant.destroy');
    Route::patch('/restaurant/{site}/status', [\App\Http\Controllers\Admin\RestaurantSitesController::class, 'toggleStatus'])->name('restaurant.toggle-status');

    // Restaurant leads
    Route::get('/leads', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'create'])->name('leads.create');
    Route::post('/leads', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'store'])->name('leads.store');
    Route::get('/leads-export', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'export'])->name('leads.export');
    Route::post('/leads/bulk-status', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'bulkUpdateStatus'])->name('leads.bulk-status');
    Route::get('/leads/{lead}', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'show'])->name('leads.show');
    Route::get('/leads/{lead}/edit', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'edit'])->name('leads.edit');
    Route::put('/leads/{lead}', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'update'])->name('leads.update');
    Route::delete('/leads/{lead}', [\App\Http\Controllers\Admin\RestaurantLeadsController::class, 'destroy'])->name('leads.destroy');
});

// --------------------------------------------------------------------
// Restaurant subdomains — {slug}.menudirect.ca
//
// Placed AFTER all portal.menudirect.ca groups so the {slug} wildcard
// doesn't grab "portal" first.
// --------------------------------------------------------------------
Route::domain('{slug}.menudirect.ca')->group(function () {
    Route::get('/', [\App\Http\Controllers\SampleSiteController::class, 'show']);
    Route::get('/{path}', [\App\Http\Controllers\SampleSiteController::class, 'show'])
        ->where('path', '.*');
});

// --------------------------------------------------------------------
// Template preview (admin/marketing use)
// --------------------------------------------------------------------
Route::get('/template-preview/{template}', [\App\Http\Controllers\SampleSiteController::class, 'templatePreview'])
    ->name('template.preview');

// --------------------------------------------------------------------
// Custom domain fallback — any host not matched above resolves to a
// restaurant site via its custom_domain column.
// --------------------------------------------------------------------
Route::fallback(function (\Illuminate\Http\Request $request) {
    $host = strtolower($request->getHost());

    // Skip our own hostnames — they should be matched by the domain-scoped routes above.
    $known = ['menudirect.ca', 'www.menudirect.ca', 'portal.menudirect.ca', 'localhost', '127.0.0.1', '192.168.23.65'];
    if (in_array($host, $known) || str_ends_with($host, '.menudirect.ca')) {
        abort(404);
    }

    $controller = app(\App\Http\Controllers\SampleSiteController::class);
    $path = $request->path() === '/' ? null : $request->path();

    return $controller->showByDomain($request, $path);
});
