@extends('layouts.app')

@section('title', 'Add User')

@section('content')

<div class="max-w-lg">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('owner.users.index') }}"
            class="text-gray-400 hover:text-gray-600 transition text-sm">
            ← Back
        </a>
        <h2 class="text-2xl font-bold text-gray-800">Add User</h2>
    </div>

    <div class="bg-white rounded-xl shadow p-6">

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('owner.users.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800"
                    placeholder="Full name" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800"
                    placeholder="Username" />
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800">
                    <option value="">Select role</option>
                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="teller" {{ old('role') === 'teller' ? 'selected' : '' }}>Teller</option>
                    <option value="runner" {{ old('role') === 'runner' ? 'selected' : '' }}>Runner</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800"
                    placeholder="Min 6 characters" />
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800"
                    placeholder="Repeat password" />
            </div>

            <button type="submit"
                class="w-full bg-gray-900 hover:bg-gray-700 text-white font-semibold
                       py-2 rounded-lg transition text-sm">
                Create User
            </button>

        </form>
    </div>
</div>

@endsection
