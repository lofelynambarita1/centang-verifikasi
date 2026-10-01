<?php

namespace App\Http\Controllers;

use App\Notifications\AuditSystemNotification;
use App\Support\NotificationDispatcher;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function createRegistration(): View
    {
        return view('auth.register');
    }

    public function storeRegistration(Request $request, NotificationDispatcher $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:admin,lead_audit,auditor'],
        ]);

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'auditor',
            'requested_role' => $validated['role'],
        ]);

        $requestedRoleName = match ($validated['role']) {
            'admin' => 'Admin',
            'lead_audit' => 'Lead Auditor',
            default => 'Auditor',
        };

        $admins = User::query()
            ->where('role', 'admin')
            ->whereNull('requested_role')
            ->get();

        $notifications->send($admins, new AuditSystemNotification(
            'role_request',
            'Permintaan role baru',
            "{$newUser->name} mengajukan role {$requestedRoleName}.",
            route('users.index', [], false),
        ));

        return redirect()->route('login')->with('success', "Permintaan akun {$requestedRoleName} dikirim. Akun dapat digunakan setelah disetujui Admin.");
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau password tidak sesuai.'])->onlyInput('email');
        }

        if ($request->user()?->requested_role !== null) {
            Auth::logout();
            $request->session()->regenerate();

            return back()->withErrors(['email' => 'Pendaftaran akun Anda masih menunggu persetujuan Admin.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
