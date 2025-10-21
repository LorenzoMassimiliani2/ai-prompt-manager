<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'parent_id', 'name', 'slug', 'path', 'depth', 'sort'];

    // relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }
    public function prompts()
    {
        return $this->belongsToMany(Prompt::class, 'folder_prompt')->withTimestamps()->withPivot('user_id');
    }

    public function folders()
    {
        return $this->belongsToMany(\App\Models\Folder::class, 'folder_prompt')->withTimestamps()->withPivot('user_id');
    }

    // scopes
    public function scopeForUser($q, $userId)
    {
        return $q->where('user_id', $userId);
    }
    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }

    // helpers
    public static function computePath(?Folder $parent, string $name): array
    {
        $slug = Str::slug($name);
        $path = ($parent?->path ?? '') . '/' . $slug;
        $depth = ($parent?->depth ?? -1) + 1;
        return [$slug, $path, $depth];
    }
}
