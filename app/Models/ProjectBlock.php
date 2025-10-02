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
        'project_id',
        'block_id',
        'selected',
        'override_text',
        // LEGG TIL DISSE
        'override_label',
        'override_icon',
        'override_tips',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'selected' => 'boolean',
        // LEGG TIL DENNE
        'override_tips' => 'array',
    ];

    public function project(){ return $this->belongsTo(Project::class); }
    public function block(){ return $this->belongsTo(Block::class); }
}