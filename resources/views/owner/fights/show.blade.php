@extends('layouts.app')

@section('title', 'Fight Details')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('owner.fights.index') }}"
        class="text-gray-400 hover:text-gray-600 transition text-sm">
        ← Back
    </a>
    <h2 class="text-2xl font-bold text-gray-800">
        Fight #{{ $fight->fight_number }}
    </h2>
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
        {{ $statusColors[$fight->status] ?? '' }}">
        {{ ucfirst($fight->status) }}
    </span>
</div>

{{-- SUMMARY CARDS --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-xs text-gray-500">Total Bets</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $summary['total_bets'] }}</p>
    </div>

    <div class="bg-white rounded-xl shadow p-5">
        <p class="text-xs text-gray-500">Total Amount</p>
        <p class="text-2xl font-bold text-gray-800 mt-1">
            ₱{{ number_format($summary['total_amount'], 2) }}
        </p>
    </div>

    <div class="bg-red-50 rounded-xl shadow p-5">
        <p class="text-xs text-red-400">Meron Total</p>
        <p class="text-2xl font-bold text-red-700 mt-1">
            ₱{{ number_format($summary['meron_total'], 2) }}
        </p>
    </div>

    <div class="bg-blue-50 rounded-xl shadow p-5">
        <p class="text-xs text-blue-400">Wala Total</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">
            ₱{{ number_format($summary['wala_total'], 2) }}
        </p>
    </div>

</div>

{{-- FIGHT INFO --}}
<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Fight Info</h3>
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <p class="text-gray-400">Created By</p>
            <p class="font-medium text-gray-800">{{ $fight->creator->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-gray-400">Commission Rate</p>
            <p class="font-medium text-gray-800">{{ $fight->commission_rate }}%</p>
        </div>
        <div>
            <p class="text-gray-400">Winner</p>
            <p class="font-medium text-gray-800">
                {{ $fight->winner ? ucfirst($fight->winner) : '—' }}
            </p>
        </div>
        <div>
            <p class="text-gray-400">Date</p>
            <p class="font-medium text-gray-800">
                {{ $fight->created_at->format('M d, Y h:i A') }}
            </p>
        </div>
    </div>
</div>

{{-- BETS TABLE --}}
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Bets</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Teller</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Side</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Amount</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Payout</th>
                <th class="text-left px-6 py-3 text-gray-500 font-medium">Time</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($fight->bets as $bet)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-700">{{ $bet->teller->name ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $bet->side === 'meron' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ ucfirst($bet->side) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-800">
                        ₱{{ number_format($bet->amount, 2) }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($bet->payout)
                            <span class="text-green-700 font-medium">
                                ₱{{ number_format($bet->payout->net_payout, 2) }}
                            </span>
                            <span class="text-xs text-gray-400 ml-1">
                                ({{ ucfirst($bet->payout->status) }})
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">
                        {{ \Carbon\Carbon::parse($bet->created_at)->format('h:i A') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                        No bets placed for this fight.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
