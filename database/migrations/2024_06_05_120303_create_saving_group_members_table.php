<?php

use App\Enums\GoalStatusEnum;
use App\Enums\SavingGroupMemberSatusEnum;
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
        Schema::create('saving_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saving_group_id')->constrained('saving_groups');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('is_admin')->default(false);
            $table->double('current_amount', 15, 2)->default(0.0);
            $table->double('virtual_current_amount', 15, 2)->default(0.0);
            $table->dateTime('last_payment_date')->nullable();
            $table->enum('participation_status', collect(GoalStatusEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(GoalStatusEnum::INPROGRESS->value);
            $table->enum('status', collect(SavingGroupMemberSatusEnum::cases())->map(fn ($status) => $status->value)->toArray())->default(SavingGroupMemberSatusEnum::PENDING->value);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_group_members');
    }
};
