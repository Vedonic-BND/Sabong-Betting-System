<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Events\SettingUpdated;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show()
    {
        $settings = Setting::firstOrCreate([], ['display_title' => 'Sabong Betting System']);
        return view('owner.settings.show', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'display_title' => 'required|string|max:255',
        ]);

        $settings = Setting::first();
        if (!$settings) {
            $settings = Setting::create(['display_title' => 'Sabong Betting System']);
        }

        $settings->update($validated);

        // Broadcast the update event
        broadcast(new SettingUpdated($settings));

        return redirect()->route('owner.settings.show')->with('success', 'Settings updated successfully');
    }
}
