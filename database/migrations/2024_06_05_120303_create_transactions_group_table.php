<?php

use App\Enums\TransactionStatusEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tx_type');
            $table->unsignedBigInteger('tx_id');
            $table->string('reason')->nullable();
            $table->double('amount', 15, 2)->default(0.0);
            $table->double('fees', 15, 2)->default(0.0);
            $table->string('external_reference')->nullable();
            $table->string('reference');
            $table->enum('status', collect(TransactionStatusEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(TransactionStatusEnum::COMPLETED->value);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('amount', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('amount', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('fundings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('amount', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('amount', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('savings');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('fundings');
    }
};
