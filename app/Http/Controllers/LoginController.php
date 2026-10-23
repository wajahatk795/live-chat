<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use App\Models\Chat;
use App\Services\AgentPresenceService;
use App\Services\Ai\OfflineAgentAiHandoff;

class LoginController extends Controller
{
    public function returnFromImpersonation(Request $request, AgentPresenceService $presence)
    {
        $superAdminId = $request->session()->get('super_admin_impersonator_id')
            ?: $request->cookie('super_admin_impersonator_id');
        $superAdmin = $superAdminId ? User::find($superAdminId) : null;

        // Older impersonation sessions may not have retained the session marker;
        // this app has a single platform Super Admin account, so recover it directly.
        $superAdmin ??= User::where('role', 'super_admin')->first();

        if (!$superAdmin) {
            return redirect()->route('login')->withErrors([
                'email' => 'Super Admin account could not be found. Please sign in again.',
            ]);
        }

        $companyUser = auth()->user();
        $presence->clear($companyUser);
        emit_pusher_notification('agent-status', 'agent-offline', [
            'agent_id' => $companyUser->id,
            'brand_ids' => $companyUser->auth_brands ? $companyUser->auth_brands->pluck('id') : [],
        ]);

        Auth::login($superAdmin);
        $request->session()->forget([
            'super_admin_impersonator_id',
            'super_admin_company_id',
            'super_admin_impersonated_user_id',
        ]);
        Cookie::queue(Cookie::forget('super_admin_impersonator_id'));
        $request->session()->regenerate();

        return redirect()->route('admin.super-admin.companies.index')->with('success', 'You have returned to Super Admin.');
    }

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();

            if (auth()->user()->isSuperAdmin()) {
                return redirect()->route('admin.super-admin.companies.index');
            }

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials',
        ])->onlyInput('email');
    }



    public function logout(
        Request $request,
        AgentPresenceService $presence,
        OfflineAgentAiHandoff $aiHandoff
    ) {
        $user = auth()->user();
        $presence->clear($user);

        $pendingChats = Chat::whereIn('brand_id', $user->auth_brands()->pluck('brand.id'))
            ->where('status', 'open')
            ->where('ai_pending', true)
            ->where('ai_processing', false)
            ->whereNotNull('ai_pending_since')
            ->get();

        foreach ($pendingChats as $chat) {
            $aiHandoff->triggerIfNoOnlineAgents($chat);
        }

        emit_pusher_notification(
            'agent-status',
            'agent-offline',
            [
                'agent_id' => $user->id,
                'brand_ids' => $user->auth_brands
                    ? $user->auth_brands->pluck('id')
                    : []
            ]
        );

        $superAdminId = $request->session()->pull('super_admin_impersonator_id');
        if ($superAdminId) {
            Auth::loginUsingId($superAdminId);
            $request->session()->forget([
                'super_admin_company_id',
                'super_admin_impersonated_user_id',
            ]);
            $request->session()->regenerate();

            return redirect()->route('admin.super-admin.companies.index')
                ->with('success', 'You have returned to Super Admin.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
