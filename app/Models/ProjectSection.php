<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSection extends Model
{
    protected $fillable = [
        'project_id',
        'section_id',
        'show_title',
    ];

    protected $casts = [
        'show_title' => 'boolean',
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