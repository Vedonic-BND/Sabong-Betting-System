@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Audit Logs</h2>
</div>

{{-- FILTER BAR --}}
<div class="bg-white rounded-xl shadow p-4 mb-6">
    <form method="GET" action="{{ route('owner.audit-logs.index') }}"
        class="flex flex-wrap gap-3 items-end">

        <div>
            <label class="block text-xs text-gray-500 mb-1">Search action</label>
            <input type="text" name="action" value="{{ request('action') }}"
                placeholder="e.g. created_user"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-800" />
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">User</label>
            <input type="text" name="user" value="{{ request('user') }}"
                placeholder="Username"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-800" />
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Date</label>
            <input type="date" name="date" value="{{ request('date') }}"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm
                       focus:outline-none focus:ring-2 focus:ring-gray-800" />
        </div>

        <button type="submit"
            class="bg-gray-900 hover:bg-gray-700 text-white text-sm px-4 py-2 rounded-lg transition">
            Filter
        </button>

        @if (request()->anyFilled(['action', 'user', 'date']))
            <a href="{{ route('owner.audit-logs.index') }}"
                class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2 transition">
                Clear
            </a>
        @endif

    </form>
</div>

{{-- TABLE --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Time</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Action</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Target</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Details</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">IP Address</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($logs as $log)
                <tr class="hover:bg-gray-50 transition">

                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}
                    </td>

                    <td class="px-6 py-4">
                        <span class="font-medium text-gray-800">
                            {{ $log->user->name ?? '—' }}
                        </span>
                        <span class="text-xs text-gray-400 block">
                            {{ $log->user->role ?? '' }}
                        </span>
                    </td>

                    <td class="px-6 py-4">
                        @php
                            $actionColors = [
                                'created_user'    => 'bg-green-100 text-green-700',
                                'updated_user'    => 'bg-yellow-100 text-yellow-700',
                                'deleted_user'    => 'bg-red-100 text-red-700',
                                'declared_winner' => 'bg-blue-100 text-blue-700',
                            ];
                            $color = $actionColors[$log->action]
                                ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $color }}">
                            {{ str_replace('_', ' ', $log->action) }}
                        </span>
                    </td>

                    <td class="px-6 py-4 text-gray-600">
                        @if ($log->target_type && $log->target_id)
                            {{ ucfirst($log->target_type) }} #{{ $log->target_id }}
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-gray-600 max-w-xs">
                        @if ($log->payload)
                            <div class="text-xs text-gray-500 space-y-0.5">
                                @foreach ($log->payload as $key => $value)
                                    <div>
                                        <span class="text-gray-400">{{ $key }}:</span>
                                        {{ $value }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-gray-500 text-xs">
                        {{ $log->ip_address ?? '—' }}
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                        No audit logs found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    @if ($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    @endif

</div>

@endsection
