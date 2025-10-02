<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = ['key','name','description','meta'];
    protected $casts = ['meta' => 'array'];

    public function sections()  { return $this->hasMany(TemplateSection::class); }
    public function blocks()    { return $this->hasMany(TemplateBlock::class); }
}
