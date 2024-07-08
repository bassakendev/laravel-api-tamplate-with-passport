<?php

namespace App\Http\Controllers;

use App\Http\Resources\SettingResource;
use App\Models\Setting;

class SettingController extends Controller
{
    public function get()
    {
        $settings =  Setting::all();

        return SettingResource::collection($settings);
    }
}
