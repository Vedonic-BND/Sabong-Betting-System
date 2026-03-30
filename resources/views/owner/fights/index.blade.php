@extends('layouts.app')

@section('title', 'Fights')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Fights</h2>
</div>

{{-- TABLE --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">#</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Fight No.</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Winner</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Commission</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Created By</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($fights as $fight)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-400">{{ $fight->id }}</td>
                    <td class="px-6 py-4 font-medium text-gray-800">{{ $fight->fight_number }}</td>
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
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $fight->commission_rate }}%</td>
                    <td class="px-6 py-4 text-gray-600">{{ $fight->creator->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ $fight->created_at->format('M d, Y h:i A') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('owner.fights.show', $fight) }}"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded transition">
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                        No fights recorded yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PAGINATION --}}
    @if ($fights->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $fights->links() }}
        </div>
    @endif

</div>

@endsection
