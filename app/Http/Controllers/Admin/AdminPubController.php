<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pub;
use App\Models\PubCampaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AdminPubController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $pubs = Pub::latest('updated_at')->paginate(100);
        return view('pages.pubs.index', compact('pubs'));
    }

    public function show($id)
    {
        $pub = Pub::find($id);
        return view('pages.pubs.show', compact('pub'));
    }

    public function create()
    {
        $campaigns = PubCampaign::all();
        return view('pages.pubs.create', compact('campaigns'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_text' => ['required', 'string', 'max:255'],
            'content_img' => ['nullable', 'file', 'mimes:jpeg,png,gif,jpg'],
            'campaign_id' => ['required', 'min:1', 'string'],
        ]);

        // // the image_url field is valid only when the content field is null
        if (!$request->hasFile('content_img')) {
            $request->validate([
                'image_url' => ['required', 'string', 'max:255'],
            ]);
        }

        $pub = new Pub();
        $lastPub = Pub::latest()->first();
        $pub->campaign_id = $request->input('campaign_id');
        $pub->content_text = $request->input('content_text');

        if ($request->hasFile('content_img')) {
            $imagePath = $request->file('content_img')->store("images/campaign-$pub->campaign_id/pub_images-" . $lastPub->id + 1, 'public');
            $pub->content_img = env('APP_URL') . "/storage/$imagePath";
        } else {
            $pub->content_img = $request->input('image_url');
        }

        $pub->save();
        return redirect()->route('show.pubs', $pub->id)->with('success', 'Created Successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content_text' => ['required', 'string', 'max:255'],
            'content_img' => ['nullable', 'file', 'mimes:jpeg,png,gif,jpg'],
            'campaign_id' => ['required', 'min:1', 'string'],
        ]);


        $pub = Pub::find($id);

        $pub->content_text = $request->input('content_text');
        $pub->campaign_id = $request->input('campaign_id');

        if (!$request->input('image_url')) {
            if ($request->hasFile('content_img')) {
                if ($pub->content_img) {
                    Storage::delete($pub->content_img);

                    $imagePath = $request->file('content_img')->store("images/pub_images-$pub->id", 'public');
                    $pub->content_img = env('APP_URL') . "/storage/$imagePath";
                } else {

                    $imagePath = $request->file('content_img')->store("images/pub_images-$pub->id", 'public');
                    $pub->content_img = env('APP_URL') . "/storage/$imagePath";
                }
            }
        } else {
            $pub->content_img = $request->input('image_url');
        }


        $pub->save();

        return redirect()->route('show.pubs', $pub->id)->with('success', 'Updated Successfully');
    }

    public function edite($id)
    {
        $pub = Pub::find($id);
        $campaigns = PubCampaign::all();
        return view('pages.pubs.edite', compact('pub', 'campaigns'));
    }

    public function delete($id)
    {
        $pub = Pub::find($id);
        if ($pub->content_img) {
            Storage::delete($pub->content_img);
        }

        Pub::destroy($id);
        return redirect()->route('all.pubs')->with('success', 'Deleted Successfully');
    }
}
