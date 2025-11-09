<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'api_key',
    ];

    /**
     * Relationships
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
