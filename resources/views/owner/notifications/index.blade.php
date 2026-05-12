@extends('layouts.app')

@section('title', 'Requests')

@section('content')

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Requests & Assignments</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Monitor real-time teller assistance requests and runner assignments
        </p>
    </div>
</div>

<!-- Successful Assignments Section -->
@if($successfulAssignments->count() > 0)
    <div class="mb-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">✅ Successful Assignments</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-green-50 dark:bg-green-900/20 border-b-2 border-green-300 dark:border-green-700">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-green-700 dark:text-green-400">Runner</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-green-700 dark:text-green-400">Assigned to Teller</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-green-700 dark:text-green-400">Time Assigned</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($successfulAssignments as $assignment)
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-green-50 dark:hover:bg-green-900/10 transition">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">
                                    {{ json_decode($assignment->data, true)['runner_name'] ?? 'Unknown Runner' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ json_decode($assignment->data, true)['teller_name'] ?? 'Unknown Teller' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $assignment->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Active Assistance Requests Section -->
@if($assistanceNotifications->count() > 0)
    <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">🔔 Active Assistance Requests</h3>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700 border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Teller</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Request Type</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Message</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Runner Assigned</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assistanceNotifications as $item)
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">
                                {{ $item->teller?->name ?? 'Unknown Teller' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ ucfirst(str_replace('_', ' ', $item->request_type)) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                @if($item->custom_message)
                                    <span class="italic">{{ $item->custom_message }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold">
                                @if($item->assigned_runner)
                                    <span class="text-green-600 dark:text-green-400">{{ $item->assigned_runner['name'] ?? 'Unknown Runner' }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($item->is_active)
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                        Assigned
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $item->created_at->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto-refresh every 3 seconds
        setInterval(function() {
            location.reload();
        }, 3000);
    </script>
@else
    @if($successfulAssignments->count() == 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="text-5xl mb-4">🔔</div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No Activity</h3>
            <p class="text-gray-500 dark:text-gray-400">
                No assistance requests or assignments at this time. The system is running smoothly!
            </p>
        </div>
    @endif
@endif

@endsection
