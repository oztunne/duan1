<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = ['kind', 'title_vi', 'title_ja', 'body_vi', 'body_ja', 'location', 'address', 'category', 'price_vi', 'price_ja', 'published', 'latitude', 'longitude', 'city', 'price_yen', 'area_sqm', 'floor_plan', 'station', 'prefecture', 'district', 'railway_line', 'property_type', 'walk_minutes', 'building_age', 'initial_cost_yen', 'apply_overseas', 'amenities'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'apply_overseas' => 'boolean', 'amenities' => 'array', 'latitude' => 'float', 'longitude' => 'float', 'price_yen' => 'integer', 'area_sqm' => 'float'];
    }

    public function local(string $field): string
    {
        return (string) $this->getAttribute($field.'_'.app()->getLocale());
    }
}
