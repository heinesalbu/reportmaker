<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCustomBlock extends Model
{
    protected $fillable = [
        'project_id',
        'section_id',
        'label',
        'icon',
        'severity',
        'text',
        'order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
