<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateBlock extends Model
{
    protected $fillable = [
        'template_id','block_id','included',
        'icon_override','label_override','severity_override',
        'default_text_override','tips_override','references_override','tags_override',
        'order_override','visible_by_default_override'
    ];
    protected $casts = [
        'included'=>'boolean',
        'tips_override'=>'array',
        'references_override'=>'array',
        'tags_override'=>'array',
        'visible_by_default_override'=>'boolean',
    ];

    public function template(){ return $this->belongsTo(Template::class); }
    public function block(){ return $this->belongsTo(Block::class); }
}
