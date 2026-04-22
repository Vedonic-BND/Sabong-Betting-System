@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Settings</h2>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 rounded">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('owner.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="display_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Display Title
                </label>
                <input
                    type="text"
                    id="display_title"
                    name="display_title"
                    value="{{ old('display_title', $settings->display_title) }}"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter display title"
                    required
                >
                <small class="text-gray-500 dark:text-gray-400">This title will appear in the live display header and public pages</small>
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 text-white font-bold py-2 px-4 rounded-lg transition"
            >
                Save Settings
            </button>
        </form>
    </div>
</div>
@endsection
