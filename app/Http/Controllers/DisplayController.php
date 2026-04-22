<?php

namespace App\Http\Controllers;

use App\Models\Fight;
use App\Models\Setting;

class DisplayController extends Controller
{
    public function index()
    {
        // get the current active fight
        // priority: open > closed > pending
        $fight = Fight::whereIn('status', ['open', 'closed', 'pending'])
            ->orderByRaw("FIELD(status, 'open', 'closed', 'pending')")
            ->latest()
            ->first();

        // get the display title from settings
        $settings = Setting::first();
        $displayTitle = $settings ? $settings->display_title : 'Sabong Betting System';

        return view('display', compact('fight', 'displayTitle'));
    }
}
