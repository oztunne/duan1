<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Task extends Model {
    protected $fillable = ['title','description','user_id','due_date','status'];
    protected function casts(): array { return ['due_date'=>'date']; }
    public function assignee() { return $this->belongsTo(User::class, 'user_id'); }
}

