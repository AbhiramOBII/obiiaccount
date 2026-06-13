<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name', 'contact_name', 'phone', 'mobile', 'email',
        'gstin', 'gst_type', 'pan', 'is_active',
        'billing_street', 'billing_city', 'billing_state_id', 'billing_state_name', 'billing_pincode', 'billing_country',
        'shipping_street', 'shipping_city', 'shipping_state_id', 'shipping_state_name', 'shipping_pincode', 'shipping_country',
        'credit_limit', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'billing_state_id' => 'integer',
            'shipping_state_id' => 'integer',
        ];
    }
}
