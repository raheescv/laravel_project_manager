<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('user.index');
    }

    public function employee(): View
    {
        return view('user.employee');
    }

    public function employeeCommission(): View
    {
        return view('user.employee-commission');
    }

    public function view($id): View
    {
        return view('user.view', compact('id'));
    }

    public function get(Request $request)
    {
        $list = (new User())->getDropDownList($request->all());

        return response()->json($list);
    }

    /**
     * Return an impersonating admin to their own account.
     *
     * Deliberately not gated by 'user.impersonate': the session is currently
     * authenticated as the *target* user, who normally has no such permission.
     * Holding the impersonator id in the session is what authorises this.
     */
    public function leaveImpersonation(Request $request, ImpersonationService $impersonation): RedirectResponse
    {
        abort_unless($impersonation->isImpersonating(), 403, 'You are not impersonating anyone.');

        $impersonatedId = Auth::id();

        if (! $impersonation->stop()) {
            // The original account is gone; staying signed in as the target would
            // turn a lapsed impersonation into a real login.
            $impersonation->forget();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your original account no longer exists. Please sign in again.');
        }

        return redirect()->route('users::view', $impersonatedId)->with('success', 'You are back in your own account.');
    }
}
