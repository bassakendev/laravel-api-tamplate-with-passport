<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['name' => 'audio_max_size', 'value' => '20', 'description' => 'Maximum size of audio file.'],
            ['name' => 'video_max_size', 'value' => '50', 'description' => 'Maximum size of video file.'],
            ['name' => 'image_max_size', 'value' => '12', 'description' => 'Maximum size of image file.'],
            ['name' => 'text_max_size', 'value' => '30', 'description' => 'Maximum size of text file.'],
        ];

        if (Setting::count() == 0) {
            foreach ($settings as $setting) {
                Setting::create($setting);
            }
        }
    }
}
