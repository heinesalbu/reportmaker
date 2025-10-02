<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['key','label','order'];

    public function blocks()
    {
        return $this->hasMany(Block::class)->orderBy('id');
    }
}
