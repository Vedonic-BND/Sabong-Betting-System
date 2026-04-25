<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereIn('role', ['admin', 'teller', 'runner'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('owner.users.index', compact('users'));
    }

    public function create()
    {
        return view('owner.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'role'     => ['required', 'in:admin,teller,runner'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        AuditLogger::log(
            'created_user',
            'user',
            $user->id,
            ['name' => $user->name, 'role' => $user->role]
        );

        return redirect()->route('owner.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        // prevent editing owner accounts
        if ($user->role === 'owner') {
            abort(403);
        }

        return view('owner.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === 'owner') {
            abort(403);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::min(6)],
            'role'     => ['required', 'in:admin,teller,runner'],
        ]);

        $user->name     = $validated['name'];
        $user->username = $validated['username'];
        $user->role     = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        AuditLogger::log(
            'updated_user',
            'user',
            $user->id,
            ['name' => $user->name, 'role' => $user->role]
        );

        return redirect()->route('owner.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'owner') {
            abort(403);
        }

        AuditLogger::log(
            'deleted_user',
            'user',
            $user->id,
            ['name' => $user->name, 'role' => $user->role]
        );

        $user->delete();

        return redirect()->route('owner.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
