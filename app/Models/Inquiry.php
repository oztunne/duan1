<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = ['listing_id', 'name', 'email', 'phone', 'service', 'locale', 'message'];

    protected function casts(): array
    {
        return ['handled' => 'boolean'];
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
