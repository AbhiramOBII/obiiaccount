<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $roleId = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public bool $is_system = false;
    public array $selectedPermissions = []; // ["module.action", ...]

    public function mount(?int $id = null): void
    {
        if ($id) {
            $role = Role::with('permissions')->findOrFail($id);
            $this->roleId      = $role->id;
            $this->name        = $role->name;
            $this->slug        = $role->slug;
            $this->description = $role->description ?? '';
            $this->is_system   = $role->is_system;

            foreach ($role->permissions as $perm) {
                $this->selectedPermissions[] = "{$perm->module}.{$perm->action}";
            }
        }
    }

    public function updatedName(string $value): void
    {
        if (!$this->roleId) {
            $this->slug = Str::slug($value);
        }
    }

    public function toggleAll(string $module): void
    {
        $moduleConfig = Permission::MODULES[$module] ?? null;
        if (!$moduleConfig) return;

        $moduleKeys = array_map(fn($a) => "{$module}.{$a}", array_keys($moduleConfig['actions']));
        $allSelected = count(array_intersect($moduleKeys, $this->selectedPermissions)) === count($moduleKeys);

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $moduleKeys));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $moduleKeys)));
        }
    }

    public function save(): void
    {
        if ($this->roleId) {
            abort_unless(auth()->user()->hasPermission('roles', 'update'), 403);
        } else {
            abort_unless(auth()->user()->hasPermission('roles', 'create'), 403);
        }

        $this->validate([
            'name'        => 'required|string|max:255',
            'slug'        => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles', 'slug')->ignore($this->roleId)],
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
        ];

        if ($this->roleId) {
            $role = Role::findOrFail($this->roleId);
            $role->update($data);
        } else {
            $role = Role::create($data);
        }

        // Sync selected permissions
        $permIds = [];
        foreach ($this->selectedPermissions as $key) {
            [$module, $action] = explode('.', $key, 2);
            $perm = Permission::where('module', $module)->where('action', $action)->first();
            if ($perm) $permIds[] = $perm->id;
        }
        $role->permissions()->sync($permIds);

        session()->flash('success', $this->roleId ? "Role \"{$role->name}\" updated." : "Role \"{$role->name}\" created.");
        $this->redirect(route('admin.roles.index'), navigate: true);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.roles.index') }}" class="p-2 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $roleId ? 'Edit Role' : 'Add Role' }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $roleId ? 'Update role name and permission set' : 'Create a new role and assign permissions' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Info --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Role Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                    <input wire:model.live="name" type="text" placeholder="e.g. Sales Manager" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none {{ $is_system ? 'bg-gray-50' : '' }}" {{ $is_system ? 'readonly' : '' }} />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                    <input wire:model="slug" type="text" placeholder="e.g. sales-manager" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none {{ $is_system ? 'bg-gray-50' : '' }}" {{ $is_system ? 'readonly' : '' }} />
                    @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input wire:model="description" type="text" placeholder="Brief description of this role..." class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Permission Matrix --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Permissions</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Select the actions this role can perform in each module</p>
                </div>
                <span class="text-xs text-gray-400">{{ count($selectedPermissions) }} selected</span>
            </div>

            @php
                $allActions = ['create', 'read', 'update', 'delete'];
                $actionLabels = ['create' => 'Create', 'read' => 'Read', 'update' => 'Update', 'delete' => 'Delete'];
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-3 pr-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-48">Module</th>
                            @foreach($actionLabels as $action => $label)
                                <th class="py-3 px-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">{{ $label }}</th>
                            @endforeach
                            <th class="py-3 pl-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-24">All</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach(\App\Models\Permission::MODULES as $module => $config)
                            @php
                                $availableActions = array_keys($config['actions']);
                                $moduleKeys = array_map(fn($a) => "{$module}.{$a}", $availableActions);
                                $allChecked = count(array_intersect($moduleKeys, $selectedPermissions)) === count($moduleKeys);
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-800">{{ $config['label'] }}</div>
                                    <div class="text-xs text-gray-400 font-mono">{{ $module }}</div>
                                </td>
                                @foreach($allActions as $action)
                                    <td class="py-3 px-4 text-center">
                                        @if(in_array($action, $availableActions))
                                            <label class="inline-flex items-center justify-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="selectedPermissions"
                                                    value="{{ $module }}.{{ $action }}"
                                                    class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                                                />
                                            </label>
                                        @else
                                            <span class="text-gray-300 text-base">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="py-3 pl-4 text-center">
                                    <button
                                        type="button"
                                        wire:click="toggleAll('{{ $module }}')"
                                        class="text-xs px-2 py-1 rounded {{ $allChecked ? 'bg-primary-100 text-primary-700 hover:bg-primary-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }} transition-colors font-medium"
                                    >
                                        {{ $allChecked ? 'Clear' : 'All' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.roles.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
            >
                <span wire:loading.remove>{{ $roleId ? 'Update Role' : 'Create Role' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
