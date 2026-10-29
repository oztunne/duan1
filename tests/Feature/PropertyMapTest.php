<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyMapTest extends TestCase
{
    use RefreshDatabase;

    private function property(array $extra = []): Listing
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();
        $p = new Listing(array_merge(['kind' => 'property', 'category' => 'rent', 'title_vi' => 'Nhà Osaka', 'title_ja' => '大阪の物件', 'body_vi' => 'Nội dung mô tả thử', 'body_ja' => 'テスト情報です', 'location' => 'Osaka', 'published' => true, 'latitude' => 34.6937, 'longitude' => 135.5023, 'city' => 'osaka', 'price_yen' => 80000, 'floor_plan' => '1LDK'], $extra));
        $p->user_id = $user->id;
        $p->save();

        return $p;
    }

    public function test_map_returns_only_published_geolocated_properties_in_local_language(): void
    {
        $a = $this->property();
        $this->property(['published' => false]);
        $this->property(['latitude' => null, 'longitude' => null]);
        $this->property(['kind' => 'job', 'category' => 'fulltime']);
        $this->get('/ja/property-points')->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.id', $a->id)->assertJsonPath('points.0.title', '大阪の物件');
        $this->get('/vi/listings/property')->assertOk()->assertSee('property-map-data', false)->assertSee('Tìm nhà trên bản đồ');
        $this->get('/vi/listings/property?mode=list')->assertOk()->assertDontSee('id="property-map"', false);
    }

    public function test_map_bounds_city_and_budget_filters_agree_with_list(): void
    {
        $osaka = $this->property();
        $this->property(['title_vi' => 'Nhà Tokyo', 'city' => 'tokyo', 'latitude' => 35.6762, 'longitude' => 139.6503, 'price_yen' => 160000]);
        $query = '?north=35&south=34&east=136&west=135&min_price=50000&max_price=100000&city=osaka&floor_plan=1LDK';
        $this->get('/vi/property-points'.$query)->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.id', $osaka->id);
        $this->get('/vi/listings/property'.$query)->assertOk()->assertSee('Nhà Osaka')->assertDontSee('Nhà Tokyo');
        $this->get('/vi/property-points?max_price=100000')->assertOk()->assertJsonCount(1, 'points');
        $this->get('/vi/property-points?min_price=200000')->assertOk()->assertJsonCount(0, 'points');
    }

    public function test_invalid_map_bounds_rejected(): void
    {
        $this->getJson('/vi/property-points?north=34')->assertUnprocessable()->assertJsonValidationErrors(['south', 'east', 'west']);
        $this->getJson('/vi/property-points?north=34&south=35&east=135&west=136')->assertUnprocessable();
        $this->getJson('/vi/property-points?min_price=100000&max_price=50000')->assertUnprocessable();
        $this->getJson('/vi/property-points?city=unknown')->assertUnprocessable();
    }

    public function test_admin_sets_and_clears_coordinate_pair(): void
    {
        $p = $this->property();
        $admin = User::findOrFail($p->user_id);
        $data = $p->only(['kind', 'category', 'title_vi', 'title_ja', 'body_vi', 'body_ja', 'location', 'published', 'city', 'price_yen', 'floor_plan']);
        $this->actingAs($admin)->put('/content/'.$p->id, array_merge($data, ['latitude' => 34.7]))->assertSessionHasErrors('longitude');
        $this->put('/content/'.$p->id, array_merge($data, ['latitude' => 99, 'longitude' => 135]))->assertSessionHasErrors('latitude');
        $this->put('/content/'.$p->id, array_merge($data, ['latitude' => 34.7, 'longitude' => 135.6]))->assertSessionHasNoErrors();
        $this->assertEquals(34.7, $p->fresh()->latitude);
        $this->put('/content/'.$p->id, array_merge($data, ['latitude' => '', 'longitude' => '']))->assertSessionHasNoErrors();
        $this->assertNull($p->fresh()->latitude);
    }

    public function test_map_data_escapes_user_content_in_html(): void
    {
        $this->property(['title_vi' => '</script><script>alert(1)</script>']);
        $this->get('/vi/listings/property')->assertOk()->assertDontSee('</script><script>alert(1)</script>',false);
    }
}
