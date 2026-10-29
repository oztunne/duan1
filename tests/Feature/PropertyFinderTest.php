<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyFinderTest extends TestCase
{
    use RefreshDatabase;

    private function property(array $overrides = []): Listing
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => 'admin'])->save();
        $p = new Listing(array_merge(['kind' => 'property', 'category' => 'rent', 'title_vi' => 'Căn hộ kiểm thử Osaka', 'title_ja' => '大阪テスト物件', 'body_vi' => 'Thông tin thử nghiệm', 'body_ja' => 'テスト物件情報', 'location' => '大阪市北区', 'city' => 'osaka', 'prefecture' => '27', 'district' => 'Kita-ku', 'railway_line' => 'Osaka Metro Midosuji', 'station' => 'Umeda', 'price_yen' => 45000, 'price_vi' => '45.000 yên/tháng', 'price_ja' => '月45,000円', 'area_sqm' => 35, 'floor_plan' => '2DK', 'property_type' => 'apartment', 'walk_minutes' => 7, 'building_age' => 5, 'initial_cost_yen' => 90000, 'apply_overseas' => true, 'amenities' => ['furnished', 'aircon', 'balcony'], 'published' => true, 'latitude' => 34.7, 'longitude' => 135.5], $overrides));
        $p->user_id = $u->id;
        $p->save();

        return $p;
    }

    public function test_bilingual_finder_and_fullscreen_map_render(): void
    {
        $this->get('/vi/find-home')->assertOk()->assertSee('Tuyến tàu')->assertSee('Kansai');
        $this->get('/ja/find-home?region=kanto&method=area')->assertOk()->assertSee('東京')->assertDontSee('大阪');
        $this->get('/vi/property-map')->assertOk()->assertSee('map-app-workspace', false)->assertSee('leaflet.markercluster.js', false);
        $this->get('/ja/property-map')->assertOk()->assertSee('都道府県');
    }

    public function test_prefecture_station_and_district_facets_only_use_published_inventory(): void
    {
        $p = $this->property();
        $this->property(['published' => false, 'station' => 'Secret Station', 'district' => 'Secret Ward']);
        $this->get('/vi/find-home?region=kansai&method=station')->assertOk()->assertSee('Umeda')->assertSee('Osaka Metro Midosuji')->assertDontSee('Secret Station');
        $this->get('/vi/find-home?region=kansai&prefecture=27&method=area')->assertOk()->assertSee('Kita-ku')->assertDontSee('Secret Ward');
        $this->get('/vi/property-points?region=kanto')->assertOk()->assertJsonCount(0, 'points');
        $this->get('/vi/property-points?prefecture=27&district=Kita-ku&railway_line=Osaka%20Metro%20Midosuji&station_name=Umeda')->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.id', $p->id);
    }

    public function test_all_topic_filters_and_amenity_filters_work(): void
    {
        $good = $this->property();
        $this->property(['title_vi' => 'Không phù hợp', 'price_yen' => 150000, 'area_sqm' => 15, 'floor_plan' => '1R', 'property_type' => 'house', 'walk_minutes' => 25, 'building_age' => 30, 'initial_cost_yen' => 200000, 'apply_overseas' => false, 'amenities' => ['internet']]);
        foreach (config('property.topics') as $topic) {
            $this->get('/vi/property-points?topic='.$topic)->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.id', $good->id);
        }
        $query = '?property_types[]=apartment&layouts[]=2DK&amenities[]=aircon&amenities[]=balcony&min_area=30&max_area=40&walk_max=10&age_max=10';
        $this->get('/vi/property-points'.$query)->assertOk()->assertJsonCount(1, 'points')->assertJsonPath('points.0.id', $good->id);
        $this->get('/vi/listings/property'.$query)->assertOk()->assertSee('Căn hộ kiểm thử Osaka')->assertDontSee('Không phù hợp');
    }

    public function test_admin_saves_search_metadata_and_bad_conditions_rejected(): void
    {
        $p = $this->property();
        $admin = User::findOrFail($p->user_id);
        $data = $p->only(['kind', 'category', 'title_vi', 'title_ja', 'body_vi', 'body_ja', 'location', 'published', 'city', 'prefecture', 'district', 'railway_line', 'station', 'property_type', 'walk_minutes', 'building_age', 'initial_cost_yen', 'apply_overseas', 'amenities']);
        $this->actingAs($admin)->put('/content/'.$p->id, $data)->assertSessionHasNoErrors();
        $this->assertSame(['furnished', 'aircon', 'balcony'], $p->fresh()->amenities);
        $data['amenities'] = ['unapproved'];
        $this->put('/content/'.$p->id, $data)->assertSessionHasErrors('amenities.0');
        $this->getJson('/vi/property-points?region=invalid')->assertUnprocessable();
        $this->getJson('/vi/property-points?prefecture=99')->assertUnprocessable();
        $this->getJson('/vi/property-points?min_area=50&max_area=20')->assertUnprocessable();
        $this->getJson('/vi/property-points?amenities[]=unapproved')->assertUnprocessable();
    }
}
