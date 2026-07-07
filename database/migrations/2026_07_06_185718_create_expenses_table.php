<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('frequency'); // one_time | monthly | quarterly | yearly
            $table->string('amount_type')->default('fixed'); // fixed | percentage
            // Dollars when amount_type = fixed; percent (e.g. 8.00 = 8%) when percentage.
            $table->decimal('amount', 12, 2);
            // Optional rent figure to apply a percentage against; null = current rent.
            $table->decimal('rent_basis', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
