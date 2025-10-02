<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateSection extends Model
{
    protected $fillable = [
        'template_id','section_id','included','title_override','order_override'
    ];
    protected $casts = ['included'=>'boolean'];

    public function template(){ return $this->belongsTo(Template::class); }
    public function section(){ return $this->belongsTo(Section::class); }
}
