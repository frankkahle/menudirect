<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\RestaurantStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffAuthController extends Controller
{
    /**
     * Show staff login form.
     */
    public function showLogin()
    {
        return view('staff.auth.login');
    }

    /**
     * Handle staff login.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('staff')->attempt($credentials, $request->boolean('remember'))) {
            $staff = Auth::guard('staff')->user();

            if (!$staff->is_active) {
                Auth::guard('staff')->logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact the restaurant owner.',
                ]);
            }

            $staff->update(['last_login_at' => now()]);
            $request->session()->regenerate();

            return redirect()->intended(route('staff.dashboard'));
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    /**
     * Log out the staff user.
     */
    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('staff.login');
    }

    /**
     * Show accept-invite form (uses token from email).
     */
    public function showAcceptInvite(string $token)
    {
        $staff = RestaurantStaff::where('invite_token', $token)
            ->whereNull('invite_accepted_at')
            ->first();

        if (!$staff || !$staff->isInvitePending()) {
            return redirect()->route('staff.login')->withErrors([
                'email' => 'This invitation link is invalid or has expired. Please ask the restaurant owner to send a new invite.',
            ]);
        }

        return view('staff.auth.accept-invite', compact('staff', 'token'));
    }

    /**
     * Process invite acceptance — set password and log in.
     */
    public function acceptInvite(Request $request, string $token)
    {
        $staff = RestaurantStaff::where('invite_token', $token)
            ->whereNull('invite_accepted_at')
            ->first();

        if (!$staff || !$staff->isInvitePending()) {
            return redirect()->route('staff.login')->withErrors([
                'email' => 'This invitation link is invalid or has expired.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $staff->update([
            'password' => $request->password,
            'invite_token' => null,
            'invite_accepted_at' => now(),
            'last_login_at' => now(),
        ]);

        Auth::guard('staff')->login($staff);
        $request->session()->regenerate();

        return redirect()->route('staff.dashboard')
            ->with('status', 'Welcome! Your account is now active.');
    }
}
