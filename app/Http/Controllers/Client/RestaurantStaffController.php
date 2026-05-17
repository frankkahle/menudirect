<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Traits\AuthorizesRestaurantSite;
use App\Http\Controllers\Controller;
use App\Models\RestaurantSite;
use App\Models\RestaurantStaff;
use App\Mail\StaffInviteMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class RestaurantStaffController extends Controller
{
    use AuthorizesRestaurantSite;

    public function index(RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $staff = $site->staff()->orderBy('name')->get();

        return view('client.restaurant.staff.index', compact('site', 'staff'));
    }

    public function store(Request $request, RestaurantSite $site)
    {
        $this->authorizeSite($site);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:menudirect.restaurant_staff,email'],
            'role' => ['required', 'in:manager,staff,content'],
        ]);

        $staff = RestaurantStaff::create([
            'restaurant_site_id' => $site->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => true,
        ]);

        $token = $staff->generateInviteToken();

        try {
            Mail::to($staff->email)->send(new StaffInviteMail($staff, $token));
            return back()->with('status', "Invitation sent to {$staff->email}.");
        } catch (\Exception $e) {
            \Log::error("Failed to send staff invite: {$e->getMessage()}");
            return back()->with('status', "Staff account created but email could not be sent. Invite link: " . route('staff.invite.show', $token));
        }
    }

    public function resendInvite(RestaurantSite $site, RestaurantStaff $staff)
    {
        $this->authorizeSite($site);

        if ($staff->restaurant_site_id !== $site->id) {
            abort(403);
        }

        if ($staff->invite_accepted_at) {
            return back()->withErrors(['error' => 'This staff member has already accepted their invitation.']);
        }

        $token = $staff->generateInviteToken();

        try {
            Mail::to($staff->email)->send(new StaffInviteMail($staff, $token));
            return back()->with('status', "Invitation resent to {$staff->email}.");
        } catch (\Exception $e) {
            return back()->with('status', "Invite link: " . route('staff.invite.show', $token));
        }
    }

    public function update(Request $request, RestaurantSite $site, RestaurantStaff $staff)
    {
        $this->authorizeSite($site);

        if ($staff->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:manager,staff,content'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $staff->update([
            'name' => $data['name'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "{$staff->name} updated.");
    }

    public function destroy(RestaurantSite $site, RestaurantStaff $staff)
    {
        $this->authorizeSite($site);

        if ($staff->restaurant_site_id !== $site->id) {
            abort(403);
        }

        $name = $staff->name;
        $staff->delete();

        return back()->with('status', "{$name} removed.");
    }
}
