@extends('layouts.guest')

@section('title', 'Owner Login')

@section('content')
<div class="w-full max-w-md px-6">

    {{-- LOGO / TITLE --}}
    <div class="text-center mb-8">
        <div class="text-5xl mb-3">🐓</div>
        <h1 class="text-white text-2xl font-bold">Sabong Betting System</h1>
        <p class="text-gray-400 text-sm mt-1">Owner Panel</p>
    </div>

    {{-- CARD --}}
    <div class="bg-white rounded-2xl shadow-xl p-8">

        {{-- ERROR --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- FORM --}}
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Username
                </label>
                <input
                    type="text"
                    name="username"
                    value="{{ old('username') }}"
                    required
                    autofocus
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-transparent
                           @error('username') border-red-400 @enderror"
                    placeholder="Enter username"
                />
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password
                </label>
                <input
                    type="password"
                    name="password"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-transparent"
                    placeholder="Enter password"
                />
            </div>

            <button
                type="submit"
                class="w-full bg-gray-900 hover:bg-gray-700 text-white font-semibold
                       py-2 rounded-lg transition text-sm">
                Login
            </button>

        </form>
    </div>

    <p class="text-center text-gray-500 text-xs mt-6">
        Restricted access — Owner only
    </p>
</div>
@endsection
