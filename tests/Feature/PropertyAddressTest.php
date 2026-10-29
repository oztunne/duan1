<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PropertyAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_lookup_requires_login_and_caches_valid_japan_results(): void
    {
        Cache::flush();
        Http::fake(['*' => Http::response([
            ['geometry' => ['type' => 'Point', 'coordinates' => [135.526, 34.687]], 'properties' => ['title' => '大阪城']],
            ['geometry' => ['type' => 'Point', 'coordinates' => [-74, 40]], 'properties' => ['title' => 'Outside Japan']],
        ])]);
        $this->postJson('/content/property-address', ['address' => '大阪城'])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/content/property-address', ['address' => '大阪城'])->assertOk()
                ->assertJsonCount(1, 'results')->assertJsonPath('results.0.latitude', 34.687)
                ->assertJsonPath('results.0.longitude', 135.526);
        }
        Http::assertSentCount(1);
        $this->postJson('/content/property-address', ['address' => ''])->assertUnprocessable();
    }

    public function test_provider_failure_returns_recoverable_message(): void
    {
        Cache::flush();
        Http::fake(['*' => Http::response([], 503)]);
        $this->actingAs(User::factory()->create())->postJson('/content/property-address', ['address' => '東京都新宿区'])
            ->assertStatus(503)->assertJsonStructure(['message']);
    }

    public function test_address_and_hidden_map_position_are_saved(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $this->actingAs($user)->get('/content/create?kind=property')->assertOk()
            ->assertSee('Địa chỉ nhà tại Nhật Bản')->assertSee('id="latitude" name="latitude" type="hidden"', false)
            ->assertDontSee('<label for="latitude">', false);
        $this->post('/content', [
            'kind' => 'property', 'category' => 'rent', 'location' => 'Osaka',
            'title_vi' => 'Nhà Osaka', 'title_ja' => '大阪の家', 'body_vi' => 'Mô tả nhà', 'body_ja' => '物件説明',
            'address' => '大阪府大阪市中央区大阪城1-1', 'latitude' => 34.687, 'longitude' => 135.526,
        ])->assertSessionHasNoErrors();
        $listing = Listing::firstOrFail();
        $this->assertSame('大阪府大阪市中央区大阪城1-1', $listing->address);
        $this->assertEquals(34.687, $listing->latitude);
    }
}
