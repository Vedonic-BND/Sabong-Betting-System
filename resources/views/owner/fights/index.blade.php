@extends('layouts.app')

@section('title', 'Fights')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Fights</h2>
</div>

{{-- FILTER BAR --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
    <div class="flex flex-wrap gap-3 items-end">
        <form method="GET" action="{{ route('owner.fights.index') }}"
            class="flex flex-wrap gap-3 items-end">

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Fight Number</label>
                <input type="text" name="fight_number" value="{{ request('fight_number') }}"
                    placeholder="e.g. 12345"
                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                        focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
            </div>

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Winner</label>
                <input type="text" name="winner" value="{{ request('winner') }}"
                    placeholder="e.g. Meron"
                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                        focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
            </div>

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg px-3 py-2 text-sm
                        focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-600" />
            </div>

            <button type="submit"
                class="bg-gray-900 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white text-sm px-4 py-2 rounded-lg transition">
                Filter
            </button>

            @if (request()->anyFilled(['fight_number', 'winner', 'date']))
                <a href="{{ route('owner.fights.index') }}"
                    class="text-sm text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-400 px-2 py-2 transition">
                    Clear
                </a>
            @endif

        </form>

        {{-- export button --}}
        <a href="{{ route('owner.fights.export', request()->query()) }}"
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
</div>

{{-- TABLE --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">#</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Fight No.</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Status</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Winner</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Commission</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Created By</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Date</th>
                <th class="text-right px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($fights as $fight)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">{{ $fight->id }}</td>
                    <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">{{ $fight->fight_number }}</td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'pending'   => 'bg-gray-100 text-gray-600',
                                'open'      => 'bg-green-100 text-green-700',
                                'closed'    => 'bg-yellow-100 text-yellow-700',
                                'done'      => 'bg-blue-100 text-blue-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $statusColors[$fight->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($fight->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if ($fight->winner)
                            @php
                                $winnerColors = [
                                    'meron'     => 'bg-red-100 text-red-700',
                                    'wala'      => 'bg-blue-100 text-blue-700',
                                    'draw'      => 'bg-gray-100 text-gray-600',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                {{ $winnerColors[$fight->winner] ?? '' }}">
                                {{ ucfirst($fight->winner) }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $fight->commission_rate }}%</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $fight->creator->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                        {{ $fight->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('owner.fights.show', $fight) }}"
                            class="text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded transition">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                        No fights recorded yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    @if ($fights->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $fights->links() }}
        </div>
    @endif

</div>

@endsection
