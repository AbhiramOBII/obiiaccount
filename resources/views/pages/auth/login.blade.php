<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $this->redirect(route('admin.dashboard'), navigate: true);
        } else {
            $this->addError('email', 'These credentials do not match our records.');
        }
    }
};
?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo-obii-kriationz.png') }}" alt="Obii KriationZ" class="h-10 mx-auto" />
            <p class="mt-3 text-sm text-gray-500">Web LLP &mdash; Accounts Portal</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Sign in to your account</h2>

            <form wire:submit="login" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        autocomplete="email"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors"
                        placeholder="admin@obiikz.com"
                    />
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        wire:model="password"
                        type="password"
                        id="password"
                        autocomplete="current-password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors"
                        placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                    />
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-primary-800 hover:bg-primary-700 text-white font-medium rounded-lg text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-75 cursor-wait"
                >
                    <span wire:loading.remove>Sign in</span>
                    <span wire:loading>Signing in...</span>
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-400">&copy; {{ date('Y') }} Obii KriationZ Web LLP. All rights reserved.</p>
    </div>
</div>
