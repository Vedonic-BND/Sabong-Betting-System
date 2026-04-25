@extends('layouts.app')

@section('title', 'Users')

@section('content')

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Users</h2>
    <a href="{{ route('owner.users.create') }}"
        class="bg-gray-900 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        + Add User
    </a>
</div>

{{-- SUCCESS --}}
@if (session('success'))
    <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-100 text-sm rounded-lg px-4 py-3 mb-5">
        {{ session('success') }}
    </div>
@endif

{{-- TABLE --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <tr>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Name</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Username</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Role</th>
                <th class="text-left px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Created</th>
                <th class="text-right px-6 py-3 text-gray-500 dark:text-gray-400 font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($users as $user)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <td class="px-6 py-4 font-medium text-gray-800 dark:text-white">{{ $user->name }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $user->username }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $user->role === 'admin' ? 'bg-blue-100 text-blue-700' : ($user->role === 'teller' ? 'bg-yellow-100 text-yellow-700' : 'bg-purple-100 text-purple-700') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">
                        {{ $user->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                        <a href="{{ route('owner.users.edit', $user) }}"
                            class="text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded transition">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('owner.users.destroy', $user) }}"
                            onsubmit="return confirm('Delete {{ $user->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded transition">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                        No users found. Add your first admin, teller, or runner.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
