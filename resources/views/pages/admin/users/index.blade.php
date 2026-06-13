<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\User;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'update'), 403);
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }
        $user->update(['is_active' => !$user->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('users', 'delete'), 403);
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }
        User::findOrFail($id)->delete();
        session()->flash('success', 'User deleted successfully.');
    }

    public function with(): array
    {
        $query = User::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        return [
            'users' => $query->with('role')->latest()->paginate(15),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Users</h1>
            <p class="mt-1 text-sm text-gray-500">Manage application users and roles</p>
        </div>
        @if(auth()->user()->hasPermission('users', 'create'))
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add User
            </a>
        @endif
    </div>

    @if(session('error'))
        <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search by name or email..."
                class="w-full sm:w-80 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-center font-medium">Role</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-sm font-semibold text-primary-700 shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        @if($user->id === auth()->id())
                                            <div class="text-xs text-primary-500 font-medium">You</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($user->role)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $user->role->is_system ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $user->role->name }}
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-400">No Role</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="toggleActive({{ $user->id }})"
                                    @if($user->id === auth()->id()) disabled @else wire:confirm="Toggle active status for {{ $user->name }}?" @endif
                                    class="{{ $user->id === auth()->id() ? 'cursor-not-allowed opacity-60' : '' }}"
                                >
                                    @if($user->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->hasPermission('users', 'update'))
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="p-1.5 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endif
                                    @if($user->id !== auth()->id() && auth()->user()->hasPermission('users', 'delete'))
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="Delete {{ $user->name }}? This cannot be undone." class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                No users found.
                                <a href="{{ route('admin.users.create') }}" class="text-primary-600 hover:underline">Add a user</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
