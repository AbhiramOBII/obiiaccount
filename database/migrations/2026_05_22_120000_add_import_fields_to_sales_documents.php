<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->string('reference_number', 50)->nullable()->after('document_number');
            $table->decimal('round_off', 8, 2)->default(0)->after('grand_total');
            $table->boolean('inclusive_tax')->default(false)->after('round_off');
        });
    }

    public function down(): void
    {
        Schema::table('sales_documents', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'round_off', 'inclusive_tax']);
        });
    }
};
