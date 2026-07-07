<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // At most one tax figure per property per year.
            $table->unique(['property_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_taxes');
    }
};
