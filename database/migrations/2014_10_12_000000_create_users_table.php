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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');

            $table->string('email')->unique();
            $table->unsignedBigInteger('referrer_id')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string("country")->default("Cameroon");
            $table->string("activation_token");

            $table->boolean('disabled')->default(false);
            $table->string('avatar')->nullable();
            $table->string('preferred_lang', 2)->nullable();

            $table->foreign('referrer_id')->references('id')->on('users')->onDelete('set null');

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
