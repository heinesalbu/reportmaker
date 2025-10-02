<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name','org_no','domains','contact_name','contact_email','notes'
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}

