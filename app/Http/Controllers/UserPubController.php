<?php

namespace App\Http\Controllers;

use App\Models\Pub;
use Illuminate\Http\Request;
use App\Models\PubCampaignStat;
use App\Http\Resources\PubResource;

class UserPubController extends Controller
{
    //

    public function active()
    {
        $activePub = Pub::latest()->first();

        if ($activePub) {
            return new PubResource($activePub);
        }

        return response()->noContent();
    }

    public function onView(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required',
        ]);

        /** @var \App\Models\User */
        $user = auth()->user();

        $stat = PubCampaignStat::create([
            'user_id' => $user->id,
            'campaign_id' => $request->campaign_id,
            'viewed_time' => now(),
        ]);

        return response()->json(['view' => $stat], 201);
    }

    public function onClick(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required',
        ]);

        $stat = PubCampaignStat::create([
            'user_id' => $request->user()->id,
            'campaign_id' => $request->input('campaign_id'),
            'clicked_time' => now(),
        ]);

        return response()->json(['click' => $stat], 201);
    }
}
