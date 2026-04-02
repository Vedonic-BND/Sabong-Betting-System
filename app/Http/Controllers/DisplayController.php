<?php

namespace App\Http\Controllers;

use App\Models\Fight;

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

        return view('display', compact('fight'));
    }
}
