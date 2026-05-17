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

<!-- Active Tellers Requesting Assistance (Grid View) -->
@if($availableTellers->count() > 0)
    <div class="mb-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">🤵‍♀️ Available Tellers</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($availableTellers as $item)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition cursor-pointer p-4 border-2 border-transparent hover:border-blue-400 dark:hover:border-blue-600"
                    onclick="loadAvailableRunners(); openAssignRunnerModal({{ $item->teller_id }}, '{{ $item->teller->name }}')">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-white">{{ $item->teller->name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Teller
                            </p>
                        </div>
                        <div class="text-2xl">👤</div>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Click to assign runner
                        </span>
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                            + Assign
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- Filter Bar for Successful Assignments -->
@if($successfulAssignments->count() > 0 || request()->anyFilled(['runner_name', 'teller_name', 'request_type', 'date_from', 'date_to']))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form method="GET" action="{{ route('owner.notifications.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Runner Name</label>
                    <input type="text" name="runner_name" value="{{ request('runner_name') }}"
                        placeholder="Search runner..."
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Teller Name</label>
                    <input type="text" name="teller_name" value="{{ request('teller_name') }}"
                        placeholder="Search teller..."
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Request Type</label>
                    <select name="request_type"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600">
                        <option value="">All Types</option>
                        <option value="assistance" {{ request('request_type') === 'assistance' ? 'selected' : '' }}>Assistance</option>
                        <option value="need_cash" {{ request('request_type') === 'need_cash' ? 'selected' : '' }}>Need Cash</option>
                        <option value="collect_cash" {{ request('request_type') === 'collect_cash' ? 'selected' : '' }}>Collect Cash</option>
                        <option value="other" {{ request('request_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
                </div>

                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                            focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
                </div>
            </div>

            <div class="flex flex-wrap gap-2 justify-start items-center">
                <button type="submit"
                    class="bg-gray-900 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition">
                    Filter
                </button>

                @if (request()->anyFilled(['runner_name', 'teller_name', 'request_type', 'date_from', 'date_to']))
                    <a href="{{ route('owner.notifications.index') }}"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-3 py-2 transition">
                        ✕ Clear Filters
                    </a>
                @endif

                {{-- export button --}}
                <a href="{{ route('owner.notifications.export', request()->query()) }}"
                    class="flex items-center gap-2 bg-green-600 hover:bg-green-700
                           text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export CSV
                </a>
            </div>
        </form>
    </div>
@endif

<!-- Successful Assignments Section -->
@if($successfulAssignments->count() > 0)
    <div class="mb-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4">✅ Successful Assignments</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Runner</th>
                            <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Assigned to Teller</th>
                            <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Request Type</th>
                            <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Time Assigned</th>
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
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold
                                        {{
                                            match(json_decode($assignment->data, true)['request_type'] ?? '') {
                                                'assistance' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
                                                'need_cash' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
                                                'collect_cash' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400',
                                                'other' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
                                                default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'
                                            }
                                        }}">
                                        {{ ucfirst(str_replace('_', ' ', json_decode($assignment->data, true)['request_type'] ?? 'N/A')) }}
                                    </span>
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
@else
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
        <div class="text-5xl mb-4">✅</div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">No Assignments</h3>
        <p class="text-gray-500 dark:text-gray-400">
            No completed assignments yet. Assignments will appear here when runners accept requests.
        </p>
    </div>
@endif

<!-- Assign Runner Modal -->
<div id="assignRunnerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50" style="display: none;">
    <div class="fixed inset-0 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Assign Runner to Teller</h3>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Teller</label>
            <input type="text" id="tellerNameDisplay" disabled class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-white rounded border border-gray-300 dark:border-gray-600">
            <input type="hidden" id="selectedTellerId">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Request Type</label>
            <select id="requestTypeSelect" class="w-full px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded border border-gray-300 dark:border-gray-600">
                <option value="assistance">Assistance</option>
                <option value="need_cash">Need Cash</option>
                <option value="collect_cash">Collect Cash</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Select Runner</label>
            <select id="runnerSelect" class="w-full px-3 py-2 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded border border-gray-300 dark:border-gray-600">
                <option value="">-- Choose a runner --</option>
            </select>
        </div>

        <div class="flex gap-3">
            <button onclick="closeAssignRunnerModal()"
                class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded transition">
                Cancel
            </button>
            <button onclick="assignRunner()"
                class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
                Assign
            </button>
        </div>
    </div>
    </div>
</div>

<script>
    let availableRunners = [];
    let isModalOpen = false; // Track modal state

    // Fetch available runners when page loads
    async function loadAvailableRunners() {
        try {
            const response = await fetch('/api/runners/available', {
                headers: {
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="csrf-token"]')?.content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                availableRunners = await response.json();
                populateRunnerSelect();
            }
        } catch (error) {
            console.error('Error loading runners:', error);
        }
    }

    function populateRunnerSelect() {
        const select = document.getElementById('runnerSelect');
        // Clear existing options except the default one
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }
        availableRunners.forEach(runner => {
            const option = document.createElement('option');
            option.value = runner.id;
            option.textContent = runner.name;
            select.appendChild(option);
        });
    }

    function openAssignRunnerModal(tellerId, tellerName) {
        isModalOpen = true; // Set flag to true
        document.getElementById('selectedTellerId').value = tellerId;
        document.getElementById('tellerNameDisplay').value = tellerName;
        document.getElementById('runnerSelect').value = '';
        document.getElementById('assignRunnerModal').style.display = 'block';
    }

    function closeAssignRunnerModal() {
        isModalOpen = false; // Set flag to false
        document.getElementById('assignRunnerModal').style.display = 'none';
    }

    async function assignRunner() {
        const tellerId = document.getElementById('selectedTellerId').value;
        const runnerId = document.getElementById('runnerSelect').value;
        const requestType = document.getElementById('requestTypeSelect').value;

        if (!runnerId) {
            alert('Please select a runner');
            return;
        }

        try {
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(`/owner/assign-runner/${tellerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    runner_id: runnerId,
                    request_type: requestType
                })
            });

            if (response.ok) {
                alert('Runner assigned successfully!');
                closeAssignRunnerModal();
                // Refresh the page to see updates
                location.reload();
            } else {
                const error = await response.json();
                alert('Error: ' + (error.message || 'Failed to assign runner'));
            }
        } catch (error) {
            console.error('Error assigning runner:', error);
            alert('Error assigning runner');
        }
    }

    // Load runners on page load
    document.addEventListener('DOMContentLoaded', loadAvailableRunners);
</script>

<script>
    // Real-time updates using fetch API with smooth refresh
    let lastUpdateTime = new Date();

    async function refreshRequests() {
        // Don't refresh if modal is open
        if (isModalOpen) {
            return;
        }

        try {
            const response = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const html = await response.text();
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');

                // Replace the main content section
                const currentContent = document.querySelector('[data-page-content]') || document.querySelector('main') || document.body;
                const newContent = newDoc.querySelector('[data-page-content]') || newDoc.querySelector('main') || newDoc.body;

                if (newContent && currentContent && newContent.innerHTML !== currentContent.innerHTML) {
                    currentContent.innerHTML = newContent.innerHTML;
                    lastUpdateTime = new Date();
                    console.log('Requests updated:', lastUpdateTime.toLocaleTimeString());
                }
            }
        } catch (error) {
            console.error('Error refreshing requests:', error);
        }
    }

    // Use WebSocket for real-time updates instead of polling every 2 seconds
    // The 2-second interval was causing excessive server load (30 requests/min per user)
    // WebSocket via Reverb provides real-time updates without polling
    // Uncomment below to enable polling as fallback if WebSocket is unavailable
    // setInterval(refreshRequests, 2000);

    // Also refresh on page visibility change
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            refreshRequests();  // Single refresh when tab becomes visible
        }
    });
</script>

@endsection
