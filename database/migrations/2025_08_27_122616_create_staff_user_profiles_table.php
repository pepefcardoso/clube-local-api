<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('active');
            $table->string('access_level')->default('basic');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_user_profiles');
    }
};
