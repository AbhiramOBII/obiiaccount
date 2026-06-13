<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['module', 'action', 'label'];

    /**
     * All modules and their available actions.
     * module => [ action => label ]
     */
    public const MODULES = [
        'dashboard' => [
            'label'   => 'Dashboard',
            'actions' => [
                'read' => 'View Dashboard',
            ],
        ],
        'customers' => [
            'label'   => 'Customers',
            'actions' => [
                'create' => 'Create Customers',
                'read'   => 'View Customers',
                'update' => 'Edit Customers',
                'delete' => 'Delete Customers',
            ],
        ],
        'sales' => [
            'label'   => 'Sales',
            'actions' => [
                'create' => 'Create Sales Documents',
                'read'   => 'View Sales Documents',
                'update' => 'Edit Sales Documents',
                'delete' => 'Delete Sales Documents',
            ],
        ],
        'import' => [
            'label'   => 'Import',
            'actions' => [
                'read' => 'Use Import',
            ],
        ],
        'users' => [
            'label'   => 'Users',
            'actions' => [
                'create' => 'Create Users',
                'read'   => 'View Users',
                'update' => 'Edit Users',
                'delete' => 'Delete Users',
            ],
        ],
        'roles' => [
            'label'   => 'Roles',
            'actions' => [
                'create' => 'Create Roles',
                'read'   => 'View Roles',
                'update' => 'Edit Roles',
                'delete' => 'Delete Roles',
            ],
        ],
    ];

    public const ALL_ACTIONS = ['create', 'read', 'update', 'delete'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
