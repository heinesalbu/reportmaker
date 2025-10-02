<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBlock extends Model
{
    protected $fillable = ['project_id','block_id','selected','override_text'];

    protected $casts = [
        'selected' => 'boolean',
    ];

    public function project(){ return $this->belongsTo(Project::class); }
    public function block(){ return $this->belongsTo(Block::class); }
}
