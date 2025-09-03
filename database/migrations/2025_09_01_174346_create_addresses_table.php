<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->string('country')->default('BR');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('type')->default('residential');
            $table->unsignedBigInteger('addressable_id')->nullable()->after('id');
            $table->string('addressable_type')->nullable()->after('addressable_id');
            $table->timestamps();

            $table->index(['addressable_id', 'addressable_type']);
            $table->unique(['addressable_id', 'addressable_type', 'type'], 'unique_address_type_per_entity');
            $table->unique(['addressable_id', 'addressable_type', 'is_primary'], 'unique_primary_address_per_entity')
                ->where('is_primary', true);
            $table->index(['city', 'state']);
            $table->index(['zip_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
