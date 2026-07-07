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
        Schema::table('property_taxes', function (Blueprint $table) {
            $table->date('bill_date')->nullable()->after('estimated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_taxes', function (Blueprint $table) {
            $table->dropColumn('bill_date');
        });
    }
};
