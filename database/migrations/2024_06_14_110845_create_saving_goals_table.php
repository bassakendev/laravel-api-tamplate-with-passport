<?php

use App\Enums\GoalStatusEnum;
use App\Enums\LoanStatusEnum;
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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->double('amount', 15, 2)->default(0.0);
            $table->double('interest', 15, 2)->default(0.0);
            $table->string('reason');
            $table->double('amount_refunded', 15, 2)->default(0.0);
            $table->enum('status', collect(LoanStatusEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(LoanStatusEnum::NOT_REFUNDED->value);
            $table->dateTime('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('saving_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('loan_id')->nullable()->constrained('loans')->onDelete('cascade');
            $table->text('description');
            $table->double('penalty_fees_per', 3, 2)->default(0.0);
            $table->double('target_amount', 15, 2)->default(0.0);
            $table->double('current_amount', 15, 2)->default(0.0);
            $table->enum('status', collect(GoalStatusEnum::cases())->map(fn ($e) => $e->value)->toArray())->default(GoalStatusEnum::INPROGRESS->value);
            $table->dateTime('deadline');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_goals');
        Schema::dropIfExists('loans');
    }
};
