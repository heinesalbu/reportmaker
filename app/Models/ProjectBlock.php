<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBlock extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id', 'block_id', 'selected',
        'override_text', 'override_label', 'override_icon', 'override_tips',
        'show_icon', 'show_label', 'show_text', 'show_tips', 'show_severity'
    ];
    protected $casts = [
        'selected' => 'boolean',
        'override_tips' => 'array',
        'show_icon' => 'boolean',
        'show_label' => 'boolean',
        'show_text' => 'boolean',
        'show_tips' => 'boolean',
        'show_severity' => 'boolean',
    ];

    public function project(){ return $this->belongsTo(Project::class); }
    public function block(){ return $this->belongsTo(Block::class); }
}