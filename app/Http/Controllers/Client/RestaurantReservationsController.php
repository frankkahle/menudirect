<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Jobs\SendReservationNotificationsJob;
use App\Models\Reservation;
use App\Models\RestaurantSite;
use Illuminate\Http\Request;

class RestaurantReservationsController extends Controller
{
    use AuthorizesRestaurantSite;

    /**
     * List reservations for a restaurant site.
     */
    public function index(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $query = $site->reservations()->orderBy('reservation_date', 'desc')->orderBy('reservation_time', 'desc');

        // Filter by date
        if ($request->filled('date')) {
            $query->forDate($request->input('date'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Default: show upcoming if no filters
        if (!$request->filled('date') && !$request->filled('status')) {
            $query->where('reservation_date', '>=', today())
                ->orderBy('reservation_date', 'asc')
                ->orderBy('reservation_time', 'asc');
        }

        $reservations = $query->paginate(25);

        // Stats
        $todayCount = $site->reservations()->forDate(today())->active()->count();
        $todayCovers = $site->reservations()->forDate(today())->active()->sum('party_size');
        $pendingCount = $site->reservations()->pending()->upcoming()->count();
        $upcomingCount = $site->reservations()->upcoming()->count();

        return view('client.restaurant.reservations.index', compact(
            'site', 'reservations', 'todayCount', 'todayCovers', 'pendingCount', 'upcomingCount'
        ));
    }

    /**
     * Show reservation details.
     */
    public function show(RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        return view('client.restaurant.reservations.show', compact('site', 'reservation'));
    }

    /**
     * Confirm a pending reservation.
     */
    public function confirm(RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        if (!$reservation->confirm()) {
            return back()->with('error', 'This reservation cannot be confirmed.');
        }

        SendReservationNotificationsJob::dispatch($reservation, 'status_update');

        return back()->with('status', 'Reservation confirmed.');
    }

    /**
     * Mark reservation as seated.
     */
    public function seat(RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        if (!$reservation->seat()) {
            return back()->with('error', 'This reservation cannot be marked as seated.');
        }

        return back()->with('status', 'Reservation marked as seated.');
    }

    /**
     * Mark reservation as completed.
     */
    public function complete(RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        if (!$reservation->complete()) {
            return back()->with('error', 'This reservation cannot be completed.');
        }

        return back()->with('status', 'Reservation completed.');
    }

    /**
     * Cancel a reservation.
     */
    public function cancel(Request $request, RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        $reason = $request->input('reason', 'Cancelled by restaurant');

        if (!$reservation->cancel($reason, 'restaurant')) {
            return back()->with('error', 'This reservation cannot be cancelled.');
        }

        SendReservationNotificationsJob::dispatch($reservation, 'status_update');

        return back()->with('status', 'Reservation cancelled.');
    }

    /**
     * Mark reservation as no-show.
     */
    public function noShow(RestaurantSite $site, Reservation $reservation)
    {
        $this->authorizeSite($site);
        $this->authorizeReservation($site, $reservation);

        if (!$reservation->markNoShow()) {
            return back()->with('error', 'This reservation cannot be marked as no-show.');
        }

        return back()->with('status', 'Reservation marked as no-show.');
    }

    /**
     * Verify the reservation belongs to the site.
     */
    protected function authorizeReservation(RestaurantSite $site, Reservation $reservation): void
    {
        if ($reservation->restaurant_site_id !== $site->id) {
            abort(404);
        }
    }
}
