<?php

namespace Database\Seeders;

use App\Models\Pub;
use App\Models\PubCampaign;
use Illuminate\Database\Seeder;

class PubSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pub_campaign = PubCampaign::create([
            'name' => 'default',
            'from_time' => now(),
            'to_time' => now()->addMonths(2),
        ]);

        Pub::create([
            'content_img' => '',
            'content_text' => 'Hey',
            'campaign_id' => $pub_campaign->id,
        ]);
    }
}
