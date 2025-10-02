<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $fillable = [
        'section_id','key','label','icon','severity',
        'default_text','tips','references','tags','visible_by_default','order'
    ];

    protected $casts = [
        'tips' => 'array',
        'references' => 'array',
        'tags' => 'array',
        'visible_by_default' => 'boolean',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function projectBlocks()
    {
        return $this->hasMany(\App\Models\ProjectBlock::class);
    }

}
