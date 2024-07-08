<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePubCampaignsAndPubsAndPubCampaignStatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('pub_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->datetime("from_time")->nullable();
            $table->datetime("to_time")->nullable();
            $table->timestamps();
        });

        Schema::create('pubs', function (Blueprint $table) {
            $table->id();
            $table->string("content_img")->nullable();
            $table->string("content_text")->nullable();
            $table->unsignedBigInteger('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('pub_campaigns');
            $table->timestamps();
        });

        Schema::create('pub_campaign_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('campaign_id');
            $table->datetime("clicked_time")->nullable();
            $table->datetime("viewed_time")->nullable();

            $table->foreign('campaign_id')->references('id')->on('pub_campaigns');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pub_campaigns');
        Schema::dropIfExists('pubs');
        Schema::dropIfExists('pub_campaigns');
    }
}
