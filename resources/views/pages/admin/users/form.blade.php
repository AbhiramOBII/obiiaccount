<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $role_id = null;
    public bool $is_active = true;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $user = User::findOrFail($id);
            $this->userId   = $user->id;
            $this->name     = $user->name;
            $this->email    = $user->email;
            $this->role_id  = $user->role_id;
            $this->is_active = $user->is_active;
        } else {
            $defaultRole = Role::where('slug', 'user')->orWhere('slug', 'staff')->first()
                ?? Role::first();
            $this->role_id = $defaultRole?->id;
        }
    }

    public function save(): void
    {
        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'role_id'   => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ];

        if ($this->userId) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        } else {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules, [
            'password.confirmed' => 'The passwords do not match.',
            'password.min'       => 'Password must be at least 8 characters.',
        ]);

        $data = [
            'name'      => $this->name,
            'email'     => $this->email,
            'role_id'   => $this->role_id,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
            session()->flash('success', 'User updated successfully.');
        } else {
            User::create($data);
            session()->flash('success', 'User created successfully.');
        }

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'roles' => \App\Models\Role::orderByRaw("is_system DESC")->orderBy('name')->get(),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.users.index') }}" class="p-2 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $userId ? 'Edit User' : 'Add User' }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $userId ? 'Update user details and permissions' : 'Create a new application user' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Account Details --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="Enter full name"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                    <input
                        wire:model="email"
                        type="email"
                        placeholder="email@example.com"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Password --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Password</h2>
            @if($userId)
                <p class="text-sm text-gray-500 mb-4">Leave both fields blank to keep the current password.</p>
            @else
                <p class="text-sm text-gray-500 mb-4">Minimum 8 characters.</p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Password {{ $userId ? '' : '*' }}
                    </label>
                    <input
                        wire:model="password"
                        type="password"
                        placeholder="{{ $userId ? 'Leave blank to keep current' : 'Minimum 8 characters' }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm Password {{ $userId ? '' : '*' }}
                    </label>
                    <input
                        wire:model="password_confirmation"
                        type="password"
                        placeholder="Repeat password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                </div>
            </div>
        </div>

        {{-- Role & Status --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Role & Access</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                    <div class="space-y-2">
                        @foreach($roles as $r)
                            <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors {{ (int)$role_id === $r->id ? 'border-primary-400 bg-primary-50' : 'border-gray-200' }}">
                                <input wire:model.live="role_id" type="radio" value="{{ $r->id }}" class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        {{ $r->name }}
                                        @if($r->is_system)
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">System</span>
                                        @endif
                                    </div>
                                    @if($r->description)
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $r->description }}</div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('role_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Status</label>
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input wire:model.live="is_active" type="checkbox" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
                        <div>
                            <div class="text-sm font-medium text-gray-900">Active</div>
                            <div class="text-xs text-gray-500">Inactive users cannot log in.</div>
                        </div>
                    </label>

                    @if($userId && $userId === auth()->id())
                        <p class="mt-2 text-xs text-amber-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            You are editing your own account.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
            >
                <span wire:loading.remove>{{ $userId ? 'Update User' : 'Create User' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
