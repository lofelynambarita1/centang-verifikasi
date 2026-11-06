<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Notifications\AuditSystemNotification;
use App\Support\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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

    public function store(StoreUserRequest $request, NotificationDispatcher $notifications): RedirectResponse
    {
        $user = User::create($request->safe()->except('password_confirmation'));
        $notifications->broadcast(new AuditSystemNotification(
            'user_created',
            'Pengguna baru ditambahkan',
            "{$request->user()->name} menambahkan pengguna {$user->name}.",
            route('notifications.index', [], false),
            sendMail: false,
        ));

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
            $notifications->broadcast(new AuditSystemNotification(
                'role_approved',
                'Permintaan role disetujui',
                "Admin {$request->user()->name} menyetujui permintaan role {$user->name} menjadi ".str_replace('_', ' ', ucfirst($user->role)).'.',
                route('notifications.index', [], false),
                sendMail: false,
            ));
        } else {
            $notifications->broadcast(new AuditSystemNotification(
                'user_updated',
                'Data pengguna diperbarui',
                "{$request->user()->name} memperbarui data pengguna {$user->name}.",
                route('notifications.index', [], false),
                sendMail: false,
            ));
        }

        return redirect()->route('users.index')->with('success', 'Data pengguna diperbarui.');
    }

    public function approveRoleRequest(User $user, NotificationDispatcher $notifications): RedirectResponse
    {
        abort_if($user->requested_role === null, 422, 'Tidak ada permintaan role yang perlu disetujui.');

        $requestedRole = $user->requested_role;
        $user->update(['role' => $requestedRole, 'requested_role' => null]);

        $notifications->broadcast(new AuditSystemNotification(
            'role_approved',
            'Permintaan role disetujui',
            'Admin '.auth()->user()->name." menyetujui permintaan role {$user->name} menjadi ".str_replace('_', ' ', ucfirst($requestedRole)).'.',
            route('notifications.index', [], false),
            sendMail: false,
        ));

        return redirect()->route('users.index')->with('success', 'Permintaan role berhasil disetujui.');
    }

    public function destroy(User $user, NotificationDispatcher $notifications): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'Akun yang sedang digunakan tidak dapat dihapus.');

        $userName = $user->name;
        $user->delete();
        $notifications->broadcast(new AuditSystemNotification(
            'user_deleted',
            'Pengguna dihapus',
            auth()->user()->name." menghapus akun pengguna {$userName}.",
            route('notifications.index', [], false),
            sendMail: false,
        ));

        return redirect()->route('users.index')->with('success', 'Pengguna dihapus.');
    }
}
