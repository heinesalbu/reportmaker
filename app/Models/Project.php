<?php
// app/Models/Project.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'customer_id','title','template_id','owner_id','status','tags','description'
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function projectBlocks()
    {
        return $this->hasMany(\App\Models\ProjectBlock::class);
    }

    public function blocks()
    {
        return $this->belongsToMany(\App\Models\Block::class, 'project_blocks')
                    ->withPivot(['selected','override_text'])
                    ->withTimestamps();
    }
    public function template()
    {
        return $this->belongsTo(\App\Models\Template::class, 'template_id', 'key')->orWhere('template_id', $this->template_id);
    }
    public function customBlocks()
    {
        return $this->hasMany(ProjectCustomBlock::class);
    }

}
