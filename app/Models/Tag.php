<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;
    protected $fillable = ['name','slug'];

    protected static function booted(){
        static::creating(function($tag){
            $tag->slug = $tag->slug ?: Str::slug($tag->name);
        });
    }

    public function prompts(){ return $this->belongsToMany(Prompt::class); }
}