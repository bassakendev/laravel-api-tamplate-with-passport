<?php

namespace Tests\Feature;

use App\Models\PubCampaign;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class PubTest extends TestCase
{
    use RefreshDatabase;

    private static mixed $request;
    private static mixed $user;
    private static mixed $campaign;

    public function setUp(): void
    {
        parent::setUp();

        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '66666666',
            'email' => 'johndoe@example.com',
            'password' => bcrypt('password'),
            'activation_token' => 'john',
        ]);

        $campaign = PubCampaign::first();

        $response = $this->postJson('api/auth/login', [
            'email' => 'johndoe@example.com',
            'remember_me' => 'present',
            'password' => 'password',
        ]);

        $logRes = $response->json();

        $request = $this->withHeaders([
            'Authorization' => 'Bearer ' . $logRes['access_token'],
        ]);

        $user = $request->get('/api/auth/user')->json()['data'];

        Self::$request = $request;
        Self::$user = $user;
        Self::$campaign = $campaign;
    }

    public function testUserCanFetchActivePub()
    {
        $response = Self::$request->get('api/pubs/active-pub');

        $response->assertStatus(200);

        $response->assertJsonStructure(
            ['data'  => [
                'id',
                'content_img',
                'content_text',
                'campaign_id'
            ]]
        );
    }

    /**
     * Test the pub view
     *
     * @return void
     */
    public function testUserCanViewPub()
    {
        $response = Self::$request->postJson('api/pubs/save-view', [
            'campaign_id' => Self::$campaign->id,
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['view']['campaign_id'] == Self::$campaign->id);

        $this->assertDatabaseHas('pub_campaign_stats', [
            'user_id' => Self::$user['id'],
            'campaign_id' => Self::$campaign->id,
        ]);
    }

    /**
     * Test the pub click
     *
     * @return void
     */
    public function testUserCanClickPub()
    {
        $response = Self::$request->postJson('api/pubs/save-click', [
            'campaign_id' => Self::$campaign->id,
        ]);

        $response->assertStatus(201);

        $this->assertTrue($response['click']['campaign_id'] == Self::$campaign->id);

        $this->assertDatabaseHas('pub_campaign_stats', [
            'user_id' => Self::$user['id'],
            'campaign_id' => Self::$campaign->id,
        ]);
    }
}
