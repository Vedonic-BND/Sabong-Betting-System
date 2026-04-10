<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the owner's profile page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        $user = auth()->user();
        return view('owner.profile.show', compact('user'));
    }

    /**
     * Update the owner's profile information.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
        ]);

        $user->update($validated);

        return redirect()->route('owner.profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the owner's password.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('owner.profile.show')
            ->with('success', 'Password updated successfully.');
    }
}
