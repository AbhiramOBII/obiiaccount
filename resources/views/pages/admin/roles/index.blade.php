<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Role;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('roles', 'delete'), 403);

        $role = Role::findOrFail($id);

        if ($role->is_system) {
            session()->flash('error', 'System roles cannot be deleted.');
            return;
        }

        $role->permissions()->detach();
        $role->users()->update(['role_id' => null]);
        $role->delete();

        session()->flash('success', "Role \"{$role->name}\" deleted.");
    }

    public function with(): array
    {
        return [
            'roles' => Role::withCount('users', 'permissions')->latest()->paginate(20),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Roles</h1>
            <p class="mt-1 text-sm text-gray-500">Define roles and their module-level permissions</p>
        </div>
        @if(auth()->user()->hasPermission('roles', 'create'))
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Role
            </a>
        @endif
    </div>

    @if(session('error'))
        <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">Role</th>
                    <th class="px-4 py-3 text-left font-medium">Description</th>
                    <th class="px-4 py-3 text-center font-medium">Permissions</th>
                    <th class="px-4 py-3 text-center font-medium">Users</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($roles as $role)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ $role->name }}</span>
                                @if($role->is_system)
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">System</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $role->slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $role->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                {{ $role->permissions_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if(auth()->user()->hasPermission('roles', 'update'))
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="p-1.5 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                @endif
                                @if(!$role->is_system && auth()->user()->hasPermission('roles', 'delete'))
                                    <button wire:click="delete({{ $role->id }})" wire:confirm="Delete role \"{{ $role->name }}\"? Users assigned this role will lose their role." class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-gray-400">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($roles->hasPages())
            <div class="p-4 border-t border-gray-200">{{ $roles->links() }}</div>
        @endif
    </div>
</div>
