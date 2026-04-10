@extends('layouts.app')

@section('title', 'Profile')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Profile Settings</h2>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Manage your account information and password</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800 font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-green-800 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Profile Information --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Account Information</h3>

        <form method="POST" action="{{ route('owner.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Full Name
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-500
                               @error('name') border-red-500 @enderror"
                    />
                    @error('name')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Username
                    </label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username', $user->username) }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-500
                               @error('username') border-red-500 @enderror"
                    />
                    @error('username')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Role
                    </label>
                    <input
                        type="text"
                        value="{{ ucfirst($user->role) }}"
                        disabled
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                               bg-gray-50 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                    />
                </div>
            </div>

            <button
                type="submit"
                class="mt-6 bg-gray-900 hover:bg-gray-700 text-white font-medium
                       px-4 py-2 rounded-lg transition">
                Save Changes
            </button>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Change Password</h3>

        <form method="POST" action="{{ route('owner.profile.update-password') }}">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Current Password
                    </label>
                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-500
                               @error('current_password') border-red-500 @enderror"
                    />
                    @error('current_password')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        New Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-500
                               @error('password') border-red-500 @enderror"
                    />
                    @error('password')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                        Password must be at least 8 characters and contain uppercase, lowercase, and numbers.
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Confirm Password
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                               focus:outline-none focus:ring-2 focus:ring-gray-800 dark:focus:ring-gray-500
                               @error('password_confirmation') border-red-500 @enderror"
                    />
                    @error('password_confirmation')
                        <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button
                type="submit"
                class="mt-6 bg-gray-900 hover:bg-gray-700 text-white font-medium
                       px-4 py-2 rounded-lg transition">
                Update Password
            </button>
        </form>
    </div>
</div>

@endsection
