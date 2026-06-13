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
        Schema::create('sales_documents', function (Blueprint $table) {
            $table->id();

            $table->enum('document_type', ['estimate', 'proforma', 'invoice']);
            $table->string('document_number', 50)->unique();
            $table->unsignedInteger('document_sequence')->default(0);

            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_name');

            // Billing snapshot
            $table->string('billing_street')->nullable();
            $table->string('billing_city')->nullable();
            $table->unsignedInteger('billing_state_id')->nullable();
            $table->string('billing_state_name')->nullable();
            $table->string('billing_pincode', 10)->nullable();
            $table->string('billing_country', 100)->default('India');
            $table->string('gstin', 15)->nullable();

            // Place of supply
            $table->unsignedInteger('place_of_supply_id')->nullable();
            $table->string('place_of_supply_name')->nullable();

            $table->date('document_date');
            $table->date('due_date')->nullable();

            // Tax type
            $table->enum('tax_type', ['cgst_sgst', 'igst'])->default('igst');

            // Totals
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('cgst_total', 15, 2)->default(0);
            $table->decimal('sgst_total', 15, 2)->default(0);
            $table->decimal('igst_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->string('currency', 10)->default('INR');

            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            $table->enum('status', ['draft', 'sent', 'accepted', 'declined', 'cancelled'])->default('draft');

            // Link to source document for conversions
            $table->foreignId('converted_from_id')->nullable()->constrained('sales_documents')->nullOnDelete();

            $table->timestamps();

            $table->index(['document_type', 'document_date']);
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_documents');
    }
};
