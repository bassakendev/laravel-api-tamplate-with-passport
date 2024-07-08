<?php

use App\Enums\SavingGroupStatusEnum;
use App\Enums\SavingGroupTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Enums\SavingGroupContributionFrequencyEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saving_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('admin_id')->constrained('users');
            $table->text('description');
            $table->dateTime('deadline');
            $table->enum('type', collect(SavingGroupTypeEnum::cases())->map(fn ($type) => $type->value)->toArray())->default(SavingGroupTypeEnum::NORMAL->value);
            $table->double('total_amount', 15, 2)->default(0.0);
            $table->double('target_amount_per_member', 15, 2)->default(0.0);
            $table->double('admission_fees', 15, 2)->default(0.0);
            $table->integer('total_members')->default(1);
            $table->integer('number_of_period')->default(1);
            $table->integer('current_period')->default(1);
            $table->dateTime('current_period_end_date')->nullable();
            $table->double('penalty_fees_per', 3, 2)->default(0.0);
            $table->enum('contribution_frequency', collect(SavingGroupContributionFrequencyEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(SavingGroupContributionFrequencyEnum::MONTHLY->value);
            $table->enum('status', collect(SavingGroupStatusEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(SavingGroupStatusEnum::INPROGRESS->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_groups');
    }
};
