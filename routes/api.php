<?php

use Illuminate\Support\Facades\Route;

// --------------------------------------------------------------------
// Default Sanctum-protected user route (from install:api)
// --------------------------------------------------------------------
Route::get('/user', function (\Illuminate\Http\Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --------------------------------------------------------------------
// MenuDirect lead intake (bearer-token authenticated; called by sos-tech.ca)
// --------------------------------------------------------------------
Route::post('/menudirect/leads', [\App\Http\Controllers\Api\MenudirectLeadController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.menudirect.leads.store');

// --------------------------------------------------------------------
// Stripe webhook relay (Manager validates Stripe signature, forwards here)
// --------------------------------------------------------------------
Route::post('/webhooks/stripe-account-updated', [\App\Http\Controllers\Api\StripeWebhookRelayController::class, 'accountUpdated'])
    ->name('webhooks.stripe.account-updated');
Route::post('/webhooks/stripe-payment-succeeded', [\App\Http\Controllers\Api\StripeWebhookRelayController::class, 'paymentIntentSucceeded'])
    ->name('webhooks.stripe.payment-succeeded');
Route::post('/webhooks/stripe-payment-failed', [\App\Http\Controllers\Api\StripeWebhookRelayController::class, 'paymentIntentFailed'])
    ->name('webhooks.stripe.payment-failed');

// --------------------------------------------------------------------
// Demo kitchen API (public, no auth — demo sites only)
// --------------------------------------------------------------------
Route::get('/demo-kitchen/{slug}/orders', [\App\Http\Controllers\Api\DemoKitchenController::class, 'orders'])
    ->middleware('throttle:60,1')
    ->name('api.demo-kitchen.orders');
Route::post('/demo-kitchen/{slug}/orders/{order}/status', [\App\Http\Controllers\Api\DemoKitchenController::class, 'updateStatus'])
    ->middleware('throttle:30,1')
    ->name('api.demo-kitchen.orders.status');
Route::get('/demo-kitchen/{slug}/menu', [\App\Http\Controllers\Api\DemoKitchenController::class, 'menu'])
    ->middleware('throttle:60,1')
    ->name('api.demo-kitchen.menu');
Route::post('/demo-kitchen/{slug}/orders', [\App\Http\Controllers\Api\DemoKitchenController::class, 'storeOrder'])
    ->middleware('throttle:30,1')
    ->name('api.demo-kitchen.orders.store');
Route::post('/demo-kitchen/{slug}/orders/{order}/add-items', [\App\Http\Controllers\Api\DemoKitchenController::class, 'addItemsToOrder'])
    ->middleware('throttle:30,1')
    ->name('api.demo-kitchen.orders.add-items');

// --------------------------------------------------------------------
// Staff tablet order API (authenticated via staff session cookie)
// --------------------------------------------------------------------
Route::middleware('web', 'staff.auth')->prefix('staff')->group(function () {
    Route::get('/orders', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'index'])->name('api.staff.orders');
    Route::post('/orders', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'store'])->name('api.staff.orders.store');
    Route::get('/menu', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'menu'])->name('api.staff.menu');
    Route::post('/orders/{order}/status', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'updateStatus'])->name('api.staff.orders.status');
    Route::post('/orders/{order}/add-items', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'addItems'])->name('api.staff.orders.add-items');
    Route::get('/orders/{order}/audit', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'auditLog'])->name('api.staff.orders.audit');
    Route::get('/shift-summary', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'shiftSummary'])->name('api.staff.shift-summary');
    Route::post('/close-shift', [\App\Http\Controllers\Api\StaffOrdersApiController::class, 'closeShift'])->name('api.staff.close-shift');
});

// --------------------------------------------------------------------
// Demo Sandbox API
// --------------------------------------------------------------------
Route::post('/demo/create', [\App\Http\Controllers\Api\DemoController::class, 'create'])
    ->middleware('throttle:10,1')
    ->name('api.demo.create');
Route::get('/demo/{token}/status', [\App\Http\Controllers\Api\DemoController::class, 'status'])
    ->middleware('throttle:60,1')
    ->name('api.demo.status');

// --------------------------------------------------------------------
// Public Restaurant Site API (consumed by public-site rendering in Phase C)
// --------------------------------------------------------------------
Route::get('/restaurant-sites', [\App\Http\Controllers\Api\RestaurantApiController::class, 'index'])
    ->middleware('throttle:120,1')
    ->name('api.restaurant.index');
Route::get('/restaurant/{slug}', [\App\Http\Controllers\Api\RestaurantApiController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.show');
Route::get('/restaurant-domain/{domain}', [\App\Http\Controllers\Api\RestaurantApiController::class, 'resolveByDomain'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.domain');

// --------------------------------------------------------------------
// Food Order API
// --------------------------------------------------------------------
Route::post('/restaurant/{slug}/orders', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'store'])
    ->name('api.restaurant.orders.store');
Route::post('/restaurant/{slug}/customer-lookup', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'lookupCustomer'])
    ->middleware('throttle:30,1')
    ->name('api.restaurant.customer.lookup');
Route::get('/restaurant/{slug}/scheduling', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'scheduling'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.scheduling');
Route::get('/orders/{token}', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('api.orders.show');
Route::get('/orders/{token}/status', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'status'])
    ->middleware('throttle:120,1')
    ->name('api.orders.status');
Route::post('/restaurant/{slug}/validate-delivery', [\App\Http\Controllers\Api\FoodOrderApiController::class, 'validateDeliveryAddress'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.validate-delivery');

// --------------------------------------------------------------------
// Reservation API
// --------------------------------------------------------------------
Route::get('/restaurant/{slug}/reservations/availability', [\App\Http\Controllers\Api\ReservationApiController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.reservations.availability');
Route::get('/restaurant/{slug}/reservations/dates', [\App\Http\Controllers\Api\ReservationApiController::class, 'dates'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.reservations.dates');
Route::post('/restaurant/{slug}/reservations', [\App\Http\Controllers\Api\ReservationApiController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.restaurant.reservations.store');
Route::get('/reservations/{token}', [\App\Http\Controllers\Api\ReservationApiController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('api.reservations.show');
Route::post('/reservations/{token}/cancel', [\App\Http\Controllers\Api\ReservationApiController::class, 'cancel'])
    ->middleware('throttle:10,1')
    ->name('api.reservations.cancel');

// --------------------------------------------------------------------
// Catering API
// --------------------------------------------------------------------
Route::get('/restaurant/{slug}/catering/packages', [\App\Http\Controllers\Api\CateringApiController::class, 'packages'])
    ->middleware('throttle:60,1')
    ->name('api.restaurant.catering.packages');
Route::post('/restaurant/{slug}/catering/inquiries', [\App\Http\Controllers\Api\CateringApiController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('api.restaurant.catering.store');

// --------------------------------------------------------------------
// Domain Check API (for restaurant ordering)
// --------------------------------------------------------------------
Route::get('/domain/check', [\App\Http\Controllers\Api\DomainCheckController::class, 'check'])
    ->middleware(['web', 'auth', 'throttle:30,1'])
    ->name('api.domain.check');
