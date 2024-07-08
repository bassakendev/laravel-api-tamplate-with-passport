<?php

use App\Enums\WalletOperationEnum;
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
        Schema::create('admin_wallets', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('balance', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('admin_loan_wallets', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('balance', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('saving_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('balance', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('loan_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('balance', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('withdrawal_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('balance', 15, 2)->default(0.0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('transaction_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('wallet_type');
            $table->unsignedBigInteger('wallet_id');
            $table->enum('operation', collect(WalletOperationEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(WalletOperationEnum::REMOVE->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_wallets');
        Schema::dropIfExists('loan_wallets');
        Schema::dropIfExists('transaction_wallets');
        Schema::dropIfExists('admin_wallets');
        Schema::dropIfExists('withdrawal_wallets');
        Schema::dropIfExists('admin_loan_wallets');
    }
};
