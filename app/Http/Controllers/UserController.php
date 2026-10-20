<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Notifications\AuditSystemNotification;
use App\Support\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('users.index', ['users' => User::query()->orderByRaw('requested_role IS NULL')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->safe()->except('password_confirmation'));

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user, NotificationDispatcher $notifications): RedirectResponse
    {
        $data = $request->safe()->except('password_confirmation');
        $hasRoleRequest = $user->requested_role !== null;

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['requested_role'] = null;
        $user->update($data);

        if ($hasRoleRequest) {
            $notifications->send([$user], new AuditSystemNotification(
                'role_approved',
                'Akun disetujui',
                'Master Admin telah mengaktifkan akun Anda dengan role '.str_replace('_', ' ', ucfirst($user->role)).'.',
                route('dashboard', [], false),
            ));
        }

        return redirect()->route('users.index')->with('success', 'Data pengguna diperbarui.');
    }

    public function approveRoleRequest(
        Request $request,
        User $user,
        NotificationDispatcher $notifications,
    ): RedirectResponse {
        abort_if($user->requested_role === null, 422, 'Tidak ada permintaan role yang perlu disetujui.');

        $validated = $request->validate([
            'role' => ['required', 'in:admin,lead_audit,auditor'],
        ]);

        $user->update(['role' => $validated['role'], 'requested_role' => null]);

        $notifications->send([$user], new AuditSystemNotification(
            'role_approved',
            'Akun disetujui',
            'Master Admin telah menyetujui akun Anda dengan role '.str_replace('_', ' ', ucfirst($validated['role'])).'.',
            route('dashboard', [], false),
        ));

        return redirect()->route('users.index')->with('success', 'Akun berhasil disetujui dan role telah ditetapkan.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna dihapus.');
    }
}
