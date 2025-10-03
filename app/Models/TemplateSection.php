<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateSection extends Model
{
    protected $fillable = [
    'template_id', 'section_id', 'included', 'title_override', 'order_override', 'show_title'
    ];
    protected $casts = [
        'included'=>'boolean',
        'show_title' => 'boolean'

    ];

    public function template(){ return $this->belongsTo(Template::class); }
    public function section(){ return $this->belongsTo(Section::class); }
}
