<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_customer_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'customer_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_customer_profile');
    }
};
