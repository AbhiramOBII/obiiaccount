<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();

            $table->string('gstin')->nullable();
            $table->enum('gst_type', ['regular', 'unregistered', 'consumer', 'overseas'])->default('unregistered');
            $table->string('pan')->nullable();
            $table->boolean('is_active')->default(true);

            $table->string('billing_street')->nullable();
            $table->string('billing_city')->nullable();
            $table->unsignedInteger('billing_state_id')->nullable();
            $table->string('billing_state_name')->nullable();
            $table->string('billing_pincode')->nullable();
            $table->string('billing_country')->default('India');

            $table->string('shipping_street')->nullable();
            $table->string('shipping_city')->nullable();
            $table->unsignedInteger('shipping_state_id')->nullable();
            $table->string('shipping_state_name')->nullable();
            $table->string('shipping_pincode')->nullable();
            $table->string('shipping_country')->default('India');

            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->string('currency', 10)->default('INR');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
