<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = ['kind', 'title_vi', 'title_ja', 'body_vi', 'body_ja', 'location', 'category', 'price_vi', 'price_ja', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function local(string $field): string
    {
        return (string) $this->getAttribute($field.'_'.app()->getLocale());
    }
}
